<div class="space-y-6">
    <div class="flex gap-2">
        @foreach (['chatwoot'=>'Chatwoot','n8n'=>'n8n','logs'=>'Logs'] as $key => $label)
            <button wire:click="$set('tab', '{{ $key }}')" class="rounded-full px-3 py-1 text-sm {{ $tab === $key ? 'bg-navy text-white' : 'border bg-white' }}">{{ $label }}</button>
        @endforeach
    </div>

    @if($tab === 'chatwoot')
        <form wire:submit="saveChatwoot" class="max-w-2xl space-y-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-sm text-slate-600">URL del webhook inbound: <code class="rounded bg-slate-100 px-1">{{ $webhookUrl }}</code></p>
            <p class="text-sm text-slate-600">Dashboard App: <code class="rounded bg-slate-100 px-1">{{ url('/embed/chatwoot') }}</code></p>
            <input type="url" wire:model="cw_base_url" placeholder="https://chatwoot.tudominio.com" class="w-full rounded-lg border-slate-300 text-sm">
            <input type="text" wire:model="cw_account_id" placeholder="Account ID" class="w-full rounded-lg border-slate-300 text-sm">
            <input type="password" wire:model="cw_access_token" placeholder="API access token" class="w-full rounded-lg border-slate-300 text-sm">
            <input type="text" wire:model="cw_webhook_secret" placeholder="Webhook secret (HMAC)" class="w-full rounded-lg border-slate-300 text-sm">
            <input type="url" wire:model="cw_dashboard_origin" placeholder="Origen del dashboard (para postMessage)" class="w-full rounded-lg border-slate-300 text-sm">
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="cw_active" class="rounded text-teal-600"> Activo</label>
            <button class="rounded-lg bg-teal-600 px-4 py-2 text-sm text-white">Guardar Chatwoot</button>
        </form>
    @endif

    @if($tab === 'n8n')
        <form wire:submit="saveN8n" class="max-w-2xl space-y-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-sm text-slate-600">API CRM: <code class="rounded bg-slate-100 px-1">{{ $apiBase }}</code> (Bearer Sanctum)</p>
            <button type="button" wire:click="generateApiToken" class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm">Generar token API para n8n</button>
            @if($apiToken)
                <p class="break-all rounded bg-amber-50 p-2 text-xs">{{ $apiToken }}</p>
            @endif
            <p class="text-sm text-slate-600">Diagnóstico gratuito: <code class="rounded bg-slate-100 px-1">{{ $diagnosticUrl }}</code></p>
            <input type="url" wire:model="n8n_webhook_url" placeholder="https://n8n.../webhook/crm-neurobiz" class="w-full rounded-lg border-slate-300 text-sm">
            <input type="text" wire:model="n8n_hmac_secret" placeholder="HMAC secret" class="w-full rounded-lg border-slate-300 text-sm">
            <div class="grid gap-3 sm:grid-cols-2">
                <input type="text" wire:model="n8n_header_name" class="rounded-lg border-slate-300 text-sm">
                <input type="text" wire:model="n8n_header_value" placeholder="Header auth opcional" class="rounded-lg border-slate-300 text-sm">
            </div>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="n8n_active" class="rounded text-teal-600"> Activo</label>
            <button class="rounded-lg bg-teal-600 px-4 py-2 text-sm text-white">Guardar n8n</button>
        </form>
    @endif

    @if($tab === 'logs')
        <div class="grid gap-6 lg:grid-cols-2">
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="mb-3 font-semibold">Inbound</h3>
                <ul class="space-y-2 text-xs">
                    @foreach ($inbound as $log)
                        <li class="rounded-lg bg-slate-50 p-2">
                            {{ $log->provider }} · {{ $log->event_type }} · {{ $log->processed ? 'ok' : 'pendiente' }}
                            @if($log->skip_reason) · {{ $log->skip_reason }} @endif
                        </li>
                    @endforeach
                </ul>
            </section>
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="mb-3 font-semibold">Outbound n8n</h3>
                <ul class="space-y-2 text-xs">
                    @foreach ($outbound as $event)
                        <li class="flex items-center justify-between rounded-lg bg-slate-50 p-2">
                            <span>{{ $event->type }} · {{ $event->status }}</span>
                            @if($event->status !== 'sent')
                                <button wire:click="retry({{ $event->id }})" class="text-teal-700">Reintentar</button>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </section>
        </div>
    @endif
</div>
