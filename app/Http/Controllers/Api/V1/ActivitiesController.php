<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivitiesController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'person_id' => ['nullable', 'integer', 'exists:people,id'],
            'deal_id' => ['nullable', 'integer', 'exists:deals,id'],
            'type' => ['nullable', 'string', 'in:call,whatsapp,meeting,task,note'],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
            'due_at' => ['nullable', 'date'],
        ]);

        $activity = Activity::query()->create([
            ...$data,
            'type' => $data['type'] ?? 'task',
            'user_id' => $request->user()?->id,
            'is_done' => false,
        ]);

        return response()->json(['data' => $activity], 201);
    }
}
