<div class="space-y-6">
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['Leads semana', $leadsWeek],
            ['B2B abiertos / ganados', $b2bOpen.' / '.$b2bWon],
            ['B2C abiertos / ganados', $b2cOpen.' / '.$b2cWon],
            ['Retainers activos', $retainers->count()],
        ] as [$label, $value])
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="text-xs uppercase text-slate-500">{{ $label }}</div>
                <div class="mt-2 text-2xl font-semibold text-navy">{{ $value }}</div>
            </div>
        @endforeach
    </div>
    <div class="grid gap-6 lg:grid-cols-2">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="mb-3 font-semibold">Conversión por oferta</h3>
            <table class="w-full text-sm">
                <thead class="text-left text-xs uppercase text-slate-500"><tr><th class="py-2">Oferta</th><th>Total</th><th>Ganados</th></tr></thead>
                <tbody>
                    @foreach ($byOffering as $row)
                        <tr class="border-t">
                            <td class="py-2">{{ $row->name ?: 'Sin oferta' }}</td>
                            <td>{{ $row->total }}</td>
                            <td>{{ $row->won }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="mb-3 font-semibold">Deals por coach / comercial</h3>
            <ul class="space-y-2 text-sm">
                @foreach ($byOwner as $row)
                    <li>{{ $row->name ?: 'Sin owner' }} · {{ $row->open_count }} abiertos / {{ $row->total }} total</li>
                @endforeach
            </ul>
        </section>
    </div>
    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <h3 class="mb-3 font-semibold">Estancados +7 días</h3>
        <ul class="space-y-2 text-sm">
            @forelse ($staleDeals as $deal)
                <li><a class="text-teal-800 hover:underline" href="{{ route('deals.show', $deal) }}" wire:navigate>{{ $deal->title }}</a> · {{ $deal->stage?->name }} · {{ $deal->owner?->name }}</li>
            @empty
                <li class="text-slate-500">Ningún deal estancado.</li>
            @endforelse
        </ul>
    </section>
</div>
