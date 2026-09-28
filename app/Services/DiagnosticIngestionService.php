<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Deal;
use App\Models\InboundWebhookLog;
use App\Models\Offering;
use App\Models\Person;
use App\Models\Pipeline;
use App\Models\User;
use Illuminate\Support\Arr;

class DiagnosticIngestionService
{
    public function __construct(
        private PersonMatcher $matcher,
        private DealService $deals,
    ) {
    }

    public function ingest(array $payload, bool $signatureOk, ?string $hash = null): array
    {
        $externalId = $hash ?: sha1(json_encode($payload));
        $log = InboundWebhookLog::query()->create([
            'provider' => 'diagnostico',
            'event_type' => 'diagnostico.completed',
            'external_event_id' => $externalId,
            'signature_ok' => $signatureOk,
            'processed' => false,
            'payload' => $payload,
        ]);

        if (! $signatureOk) {
            $log->skip_reason = 'invalid_signature';
            $log->save();

            return ['ok' => false, 'reason' => 'invalid_signature'];
        }

        $person = $this->matcher->upsert([
            'name' => $payload['name'] ?? $payload['contact_name'] ?? 'Lead diagnóstico',
            'phone' => $payload['phone'] ?? $payload['phone_number'] ?? null,
            'email' => $payload['email'] ?? null,
            'source' => 'diagnostico_gratuito',
        ], User::query()->orderBy('id')->value('id'));

        $pipeline = Pipeline::query()->where('slug', 'neurobusiness-b2b')->firstOrFail();
        $offering = Offering::query()->where('slug', 'diagnostico-gratuito')->first();
        $stage = $pipeline->stageBySlug('calificado') ?? $pipeline->firstStage();

        $deal = $person->deals()->where('status', 'open')->latest()->first();
        $risk = $payload['risk_level'] ?? $payload['semaforo'] ?? null;
        $result = Arr::only($payload, ['scores', 'dimensions', 'risk_level', 'semaforo', 'report_url', 'kpis']);

        if ($deal) {
            $deal->fill([
                'offering_id' => $offering?->id ?? $deal->offering_id,
                'source' => 'diagnostico_gratuito',
                'diagnostic_risk_level' => $risk,
                'diagnostic_result' => $result,
            ])->save();
            $deal->moveToStage($stage);
        } else {
            $deal = $this->deals->create([
                'person_id' => $person->id,
                'organization_id' => $payload['organization_id'] ?? null,
                'offering_id' => $offering?->id,
                'pipeline_id' => $pipeline->id,
                'stage_id' => $stage->id,
                'owner_id' => $person->owner_id,
                'title' => 'Diagnóstico gratuito · '.$person->name,
                'source' => 'diagnostico_gratuito',
                'diagnostic_risk_level' => $risk,
                'diagnostic_result' => $result,
            ]);
        }

        Activity::query()->create([
            'person_id' => $person->id,
            'deal_id' => $deal->id,
            'user_id' => $person->owner_id,
            'type' => 'call',
            'title' => 'Llamada post diagnóstico gratuito',
            'body' => 'Riesgo: '.($risk ?: 'sin dato').'. Contactar en las próximas 24 horas.',
            'due_at' => now()->addDay(),
            'is_done' => false,
        ]);

        $log->processed = true;
        $log->save();

        return [
            'ok' => true,
            'person_id' => $person->id,
            'deal_id' => $deal->id,
        ];
    }
}
