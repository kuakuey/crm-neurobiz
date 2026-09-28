<div class="grid gap-6 lg:grid-cols-3">
    <div class="space-y-4 lg:col-span-2">
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-start justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-navy">{{ $person->name }}</h2>
                    <p class="text-sm text-slate-500">{{ $person->phone_e164 ?: 'Sin teléfono' }} · {{ $person->email ?: 'Sin email' }}</p>
                </div>
                <a href="{{ route('people.edit', $person) }}" class="text-sm text-teal-700" wire:navigate>Editar</a>
            </div>
            <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
                <div><dt class="text-slate-500">Origen</dt><dd>{{ $person->source }}</dd></div>
                <div><dt class="text-slate-500">Owner</dt><dd>{{ $person->owner?->name ?: '—' }}</dd></div>
                <div><dt class="text-slate-500">Chatwoot</dt><dd>{{ $person->chatwoot_contact_id ?: 'No vinculado' }}</dd></div>
                <div><dt class="text-slate-500">Empresas</dt><dd>{{ $person->organizations->pluck('name')->join(', ') ?: '—' }}</dd></div>
            </dl>
            @if($person->notes)
                <p class="mt-4 whitespace-pre-line text-sm text-slate-700">{{ $person->notes }}</p>
            @endif
        </section>
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-3 flex items-center justify-between">
                <h3 class="font-semibold">Deals</h3>
                <a href="{{ route('deals.create', ['person_id' => $person->id]) }}" class="text-sm text-teal-700" wire:navigate>Nuevo deal</a>
            </div>
            <ul class="divide-y divide-slate-100 text-sm">
                @forelse ($person->deals as $deal)
                    <li class="flex justify-between py-2">
                        <a href="{{ route('deals.show', $deal) }}" wire:navigate class="font-medium hover:text-teal-700">{{ $deal->title }}</a>
                        <span>{{ $deal->stage?->name }} · {{ $deal->status }}</span>
                    </li>
                @empty
                    <li class="py-4 text-slate-500">Sin deals.</li>
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
                @foreach ($person->activities->sortByDesc('created_at') as $activity)
                    <li>
                        <div class="font-medium">{{ $activity->title }}</div>
                        <div class="text-xs text-slate-500">{{ $activity->type }} · {{ $activity->created_at->format('d/m H:i') }}</div>
                    </li>
                @endforeach
            </ul>
        </section>
    </aside>
</div>
