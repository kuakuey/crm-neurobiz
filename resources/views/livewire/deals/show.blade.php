<div class="grid gap-6 lg:grid-cols-3">
    <div class="space-y-4 lg:col-span-2">
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="text-xs uppercase tracking-wide text-slate-400">{{ $deal->pipeline?->name }}</div>
            <h2 class="text-xl font-semibold text-navy">{{ $deal->title }}</h2>
            <p class="text-sm text-slate-500">
                @if($deal->person)
                    <a class="text-teal-800 hover:underline" href="{{ route('people.show', $deal->person) }}" wire:navigate>{{ $deal->person->name }}</a>
                @endif
                · {{ $deal->offering?->name ?: 'Sin oferta' }} · {{ $deal->amount ? '$'.number_format($deal->amount, 0) : 'Sin monto' }}
            </p>
            @if($deal->diagnostic_risk_level)
                <div class="mt-3 rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-900">Diagnóstico gratuito · riesgo {{ $deal->diagnostic_risk_level }}</div>
            @endif
            @if($deal->offering?->is_retainer)
                <div class="mt-3 text-sm text-teal-800">Retainer {{ $deal->offering->retainer_months }} meses · {{ $deal->offering->kpi_notes }}</div>
            @endif
            <form wire:submit="changeStage" class="mt-4 flex gap-2">
                <select wire:model="stage_id" class="flex-1 rounded-lg border-slate-300 text-sm">
                    @foreach ($deal->pipeline->stages as $stage)
                        <option value="{{ $stage->id }}">{{ $stage->name }}</option>
                    @endforeach
                </select>
                <button class="rounded-lg bg-navy px-4 py-2 text-sm text-white">Mover</button>
            </form>
        </section>
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="mb-3 font-semibold">Actividades</h3>
            <ul class="space-y-2 text-sm">
                @foreach ($deal->activities->sortByDesc('created_at') as $activity)
                    <li>{{ $activity->title }} <span class="text-slate-400">· {{ $activity->type }}</span></li>
                @endforeach
            </ul>
        </section>
    </div>
    <aside class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <h3 class="mb-3 font-semibold">Registrar</h3>
        <form wire:submit="addActivity" class="space-y-3">
            <select wire:model="taskType" class="w-full rounded-lg border-slate-300 text-sm">
                <option value="task">Tarea</option>
                <option value="call">Llamada</option>
                <option value="whatsapp">WhatsApp</option>
                <option value="meeting">Reunión</option>
                <option value="note">Nota</option>
            </select>
            <input type="text" wire:model="taskTitle" class="w-full rounded-lg border-slate-300 text-sm" placeholder="Título">
            <input type="datetime-local" wire:model="taskDue" class="w-full rounded-lg border-slate-300 text-sm">
            <button class="w-full rounded-lg bg-teal-600 py-2 text-sm text-white">Guardar</button>
        </form>
        <dl class="mt-6 space-y-2 text-sm">
            <div><dt class="text-slate-500">Estado</dt><dd>{{ $deal->status }}</dd></div>
            <div><dt class="text-slate-500">Owner</dt><dd>{{ $deal->owner?->name }}</dd></div>
            <div><dt class="text-slate-500">Conversación Chatwoot</dt><dd>{{ $deal->chatwoot_conversation_id ?: '—' }}</dd></div>
            <div><dt class="text-slate-500">Último cambio de etapa</dt><dd>{{ optional($deal->stage_changed_at)->format('d/m/Y H:i') }}</dd></div>
        </dl>
    </aside>
</div>
