<div class="space-y-4">
    <div class="flex items-center justify-between">
        <input type="search" wire:model.live.debounce.300ms="q" placeholder="Buscar empresa" class="w-full max-w-md rounded-lg border-slate-300 text-sm">
        <a href="{{ route('organizations.create') }}" wire:navigate class="rounded-lg bg-navy px-3 py-2 text-sm text-white">Nueva empresa</a>
    </div>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($organizations as $organization)
            <a href="{{ route('organizations.show', $organization) }}" wire:navigate class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm hover:border-teal-300">
                <div class="font-semibold text-navy">{{ $organization->name }}</div>
                <div class="mt-1 text-sm text-slate-500">{{ $organization->industry ?: 'Sin industria' }} · {{ $organization->city }}</div>
                <div class="mt-3 text-xs text-slate-400">{{ $organization->people_count }} personas</div>
            </a>
        @empty
            <p class="text-sm text-slate-500">No hay empresas todavía.</p>
        @endforelse
    </div>
    {{ $organizations->links() }}
</div>
