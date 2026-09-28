<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Person;
use App\Services\PersonMatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PeopleController extends Controller
{
    public function show(Person $person): JsonResponse
    {
        $person->load(['owner', 'organizations', 'deals.stage', 'deals.offering', 'activities' => fn ($q) => $q->latest()->limit(20)]);

        return response()->json([
            'data' => [
                'id' => $person->id,
                'name' => $person->name,
                'phone_e164' => $person->phone_e164,
                'email' => $person->email,
                'source' => $person->source,
                'owner_id' => $person->owner_id,
                'chatwoot_contact_id' => $person->chatwoot_contact_id,
                'notes' => $person->notes,
                'organizations' => $person->organizations,
                'open_deal' => $person->openDeal(),
                'recent_activities' => $person->activities,
            ],
        ]);
    }

    public function store(Request $request, PersonMatcher $matcher): JsonResponse
    {
        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'phone_e164' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email'],
            'source' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string'],
            'identifier' => ['nullable', 'string'],
            'chatwoot_contact_id' => ['nullable', 'string'],
        ]);

        $person = $matcher->upsert($data, $request->user()?->id);

        return response()->json(['data' => $person], 201);
    }
}
