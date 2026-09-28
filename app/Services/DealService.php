<?php

namespace App\Services;

use App\Models\Deal;
use App\Models\Offering;
use App\Models\Person;
use App\Models\Pipeline;
use App\Models\Stage;
use Illuminate\Support\Arr;

class DealService
{
    public function create(array $data): Deal
    {
        $pipeline = Pipeline::query()->findOrFail($data['pipeline_id']);
        $stage = isset($data['stage_id'])
            ? Stage::query()->findOrFail($data['stage_id'])
            : $pipeline->firstStage();

        if (! $stage) {
            throw new \RuntimeException('El pipeline no tiene etapas.');
        }

        $offering = isset($data['offering_id']) ? Offering::query()->find($data['offering_id']) : null;

        return Deal::query()->create([
            'person_id' => $data['person_id'] ?? null,
            'organization_id' => $data['organization_id'] ?? null,
            'offering_id' => $offering?->id,
            'pipeline_id' => $pipeline->id,
            'stage_id' => $stage->id,
            'owner_id' => $data['owner_id'] ?? null,
            'title' => $data['title'] ?? trim(($offering?->name ?? 'Deal').' · '.($data['person_name'] ?? 'Lead')),
            'amount' => $data['amount'] ?? $offering?->default_amount,
            'probability' => $data['probability'] ?? $stage->probability_default,
            'close_date' => $data['close_date'] ?? null,
            'source' => $data['source'] ?? 'manual',
            'status' => 'open',
            'chatwoot_conversation_id' => $data['chatwoot_conversation_id'] ?? null,
            'stage_changed_at' => now(),
            'kpis' => $data['kpis'] ?? null,
            'diagnostic_risk_level' => $data['diagnostic_risk_level'] ?? null,
            'diagnostic_result' => $data['diagnostic_result'] ?? null,
        ]);
    }

    public function changeStage(Deal $deal, Stage|string|int $stage): Deal
    {
        if (! $stage instanceof Stage) {
            $stage = is_numeric($stage)
                ? Stage::query()->findOrFail($stage)
                : Stage::query()->where('pipeline_id', $deal->pipeline_id)->where('slug', $stage)->firstOrFail();
        }

        $deal->moveToStage($stage);

        return $deal->fresh(['stage', 'person', 'offering']);
    }

    public function serialize(Deal $deal): array
    {
        $deal->loadMissing(['stage', 'person', 'offering', 'pipeline', 'owner']);

        return [
            'id' => $deal->id,
            'title' => $deal->title,
            'status' => $deal->status,
            'amount' => $deal->amount,
            'probability' => $deal->probability,
            'source' => $deal->source,
            'stage' => $deal->stage?->only(['id', 'name', 'slug']),
            'pipeline' => $deal->pipeline?->only(['id', 'name', 'slug']),
            'offering' => $deal->offering?->only(['id', 'name', 'slug', 'is_retainer']),
            'person_id' => $deal->person_id,
            'organization_id' => $deal->organization_id,
            'owner_id' => $deal->owner_id,
            'chatwoot_conversation_id' => $deal->chatwoot_conversation_id,
            'diagnostic_risk_level' => $deal->diagnostic_risk_level,
            'kpis' => $deal->kpis,
            'stage_changed_at' => $deal->stage_changed_at?->toIso8601String(),
        ];
    }

    public function fromApi(array $data, ?int $ownerId = null): Deal
    {
        if (empty($data['pipeline_id']) && ! empty($data['pipeline'])) {
            $data['pipeline_id'] = Pipeline::query()->where('slug', $data['pipeline'])->value('id');
        }

        if (empty($data['stage_id']) && ! empty($data['stage'])) {
            $data['stage_id'] = Stage::query()
                ->where('pipeline_id', $data['pipeline_id'])
                ->where('slug', $data['stage'])
                ->value('id');
        }

        if (empty($data['offering_id']) && ! empty($data['offering'])) {
            $data['offering_id'] = Offering::query()->where('slug', $data['offering'])->value('id');
        }

        $data['owner_id'] = $data['owner_id'] ?? $ownerId;

        return $this->create(Arr::only($data, [
            'person_id', 'organization_id', 'offering_id', 'pipeline_id', 'stage_id',
            'owner_id', 'title', 'amount', 'probability', 'close_date', 'source',
            'chatwoot_conversation_id', 'kpis', 'diagnostic_risk_level', 'diagnostic_result',
        ]));
    }
}
