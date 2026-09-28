<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Channel;
use App\Models\LeadSource;
use App\Models\Person;
use App\Support\PhoneNormalizer;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class WhatsappIngestionService
{
    /**
     * Equivale a la RPC find_duplicate_contacts(p_phone, p_email, p_first_name, p_company_id).
     *
     * El teléfono normalizado gana. El email se usa si no hay teléfono.
     * Nombre y empresa solo se usan si no hay teléfono ni email.
     *
     * @return Collection<int, Person>
     */
    public function findDuplicates(?string $phone, ?string $email, ?string $firstName, ?int $companyId): Collection
    {
        $normalized = PhoneNormalizer::normalized($phone);
        if ($normalized) {
            return Person::query()->where('phone_normalized', $normalized)->orderBy('id')->get();
        }

        $email = $email !== null ? strtolower(trim($email)) : '';
        if ($email !== '') {
            return Person::query()->where('email', $email)->orderBy('id')->get();
        }

        $firstName = $firstName !== null ? trim($firstName) : '';
        if ($firstName !== '' && $companyId) {
            return Person::query()
                ->where('name', $firstName)
                ->whereHas('organizations', fn ($query) => $query->where('organizations.id', $companyId))
                ->orderBy('id')
                ->get();
        }

        return Person::query()->whereRaw('0 = 1')->get();
    }

    /**
     * Contrato de un mensaje entrante: deduplicar contacto, crearlo si falta
     * y registrar la actividad de WhatsApp. external_ref hace el reintento idempotente.
     *
     * @param  array{phone?: ?string, email?: ?string, first_name?: ?string, company_id?: ?int, message?: ?string, result?: ?string, external_ref?: ?string}  $input
     * @return array{person: Person, activity: Activity, created_contact: bool, created_activity: bool}
     */
    public function ingest(array $input): array
    {
        return DB::transaction(function () use ($input) {
            $externalRef = $this->externalRef($input['external_ref'] ?? null);
            if ($externalRef) {
                $existing = Activity::query()->where('external_ref', $externalRef)->first();
                if ($existing) {
                    $existing->loadMissing('person', 'channel');

                    return [
                        'person' => $existing->person,
                        'activity' => $existing,
                        'created_contact' => false,
                        'created_activity' => false,
                    ];
                }
            }

            $phone = $input['phone'] ?? null;
            $email = isset($input['email']) ? strtolower(trim((string) $input['email'])) : null;
            $email = $email === '' ? null : $email;
            $firstName = trim((string) ($input['first_name'] ?? ''));
            $companyId = isset($input['company_id']) ? (int) $input['company_id'] : null;

            $matches = $this->findDuplicates($phone, $email, $firstName !== '' ? $firstName : null, $companyId);
            $createdContact = false;

            if ($matches->isEmpty()) {
                $source = LeadSource::query()->where('name', 'WhatsApp')->first();
                if (! $source) {
                    throw new RuntimeException('Falta la fuente WhatsApp en lead_sources.');
                }

                $person = Person::query()->create([
                    'name' => $firstName !== '' ? $firstName : 'Sin nombre',
                    'phone_raw' => $phone,
                    'email' => $email,
                    'source' => 'WhatsApp',
                    'source_id' => $source->id,
                    'next_action' => 'Responder mensaje entrante',
                    'next_action_at' => now(),
                    'owner_id' => null,
                ]);
                $createdContact = true;

                if ($companyId) {
                    $person->organizations()->syncWithoutDetaching([
                        $companyId => ['role' => 'otro'],
                    ]);
                }
            } else {
                $person = $matches->first();
            }

            $channel = Channel::query()->where('name', 'WhatsApp')->first();
            if (! $channel) {
                throw new RuntimeException('Falta el canal WhatsApp en channels.');
            }

            $message = trim((string) ($input['result'] ?? $input['message'] ?? ''));

            try {
                $activity = Activity::query()->create([
                    'person_id' => $person->id,
                    'channel_id' => $channel->id,
                    'user_id' => null,
                    'type' => 'WhatsApp',
                    'title' => 'Mensaje de WhatsApp',
                    'body' => $message,
                    'is_done' => false,
                    'external_ref' => $externalRef,
                ]);
                $createdActivity = true;
            } catch (UniqueConstraintViolationException) {
                $activity = Activity::query()->where('external_ref', $externalRef)->firstOrFail();
                $createdActivity = false;
                $createdContact = false;
                $person = $activity->person ?? $person;
            }

            $person = $person->refresh();
            $activity = $activity->refresh()->load('channel');

            return [
                'person' => $person,
                'activity' => $activity,
                'created_contact' => $createdContact,
                'created_activity' => $createdActivity,
            ];
        });
    }

    private function externalRef(mixed $value): ?string
    {
        $ref = trim((string) ($value ?? ''));

        return $ref === '' ? null : $ref;
    }
}
