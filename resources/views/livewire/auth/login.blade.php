<div>
    <form wire:submit="authenticate" class="space-y-4">
        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Correo</label>
            <input type="email" wire:model="email" class="w-full rounded-lg border-slate-300 text-sm" required>
            @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Contraseña</label>
            <input type="password" wire:model="password" class="w-full rounded-lg border-slate-300 text-sm" required>
        </div>
        <label class="flex items-center gap-2 text-sm text-slate-600">
            <input type="checkbox" wire:model="remember" class="rounded border-slate-300 text-teal-600">
            Recordarme
        </label>
        <button class="w-full rounded-lg bg-teal-600 py-2.5 text-sm font-semibold text-white hover:bg-teal-700">Ingresar</button>
    </form>
</div>
