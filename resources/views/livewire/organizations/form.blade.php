<div class="mx-auto max-w-xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <form wire:submit="save" class="space-y-4">
        <div>
            <label class="mb-1 block text-sm font-medium">Nombre</label>
            <input type="text" wire:model="name" class="w-full rounded-lg border-slate-300 text-sm">
            @error('name') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-medium">Industria</label>
                <input type="text" wire:model="industry" class="w-full rounded-lg border-slate-300 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Tamaño</label>
                <input type="text" wire:model="size" placeholder="Ej. 20-50" class="w-full rounded-lg border-slate-300 text-sm">
            </div>
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium">Ciudad</label>
            <input type="text" wire:model="city" class="w-full rounded-lg border-slate-300 text-sm">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium">Notas</label>
            <textarea wire:model="notes" rows="4" class="w-full rounded-lg border-slate-300 text-sm"></textarea>
        </div>
        <button class="rounded-lg bg-teal-600 px-4 py-2 text-sm text-white">Guardar</button>
    </form>
</div>
