<div class="mx-auto max-w-2xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <form wire:submit="save" class="space-y-4">
        <div>
            <label class="mb-1 block text-sm font-medium">Título</label>
            <input type="text" wire:model="title" class="w-full rounded-lg border-slate-300 text-sm">
            @error('title') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-medium">Persona</label>
                <select wire:model="person_id" class="w-full rounded-lg border-slate-300 text-sm">
                    <option value="">—</option>
                    @foreach ($people as $person)
                        <option value="{{ $person->id }}">{{ $person->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Empresa</label>
                <select wire:model="organization_id" class="w-full rounded-lg border-slate-300 text-sm">
                    <option value="">—</option>
                    @foreach ($organizations as $organization)
                        <option value="{{ $organization->id }}">{{ $organization->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-medium">Oferta</label>
                <select wire:model.live="offering_id" class="w-full rounded-lg border-slate-300 text-sm">
                    <option value="">—</option>
                    @foreach ($offerings as $offering)
                        <option value="{{ $offering->id }}">{{ $offering->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Monto</label>
                <input type="number" step="0.01" wire:model="amount" class="w-full rounded-lg border-slate-300 text-sm">
            </div>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-medium">Pipeline</label>
                <select wire:model.live="pipeline_id" class="w-full rounded-lg border-slate-300 text-sm">
                    @foreach ($pipelines as $pipeline)
                        <option value="{{ $pipeline->id }}">{{ $pipeline->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Etapa</label>
                <select wire:model="stage_id" class="w-full rounded-lg border-slate-300 text-sm">
                    @foreach ($stages as $stage)
                        <option value="{{ $stage->id }}">{{ $stage->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-medium">Owner</label>
                <select wire:model="owner_id" class="w-full rounded-lg border-slate-300 text-sm">
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Cierre estimado</label>
                <input type="date" wire:model="close_date" class="w-full rounded-lg border-slate-300 text-sm">
            </div>
        </div>
        <button class="rounded-lg bg-teal-600 px-4 py-2 text-sm text-white">Crear deal</button>
    </form>
</div>
