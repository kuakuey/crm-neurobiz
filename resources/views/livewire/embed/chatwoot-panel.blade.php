<div class="p-3 text-sm"
     x-data="chatwootEmbed()"
     x-init="init()">
    <div class="mb-3 flex items-center justify-between">
        <div>
            <div class="text-[10px] uppercase tracking-[0.2em] text-teal-700">Neurobiz</div>
            <div class="font-semibold text-navy">Ficha CRM</div>
        </div>
    </div>

    @if($status)
        <p class="rounded-lg bg-slate-100 p-3 text-slate-600">{{ $status }}</p>
    @endif

    @if($person)
        <div class="space-y-3">
            <div class="rounded-xl border border-slate-200 bg-white p-3">
                <div class="font-semibold">{{ $person->name }}</div>
                <div class="text-xs text-slate-500">{{ $person->phone_e164 }} · {{ $person->email }}</div>
                <a href="{{ route('people.show', $person) }}" target="_blank" class="mt-2 inline-block text-xs text-teal-700">Abrir en CRM</a>
            </div>
            @if($deal)
                <div class="rounded-xl border border-slate-200 bg-white p-3">
                    <div class="text-xs text-slate-500">Deal</div>
                    <div class="font-medium">{{ $deal->title }}</div>
                    <div class="text-xs">{{ $deal->stage?->name }} · {{ $deal->offering?->name }}</div>
                    <select class="mt-2 w-full rounded-lg border-slate-300 text-xs" @change="$wire.changeStage($event.target.value)">
                        @foreach ($stages as $stage)
                            <option value="{{ $stage->id }}" @selected($deal->stage_id === $stage->id)>{{ $stage->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <form wire:submit="addTask" class="rounded-xl border border-slate-200 bg-white p-3">
                <input type="text" wire:model="taskTitle" placeholder="Nueva tarea" class="w-full rounded-lg border-slate-300 text-xs">
                <button class="mt-2 w-full rounded-lg bg-navy py-1.5 text-xs text-white">Crear tarea</button>
            </form>
        </div>
    @endif
</div>

<script>
function chatwootEmbed() {
    return {
        init() {
            const allowed = @json(\App\Models\Integration::ofType('chatwoot')?->credential('dashboard_origin'));
            window.addEventListener('message', (event) => {
                if (event.source !== window.parent) return;
                if (allowed && event.origin !== allowed) return;
                let payload = event.data;
                if (typeof payload === 'string') {
                    try { payload = JSON.parse(payload); } catch { return; }
                }
                if (!payload || payload.event !== 'appContext') return;
                const { contact = {}, conversation = {}, currentAgent = {} } = payload.data || {};
                this.$wire.hydrateFromContext(contact, conversation, currentAgent);
            });
            window.parent.postMessage('chatwoot-dashboard-app:fetch-info', '*');
        }
    }
}
</script>
