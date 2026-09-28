<?php

namespace App\Livewire\Settings;

use App\Jobs\DispatchOutboundEventJob;
use App\Models\InboundWebhookLog;
use App\Models\Integration;
use App\Models\OutboundEvent;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Integraciones')]
class Integrations extends Component
{
    public string $tab = 'chatwoot';

    public string $cw_base_url = '';

    public string $cw_account_id = '';

    public string $cw_access_token = '';

    public string $cw_webhook_secret = '';

    public string $cw_dashboard_origin = '';

    public bool $cw_active = false;

    public string $n8n_webhook_url = '';

    public string $n8n_hmac_secret = '';

    public string $n8n_header_name = 'X-N8N-Auth';

    public string $n8n_header_value = '';

    public bool $n8n_active = false;

    public ?string $apiToken = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->canManageSettings(), 403);

        $cw = Integration::ofType('chatwoot');
        $this->cw_base_url = $cw?->base_url ?? '';
        $this->cw_account_id = (string) ($cw?->credential('account_id') ?? '');
        $this->cw_access_token = (string) ($cw?->credential('access_token') ?? '');
        $this->cw_webhook_secret = (string) ($cw?->credential('webhook_secret') ?? '');
        $this->cw_dashboard_origin = (string) ($cw?->credential('dashboard_origin') ?? '');
        $this->cw_active = (bool) ($cw?->is_active ?? false);

        $n8n = Integration::ofType('n8n');
        $this->n8n_webhook_url = (string) ($n8n?->credential('webhook_url') ?: $n8n?->base_url);
        $this->n8n_hmac_secret = (string) ($n8n?->credential('hmac_secret') ?? '');
        $this->n8n_header_name = (string) ($n8n?->credential('header_name') ?? 'X-N8N-Auth');
        $this->n8n_header_value = (string) ($n8n?->credential('header_value') ?? '');
        $this->n8n_active = (bool) ($n8n?->is_active ?? false);
    }

    public function saveChatwoot(): void
    {
        Integration::query()->updateOrCreate(
            ['type' => 'chatwoot'],
            [
                'name' => 'Chatwoot',
                'base_url' => rtrim($this->cw_base_url, '/'),
                'is_active' => $this->cw_active,
                'credentials' => [
                    'account_id' => $this->cw_account_id,
                    'access_token' => $this->cw_access_token,
                    'webhook_secret' => $this->cw_webhook_secret,
                    'dashboard_origin' => $this->cw_dashboard_origin,
                ],
            ]
        );

        session()->flash('status', 'Chatwoot guardado.');
    }

    public function saveN8n(): void
    {
        if ($this->n8n_hmac_secret === '') {
            $this->n8n_hmac_secret = Str::random(40);
        }

        Integration::query()->updateOrCreate(
            ['type' => 'n8n'],
            [
                'name' => 'n8n',
                'base_url' => $this->n8n_webhook_url,
                'is_active' => $this->n8n_active,
                'credentials' => [
                    'webhook_url' => $this->n8n_webhook_url,
                    'hmac_secret' => $this->n8n_hmac_secret,
                    'header_name' => $this->n8n_header_name,
                    'header_value' => $this->n8n_header_value,
                ],
            ]
        );

        session()->flash('status', 'n8n guardado.');
    }

    public function generateApiToken(): void
    {
        $this->apiToken = auth()->user()->createToken('n8n')->plainTextToken;
        session()->flash('status', 'Token generado. Cópialo ahora; no se volverá a mostrar.');
    }

    public function retry(int $id): void
    {
        $event = OutboundEvent::query()->findOrFail($id);
        $event->status = 'pending';
        $event->save();
        DispatchOutboundEventJob::dispatch($event);
        session()->flash('status', 'Evento reenviado a la cola.');
    }

    public function render()
    {
        return view('livewire.settings.integrations', [
            'inbound' => InboundWebhookLog::query()->latest()->limit(30)->get(),
            'outbound' => OutboundEvent::query()->latest()->limit(30)->get(),
            'webhookUrl' => url('/webhooks/chatwoot'),
            'diagnosticUrl' => url('/webhooks/diagnostico'),
            'apiBase' => url('/api/v1'),
        ]);
    }
}
