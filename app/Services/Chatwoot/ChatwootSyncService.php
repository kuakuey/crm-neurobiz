<?php

namespace App\Services\Chatwoot;

use App\Models\Deal;
use App\Models\InboundWebhookLog;
use App\Models\Person;
use App\Models\Pipeline;
use App\Services\EventDispatcher;
use App\Services\PersonMatcher;
use App\Support\PhoneNormalizer;
use Illuminate\Support\Arr;

class ChatwootSyncService
{
    public function __construct(
        private ChatwootClient $client,
        private PersonMatcher $matcher,
        private EventDispatcher $dispatcher,
    ) {
    }

    public function handleWebhook(array $payload, bool $signatureOk, ?string $rawHash = null): InboundWebhookLog
    {
        $event = (string) ($payload['event'] ?? 'unknown');
        $externalId = $rawHash ?: sha1(json_encode($payload));

        $existing = InboundWebhookLog::query()
            ->where('provider', 'chatwoot')
            ->where('external_event_id', $externalId)
            ->first();

        if ($existing) {
            $existing->skip_reason = 'duplicate';
            $existing->save();

            return $existing;
        }

        $log = InboundWebhookLog::query()->create([
            'provider' => 'chatwoot',
            'event_type' => $event,
            'external_event_id' => $externalId,
            'signature_ok' => $signatureOk,
            'processed' => false,
            'payload' => $payload,
        ]);

        if (! $signatureOk) {
            $log->skip_reason = 'invalid_signature';
            $log->save();

            return $log;
        }

        if ($this->isEchoFromCrm($payload)) {
            $log->skip_reason = 'crm_echo';
            $log->processed = true;
            $log->save();

            return $log;
        }

        match ($event) {
            'contact_created', 'contact_updated' => $this->syncContact($payload),
            'conversation_created', 'conversation_updated', 'conversation_status_changed', 'message_created' => $this->syncConversation($payload, $event),
            default => null,
        };

        $log->processed = true;
        $log->save();

        return $log;
    }

    public function syncDeal(Deal $deal): void
    {
        if (! $this->client->configured()) {
            return;
        }

        $deal->loadMissing(['person', 'stage', 'offering']);
        $person = $deal->person;
        if (! $person) {
            return;
        }

        $contactId = $this->ensureRemoteContact($person);
        if (! $contactId) {
            return;
        }

        $attributes = [
            'crm_person_id' => $person->id,
            'crm_deal_id' => $deal->id,
            'offering' => $deal->offering?->name,
            'stage' => $deal->stage?->name,
            'crm_sync_source' => 'crm',
            'crm_synced_at' => now()->toIso8601String(),
        ];

        $this->client->updateContact($contactId, [
            'custom_attributes' => $attributes,
        ]);

        if ($deal->chatwoot_conversation_id) {
            $this->client->updateConversationCustomAttributes((int) $deal->chatwoot_conversation_id, $attributes);

            if ($deal->wasRecentlyCreated || $deal->wasChanged('stage_id')) {
                $this->client->addPrivateNote(
                    (int) $deal->chatwoot_conversation_id,
                    sprintf(
                        'CRM Neurobiz: %s · etapa %s',
                        $deal->title,
                        $deal->stage?->name ?? '—'
                    )
                );
            }
        }
    }

    public function resolveFromContext(array $contact, array $conversation = []): array
    {
        $person = $this->matcher->upsert($this->contactHints($contact, $conversation), $this->defaultOwnerId());
        $deal = $this->ensureLeadDeal($person, $conversation);

        if (! empty($conversation['id']) && ! $deal->chatwoot_conversation_id) {
            $deal->chatwoot_conversation_id = (string) ($conversation['display_id'] ?? $conversation['id']);
            $deal->saveQuietly();
        }

        return compact('person', 'deal');
    }

    private function syncContact(array $payload): Person
    {
        $contact = $payload['contact'] ?? $payload;

        return $this->matcher->upsert($this->contactHints($contact), $this->defaultOwnerId());
    }

