<div class="space-y-6">
    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex justify-between">
            <div>
                <h2 class="text-xl font-semibold text-navy">{{ $organization->name }}</h2>
                <p class="text-sm text-slate-500">{{ $organization->industry }} · {{ $organization->city }} · {{ $organization->size }}</p>
            </div>
            <a href="{{ route('organizations.edit', $organization) }}" class="text-sm text-teal-700" wire:navigate>Editar</a>
        </div>
        @if($organization->notes)
            <p class="mt-4 text-sm">{{ $organization->notes }}</p>
        @endif
    </section>
    <div class="grid gap-6 lg:grid-cols-2">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="mb-3 font-semibold">Personas</h3>
            <ul class="space-y-2 text-sm">
                @foreach ($organization->people as $person)
                    <li><a class="text-teal-800 hover:underline" href="{{ route('people.show', $person) }}" wire:navigate>{{ $person->name }}</a> · {{ $person->pivot->role }}</li>
                @endforeach
            </ul>
        </section>
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="mb-3 font-semibold">Deals</h3>
            <ul class="space-y-2 text-sm">
                @foreach ($organization->deals as $deal)
                    <li><a class="text-teal-800 hover:underline" href="{{ route('deals.show', $deal) }}" wire:navigate>{{ $deal->title }}</a> · {{ $deal->stage?->name }}</li>
                @endforeach
            </ul>
        </section>
    </div>
</div>
