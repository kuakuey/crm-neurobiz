<div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <input type="search" wire:model.live.debounce.300ms="q" placeholder="Buscar por nombre, teléfono o email" class="w-full max-w-md rounded-lg border-slate-300 text-sm">
        <a href="{{ route('people.create') }}" wire:navigate class="rounded-lg bg-navy px-3 py-2 text-sm font-medium text-white">Nueva persona</a>
    </div>
    @if ($canViewUnassigned)
        <div class="flex flex-wrap gap-2 text-sm">
            <button type="button" wire:click="$set('filtro', '')" class="rounded-full px-3 py-1 {{ $filtro === '' ? 'bg-navy text-white' : 'border border-slate-200 bg-white text-slate-700' }}">Todos</button>
            <button type="button" wire:click="$set('filtro', 'sin-asignar')" class="rounded-full px-3 py-1 {{ $filtro === 'sin-asignar' ? 'bg-navy text-white' : 'border border-slate-200 bg-white text-slate-700' }}">Leads sin asignar</button>
        </div>
    @endif
    @if ($assignError)
        <p class="text-sm text-red-600">{{ $assignError }}</p>
    @endif
    <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">Nombre</th>
                    <th class="px-4 py-3">Teléfono</th>
                    <th class="px-4 py-3">Email</th>
                    <th class="px-4 py-3">Fuente</th>
                    <th class="px-4 py-3">Próxima acción</th>
                    <th class="px-4 py-3">Fecha</th>
                    <th class="px-4 py-3">Gestor</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($people as $person)
                    <tr class="{{ $person->requiresResponse() ? 'bg-amber-50/60' : '' }}">
                        <td class="px-4 py-3">
                            <a class="font-medium text-teal-800 hover:underline" href="{{ route('people.show', $person) }}" wire:navigate>{{ $person->name }}</a>
                            @if ($person->requiresResponse())
                                <span class="ml-2 inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800">Por responder</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $person->phone_e164 ?: ($person->phone_raw ?: '—') }}</td>
                        <td class="px-4 py-3">{{ $person->email ?: '—' }}</td>
                        <td class="px-4 py-3">{{ $person->sourceLabel() }}</td>
                        <td class="px-4 py-3">{{ $person->next_action ?: '—' }}</td>
                        <td class="px-4 py-3">{{ $person->next_action_at?->format('d/m/Y H:i') ?: '—' }}</td>
                        <td class="px-4 py-3">
                            @if ($filtro === 'sin-asignar' && $canViewUnassigned && ! $person->owner_id)
                                <form wire:submit="assignOwner({{ $person->id }})" class="flex flex-wrap items-center gap-2">
                                    <select wire:model="assignee.{{ $person->id }}" class="rounded-lg border-slate-300 text-xs">
                                        <option value="">Elegir gestor</option>
                                        @foreach ($users as $user)
                                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                                        @endforeach
                                    </select>
                                    <button class="rounded-lg bg-navy px-2 py-1 text-xs text-white">Asignar</button>
                                </form>
                            @else
                                {{ $person->owner?->name ?: 'Sin gestor' }}
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-slate-500">No hay contactos en esta vista.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="border-t border-slate-100 px-4 py-3">{{ $people->links() }}</div>
    </div>
</div>
