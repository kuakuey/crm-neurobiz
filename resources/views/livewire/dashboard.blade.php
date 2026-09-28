<div class="space-y-6">
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['Leads esta semana', $leadsThisWeek, 'teal'],
            ['Deals abiertos', $openDeals, 'navy'],
            ['Valor en pipeline', '$'.number_format($pipelineValue, 0), 'gold'],
            ['Tareas hoy / vencidas', $tasksToday.' / '.$overdue, 'rose'],
        ] as [$label, $value])
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="text-xs uppercase tracking-wide text-slate-500">{{ $label }}</div>
                <div class="mt-2 text-2xl font-semibold text-navy">{{ $value }}</div>
            </div>
        @endforeach
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="font-semibold text-navy">Personas recientes</h2>
                <a href="{{ route('people.create') }}" class="text-sm text-teal-700" wire:navigate>Nueva</a>
            </div>
            <ul class="divide-y divide-slate-100">
                @forelse ($recentPeople as $person)
                    <li class="flex items-center justify-between py-3">
                        <a href="{{ route('people.show', $person) }}" wire:navigate class="font-medium text-slate-800 hover:text-teal-700">{{ $person->name }}</a>
                        <span class="text-xs text-slate-500">{{ $person->phone_e164 ?: $person->email }}</span>
                    </li>
                @empty
                    <li class="py-6 text-sm text-slate-500">Aún no hay personas. Crea la primera ficha comercial.</li>
                @endforelse
            </ul>
        </section>
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="mb-4 font-semibold text-navy">Seguimiento pendiente</h2>
            <ul class="divide-y divide-slate-100">
                @forelse ($todayTasks as $task)
                    <li class="py-3">
                        <div class="font-medium">{{ $task->title }}</div>
                        <div class="text-xs text-slate-500">{{ $task->person?->name }} · {{ optional($task->due_at)->format('d/m H:i') }}</div>
                    </li>
                @empty
                    <li class="py-6 text-sm text-slate-500">No hay tareas vencidas ni para hoy.</li>
                @endforelse
            </ul>
        </section>
    </div>
</div>
