<div class="space-y-4">
    <div class="flex gap-2">
        @foreach (['pending'=>'Pendientes','today'=>'Hoy','overdue'=>'Vencidas','done'=>'Hechas'] as $key => $label)
            <button wire:click="$set('filter', '{{ $key }}')" class="rounded-full px-3 py-1 text-sm {{ $filter === $key ? 'bg-navy text-white' : 'bg-white text-slate-600 border' }}">{{ $label }}</button>
        @endforeach
    </div>
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <ul class="divide-y divide-slate-100">
            @forelse ($activities as $activity)
                <li class="flex items-center justify-between px-4 py-3">
                    <div>
                        <div class="font-medium">{{ $activity->title }}</div>
                        <div class="text-xs text-slate-500">{{ $activity->person?->name }} · {{ $activity->type }} · {{ optional($activity->due_at)->format('d/m H:i') }}</div>
                    </div>
                    @unless($activity->is_done)
                        <button wire:click="complete({{ $activity->id }})" class="text-sm text-teal-700">Completar</button>
                    @endunless
                </li>
            @empty
                <li class="px-4 py-8 text-center text-slate-500">No hay actividades en este filtro.</li>
            @endforelse
        </ul>
        <div class="px-4 py-3">{{ $activities->links() }}</div>
    </div>
</div>
