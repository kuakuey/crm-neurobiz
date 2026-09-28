<div class="grid gap-6 lg:grid-cols-3">
    <div class="space-y-4 lg:col-span-2">
        @if ($person->requiresResponse())
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                Próxima acción vencida o de hoy, con un mensaje de WhatsApp sin completar.
            </div>
        @endif
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="text-xl font-semibold text-navy">{{ $person->name }}</h2>
                    <p class="text-sm text-slate-500">{{ $person->phone_e164 ?: 'Sin teléfono' }} · {{ $person->email ?: 'Sin email' }}</p>
                </div>
                <a href="{{ route('people.edit', $person) }}" class="text-sm text-teal-700" wire:navigate>Editar</a>
            </div>
            <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
                <div><dt class="text-slate-500">Fuente</dt><dd>{{ $person->sourceLabel() }}</dd></div>
                <div>
                    <dt class="text-slate-500">Gestor</dt>
                    <dd>
                        {{ $person->owner?->name ?: 'Sin gestor' }}
                        @if ($canAssign && ! $person->owner_id)
                            <form wire:submit="assignOwner" class="mt-2 flex flex-wrap items-center gap-2">
                                <select wire:model="assigneeId" class="rounded-lg border-slate-300 text-xs">
                                    <option value="">Elegir gestor</option>
                                    @foreach ($users as $user)
                                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                                    @endforeach
                                </select>
                                <button class="rounded-lg bg-navy px-2 py-1 text-xs text-white">Asignar</button>
                            </form>
                            @if ($assignError)
                                <p class="mt-1 text-xs text-red-600">{{ $assignError }}</p>
                            @endif
                        @endif
                    </dd>
                </div>
                <div><dt class="text-slate-500">Próxima acción</dt><dd>{{ $person->next_action ?: '—' }}</dd></div>
                <div><dt class="text-slate-500">Fecha</dt><dd>{{ $person->next_action_at?->format('d/m/Y H:i') ?: '—' }}</dd></div>
                <div class="col-span-2"><dt class="text-slate-500">Empresas</dt><dd>{{ $person->organizations->pluck('name')->join(', ') ?: '—' }}</dd></div>
            </dl>
            @if($person->notes)
                <p class="mt-4 whitespace-pre-line text-sm text-slate-700">{{ $person->notes }}</p>
            @endif
        </section>
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-3 flex items-center justify-between gap-3">
                <h3 class="font-semibold">Oportunidades</h3>
                <a href="{{ route('deals.create', ['person_id' => $person->id]) }}" class="rounded-lg bg-teal-600 px-3 py-2 text-sm font-medium text-white hover:bg-teal-700" wire:navigate>Crear oportunidad</a>
            </div>
            <ul class="divide-y divide-slate-100 text-sm">
                @forelse ($person->deals as $deal)
                    <li class="flex justify-between py-2">
                        <a href="{{ route('deals.show', $deal) }}" wire:navigate class="font-medium hover:text-teal-700">{{ $deal->title }}</a>
                        <span>{{ $deal->stage?->name }} · {{ $deal->status }}</span>
                    </li>
                @empty
                    <li class="py-4 text-slate-500">Sin oportunidades. Puedes crear una manualmente.</li>
                @endforelse
            </ul>
        </section>
    </div>
    <aside class="space-y-4">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="mb-3 font-semibold">Nueva actividad</h3>
            <form wire:submit="addActivity" class="space-y-3">
                <select wire:model="taskType" class="w-full rounded-lg border-slate-300 text-sm">
                    <option value="task">Tarea</option>
                    <option value="call">Llamada</option>
                    <option value="whatsapp">WhatsApp</option>
                    <option value="meeting">Reunión</option>
                    <option value="note">Nota</option>
                </select>
                <input type="text" wire:model="taskTitle" placeholder="Título" class="w-full rounded-lg border-slate-300 text-sm">
                <input type="datetime-local" wire:model="taskDue" class="w-full rounded-lg border-slate-300 text-sm">
                <button class="w-full rounded-lg bg-navy py-2 text-sm text-white">Guardar</button>
            </form>
        </section>
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="mb-3 font-semibold">Historial</h3>
            <ul class="space-y-3 text-sm">
                @forelse ($person->activities->sortByDesc('created_at') as $activity)
                    <li class="border-l-2 border-slate-200 pl-3">
                        <div class="font-medium">{{ $activity->title }}</div>
                        <div class="text-xs text-slate-500">
                            {{ $activity->channel?->name ?? $activity->type }}
                            · {{ $activity->created_at?->format('d/m/Y H:i') }}
                            · {{ $activity->user?->name ?: 'Sin gestor' }}
                        </div>
                        @if ($activity->body)
                            <p class="mt-1 whitespace-pre-line text-slate-700">{{ $activity->body }}</p>
                        @endif
                    </li>
                @empty
                    <li class="text-slate-500">Sin actividades.</li>
                @endforelse
            </ul>
        </section>
    </aside>
</div>
