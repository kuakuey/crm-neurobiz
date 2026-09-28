<?php

namespace App\Services;

use App\Models\ExternalIdentity;
use App\Models\Person;
use App\Support\PhoneNormalizer;

class PersonMatcher
{
    public function find(array $hints): ?Person
    {
        $phone = PhoneNormalizer::toE164($hints['phone'] ?? $hints['phone_e164'] ?? null);
        $email = isset($hints['email']) ? strtolower(trim((string) $hints['email'])) : null;
        $identifier = $hints['identifier'] ?? $hints['chatwoot_identifier'] ?? null;
        $chatwootId = isset($hints['chatwoot_contact_id']) ? (string) $hints['chatwoot_contact_id'] : null;

        if ($chatwootId) {
            $person = Person::query()->where('chatwoot_contact_id', $chatwootId)->first();
            if ($person) {
                return $person;
            }

            $identity = ExternalIdentity::query()
                ->where('provider', 'chatwoot')
                ->where('external_id', $chatwootId)
                ->first();
            if ($identity) {
                return $identity->person;
            }
        }

        if ($phone) {
            $person = Person::query()->where('phone_e164', $phone)->first();
            if ($person) {
                return $person;
            }
        }

        if ($email) {
            $person = Person::query()->where('email', $email)->first();
            if ($person) {
                return $person;
            }
        }

        if ($identifier) {
            return Person::query()->where('chatwoot_identifier', $identifier)->first();
        }

        return null;
    }

    public function upsert(array $attributes, ?int $ownerId = null): Person
    {
        $person = $this->find($attributes);

        $payload = [
            'name' => $attributes['name'] ?? $person?->name ?? 'Sin nombre',
            'phone_raw' => $attributes['phone'] ?? $attributes['phone_raw'] ?? $person?->phone_raw,
            'email' => $attributes['email'] ?? $person?->email,
            'source' => $attributes['source'] ?? $person?->source ?? 'manual',
            'chatwoot_contact_id' => $attributes['chatwoot_contact_id'] ?? $person?->chatwoot_contact_id,
            'chatwoot_identifier' => $attributes['identifier'] ?? $attributes['chatwoot_identifier'] ?? $person?->chatwoot_identifier,
            'notes' => $attributes['notes'] ?? $person?->notes,
            'owner_id' => $ownerId ?? $person?->owner_id,
        ];

        if ($person) {
            $person->fill(array_filter($payload, fn ($value) => $value !== null))->save();
        } else {
            $person = Person::query()->create($payload);
        }

        if (! empty($attributes['chatwoot_contact_id'])) {
            ExternalIdentity::query()->updateOrCreate(
                [
                    'provider' => 'chatwoot',
                    'external_id' => (string) $attributes['chatwoot_contact_id'],
                ],
                ['person_id' => $person->id]
            );
        }

        return $person->refresh();
    }
}
