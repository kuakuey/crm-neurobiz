<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Deal;
use App\Services\DealService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DealsController extends Controller
{
    public function store(Request $request, DealService $deals): JsonResponse
    {
        $data = $request->validate([
            'person_id' => ['nullable', 'integer', 'exists:people,id'],
            'organization_id' => ['nullable', 'integer', 'exists:organizations,id'],
            'offering_id' => ['nullable', 'integer', 'exists:offerings,id'],
            'offering' => ['nullable', 'string'],
            'pipeline_id' => ['nullable', 'integer', 'exists:pipelines,id'],
            'pipeline' => ['nullable', 'string'],
            'stage_id' => ['nullable', 'integer', 'exists:stages,id'],
            'stage' => ['nullable', 'string'],
            'title' => ['nullable', 'string', 'max:255'],
            'amount' => ['nullable', 'numeric'],
            'source' => ['nullable', 'string'],
            'chatwoot_conversation_id' => ['nullable', 'string'],
        ]);

        $deal = $deals->fromApi($data, $request->user()?->id);

        return response()->json(['data' => $deals->serialize($deal)], 201);
    }

    public function changeStage(Request $request, Deal $deal, DealService $deals): JsonResponse
    {
        $data = $request->validate([
            'stage' => ['required'],
        ]);

        $deal = $deals->changeStage($deal, $data['stage']);

        return response()->json(['data' => $deals->serialize($deal)]);
    }
}
