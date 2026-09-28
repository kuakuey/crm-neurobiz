<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Channel;
use App\Models\LeadSource;
use App\Models\Person;
use App\Services\WhatsappIngestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WhatsappIngestionController extends Controller
{
    public function channels(Request $request): JsonResponse
    {
        $channels = Channel::query()
            ->when($request->string('name')->toString() !== '', fn ($query) => $query->where('name', $request->string('name')->toString()))
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json(['data' => $channels]);
    }

    public function leadSources(): JsonResponse
    {
        return response()->json([
            'data' => LeadSource::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function findDuplicates(Request $request, WhatsappIngestionService $ingestion): JsonResponse
    {
        $data = $request->validate([
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:255'],
            'first_name' => ['nullable', 'string', 'max:255'],
            'company_id' => ['nullable', 'integer'],
        ]);

        $people = $ingestion->findDuplicates(
            $data['phone'] ?? null,
            $data['email'] ?? null,
            $data['first_name'] ?? null,
            isset($data['company_id']) ? (int) $data['company_id'] : null,
        );

        return response()->json([
            'data' => $people->map(fn (Person $person) => $this->contactPayload($person))->values(),
        ]);
    }

    public function store(Request $request, WhatsappIngestionService $ingestion): JsonResponse
    {
        $data = $request->validate([
            'phone' => ['nullable', 'string', 'max:40', 'required_without:email'],
            'email' => ['nullable', 'email', 'max:255', 'required_without:phone'],
            'first_name' => ['nullable', 'string', 'max:255'],
            'company_id' => ['nullable', 'integer', 'exists:organizations,id'],
            'message' => ['nullable', 'string', 'required_without:result'],
            'result' => ['nullable', 'string', 'required_without:message'],
            'external_ref' => ['nullable', 'string', 'max:255'],
        ]);

        $result = $ingestion->ingest($data);

        return response()->json([
            'data' => [
                'contact' => $this->contactPayload($result['person']),
                'activity' => $this->activityPayload($result['activity']),
                'created_contact' => $result['created_contact'],
                'created_activity' => $result['created_activity'],
            ],
        ], $result['created_activity'] ? 201 : 200);
    }

    private function contactPayload(Person $person): array
    {
        $person->loadMissing('leadSource');

        return [
            'id' => $person->id,
            'first_name' => $person->name,
            'phone' => $person->phone_raw,
            'phone_normalized' => $person->phone_normalized,
            'email' => $person->email,
            'source' => $person->sourceLabel(),
            'source_id' => $person->source_id,
            'owner_user_id' => $person->owner_id,
            'next_action' => $person->next_action,
            'next_action_at' => $person->next_action_at?->toIso8601String(),
        ];
    }

    private function activityPayload(Activity $activity): array
    {
        $activity->loadMissing('channel');

        return [
            'id' => $activity->id,
            'contact_id' => $activity->person_id,
            'channel_id' => $activity->channel_id,
            'channel' => $activity->channel?->name,
            'activity_type' => $activity->type,
            'result' => $activity->body,
            'owner_user_id' => $activity->user_id,
            'completed' => $activity->is_done,
            'external_ref' => $activity->external_ref,
            'created_at' => $activity->created_at?->toIso8601String(),
        ];
    }
}
