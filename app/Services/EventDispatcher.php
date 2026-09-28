<?php

namespace App\Services;

use App\Jobs\DispatchOutboundEventJob;
use App\Models\OutboundEvent;
use Illuminate\Support\Str;

class EventDispatcher
{
    public function dispatch(string $type, array $payload): OutboundEvent
    {
        $event = OutboundEvent::query()->create([
            'event_id' => (string) Str::uuid(),
            'type' => $type,
            'payload' => array_merge($payload, [
                'occurred_at' => now()->toIso8601String(),
            ]),
            'status' => 'pending',
            'attempts' => 0,
        ]);

        DispatchOutboundEventJob::dispatch($event);

        return $event;
    }
}
