<div class="space-y-4" x-data>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <select wire:model.live="pipelineId" class="rounded-lg border-slate-300 text-sm">
            @foreach ($pipelines as $item)
                <option value="{{ $item->id }}">{{ $item->name }}</option>
            @endforeach
        </select>
        <a href="{{ route('deals.create') }}" wire:navigate class="rounded-lg bg-teal-600 px-3 py-2 text-sm text-white">Nuevo deal</a>
    </div>
    @if($pipeline)
        <div class="flex gap-4 overflow-x-auto pb-4">
            @foreach ($pipeline->stages as $stage)
                <div class="w-72 shrink-0 rounded-2xl border border-slate-200 bg-slate-100/70 p-3"
                     ondragover="event.preventDefault()"
                     ondrop="event.preventDefault(); $wire.move(event.dataTransfer.getData('deal'), {{ $stage->id }})">
                    <div class="mb-3 flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-navy">{{ $stage->name }}</h3>
                        <span class="text-xs text-slate-500">{{ ($deals[$stage->id] ?? collect())->count() }}</span>
                    </div>
                    <div class="space-y-2">
                        @foreach ($deals[$stage->id] ?? [] as $deal)
                            <article draggable="true"
                                     ondragstart="event.dataTransfer.setData('deal', '{{ $deal->id }}')"
                                     class="cursor-grab rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
                                <a href="{{ route('deals.show', $deal) }}" wire:navigate class="font-medium text-slate-800 hover:text-teal-700">{{ $deal->title }}</a>
                                <div class="mt-1 text-xs text-slate-500">{{ $deal->person?->name }}</div>
                                <div class="mt-2 text-sm font-semibold text-navy">{{ $deal->amount ? '$'.number_format($deal->amount, 0) : '—' }}</div>
                                <div class="mt-1 text-[11px] uppercase tracking-wide text-slate-400">{{ $deal->offering?->name }}</div>
                            </article>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
