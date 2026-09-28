<div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <input type="search" wire:model.live.debounce.300ms="q" placeholder="Buscar por nombre, teléfono o email" class="w-full max-w-md rounded-lg border-slate-300 text-sm">
        <a href="{{ route('people.create') }}" wire:navigate class="rounded-lg bg-navy px-3 py-2 text-sm font-medium text-white">Nueva persona</a>
    </div>
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">Nombre</th>
                    <th class="px-4 py-3">Teléfono</th>
                    <th class="px-4 py-3">Email</th>
                    <th class="px-4 py-3">Origen</th>
                    <th class="px-4 py-3">Owner</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($people as $person)
                    <tr>
                        <td class="px-4 py-3"><a class="font-medium text-teal-800 hover:underline" href="{{ route('people.show', $person) }}" wire:navigate>{{ $person->name }}</a></td>
                        <td class="px-4 py-3">{{ $person->phone_e164 ?: '—' }}</td>
                        <td class="px-4 py-3">{{ $person->email ?: '—' }}</td>
                        <td class="px-4 py-3">{{ $person->source }}</td>
                        <td class="px-4 py-3">{{ $person->owner?->name ?: '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-slate-500">No hay personas. Crea un lead o espera un WhatsApp de Chatwoot.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="border-t border-slate-100 px-4 py-3">{{ $people->links() }}</div>
    </div>
</div>
