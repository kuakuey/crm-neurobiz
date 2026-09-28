<?php

namespace App\Jobs;

use App\Models\Integration;
use App\Models\OutboundEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

class DispatchOutboundEventJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public function __construct(public OutboundEvent $event)
    {
        $this->onQueue('default');
    }

    public function handle(): void
    {
        $this->event->refresh();

        if ($this->event->status === 'sent') {
            return;
        }

        $integration = Integration::active('n8n');
        if (! $integration) {
            $this->event->markFailed('Integración n8n inactiva o no configurada');

            return;
        }

        $url = $integration->credential('webhook_url') ?: $integration->base_url;
        if (! $url) {
            $this->event->markFailed('URL de webhook n8n vacía');

            return;
        }

        $body = [
            'event_id' => $this->event->event_id,
            'type' => $this->event->type,
            'data' => $this->event->payload,
        ];
        $json = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $secret = (string) ($integration->credential('hmac_secret') ?? '');
        $signature = $secret !== '' ? hash_hmac('sha256', $json, $secret) : '';

        $headers = [
            'Content-Type' => 'application/json',
            'X-CRM-Event' => $this->event->type,
            'X-CRM-Event-Id' => $this->event->event_id,
        ];

        if ($signature !== '') {
            $headers['X-CRM-Signature'] = $signature;
        }

        $headerName = $integration->credential('header_name');
        $headerValue = $integration->credential('header_value');
        if ($headerName && $headerValue) {
            $headers[$headerName] = $headerValue;
        }

        try {
            $response = Http::timeout(20)
                ->withHeaders($headers)
                ->withBody($json, 'application/json')
                ->post($url);

            $this->event->attempts++;

            if ($response->successful()) {
                $this->event->markSent();

                return;
            }

            $this->event->markFailed('HTTP '.$response->status().': '.$response->body());
        } catch (\Throwable $e) {
            $this->event->markFailed($e->getMessage());
            throw $e;
        }
    }
}
