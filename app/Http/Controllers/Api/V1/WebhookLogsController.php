<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\InboundWebhookLog;
use App\Models\OutboundEvent;
use App\Jobs\DispatchOutboundEventJob;
use Illuminate\Http\JsonResponse;

class WebhookLogsController extends Controller
{
    public function retryOutbound(OutboundEvent $event): JsonResponse
    {
        $event->status = 'pending';
        $event->last_error = null;
        $event->save();

        DispatchOutboundEventJob::dispatch($event);

        return response()->json(['ok' => true, 'event_id' => $event->event_id]);
    }

    public function inbound(): JsonResponse
    {
        return response()->json([
            'data' => InboundWebhookLog::query()->latest()->limit(50)->get(),
        ]);
    }
}