    private function syncConversation(array $payload, string $event): void
    {
        $conversation = $payload['conversation'] ?? $payload;
        $contact = $payload['contact'] ?? ($conversation['meta']['sender'] ?? []);
        $resolved = $this->resolveFromContext($contact, $conversation);
        $deal = $resolved['deal'];

        if ($event === 'conversation_created' || ($event === 'message_created' && empty($deal->chatwoot_conversation_id))) {
            $this->dispatcher->dispatch('conversation.linked', [
                'person_id' => $resolved['person']->id,
                'deal_id' => $deal->id,
                'chatwoot_conversation_id' => $deal->chatwoot_conversation_id,
                'channel' => $conversation['channel'] ?? ($conversation['inbox']['channel_type'] ?? null),
            ]);
        }
    }

    private function ensureLeadDeal(Person $person, array $conversation = []): Deal
    {
        $existing = $person->deals()->where('status', 'open')->latest()->first();
        if ($existing) {
            if (! empty($conversation['id']) && ! $existing->chatwoot_conversation_id) {
                $existing->chatwoot_conversation_id = (string) ($conversation['display_id'] ?? $conversation['id']);
                $existing->saveQuietly();
            }

            return $existing;
        }

        $pipeline = Pipeline::query()->where('slug', 'neurobusiness-b2b')->first()
            ?? Pipeline::query()->where('is_default', true)->first();
        $stage = $pipeline?->firstStage();

        return Deal::query()->create([
            'person_id' => $person->id,
            'pipeline_id' => $pipeline?->id,
            'stage_id' => $stage?->id,
            'owner_id' => $person->owner_id,
            'title' => 'Lead '.$person->name,
            'source' => 'chatwoot',
            'status' => 'open',
            'probability' => $stage?->probability_default ?? 10,
            'chatwoot_conversation_id' => isset($conversation['id']) ? (string) ($conversation['display_id'] ?? $conversation['id']) : null,
            'stage_changed_at' => now(),
        ]);
    }

    private function ensureRemoteContact(Person $person): ?int
    {
        if ($person->chatwoot_contact_id) {
            return (int) $person->chatwoot_contact_id;
        }

        $created = $this->client->createContact([
            'name' => $person->name,
            'email' => $person->email,
            'phone_number' => $person->phone_e164,
            'identifier' => $person->chatwoot_identifier ?: 'crm-'.$person->id,
            'custom_attributes' => [
                'crm_person_id' => $person->id,
                'crm_sync_source' => 'crm',
            ],
        ]);

        $id = Arr::get($created, 'id') ?? Arr::get($created, 'contact.id') ?? Arr::get($created, 'payload.contact.id');
        if ($id) {
            $person->chatwoot_contact_id = (string) $id;
            $person->saveQuietly();
        }

        return $id ? (int) $id : null;
    }

    private function contactHints(array $contact, array $conversation = []): array
    {
        $phone = $contact['phone_number'] ?? $contact['phone'] ?? null;

        return [
            'name' => $contact['name'] ?? $contact['available_name'] ?? 'Contacto Chatwoot',
            'phone' => $phone,
            'phone_e164' => PhoneNormalizer::toE164($phone),
            'email' => $contact['email'] ?? null,
            'identifier' => $contact['identifier'] ?? null,
            'chatwoot_contact_id' => $contact['id'] ?? ($conversation['contact_id'] ?? null),
            'source' => 'chatwoot',
        ];
    }

    private function isEchoFromCrm(array $payload): bool
    {
        $attributes = $payload['custom_attributes']
            ?? $payload['contact']['custom_attributes']
            ?? $payload['conversation']['custom_attributes']
            ?? [];

        if (($attributes['crm_sync_source'] ?? null) !== 'crm') {
            return false;
        }

        $syncedAt = $attributes['crm_synced_at'] ?? null;
        if (! $syncedAt) {
            return true;
        }

        try {
            return now()->diffInSeconds(\Carbon\Carbon::parse($syncedAt), false) > -60
                && now()->diffInSeconds(\Carbon\Carbon::parse($syncedAt)) < 60;
        } catch (\Throwable) {
            return true;
        }
    }

    private function defaultOwnerId(): ?int
    {
        return \App\Models\User::query()->orderBy('id')->value('id');
    }
}
