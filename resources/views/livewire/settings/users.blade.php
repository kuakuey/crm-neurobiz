<div>
<div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-slate-500">{{ $users->count() }} {{ $users->count() === 1 ? 'usuario' : 'usuarios' }}</p>
        <button type="button" wire:click="openCreate" class="rounded-lg bg-navy px-3 py-2 text-sm font-medium text-white">Nuevo usuario</button>
    </div>
    <section class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">Nombre</th>
                    <th class="px-4 py-3">Correo</th>
                    <th class="px-4 py-3">Rol</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($users as $user)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $user->name }}</td>
                        <td class="px-4 py-3">{{ $user->email }}</td>
                        <td class="px-4 py-3">{{ $user->role?->label() }}</td>
                        <td class="px-4 py-3 text-right">
                            <button type="button" wire:click="edit({{ $user->id }})" class="text-sm text-teal-700">Editar</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-8 text-center text-slate-500">No hay usuarios.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>
</div>

@if ($showModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4" wire:keydown.escape.window="cancel">
        <button type="button" class="absolute inset-0 bg-slate-900/50" wire:click="cancel" aria-label="Cerrar"></button>
        <div class="relative z-10 w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl" role="dialog" aria-modal="true" aria-labelledby="user-modal-title">
            <div class="mb-4 flex items-start justify-between gap-3">
                <h2 id="user-modal-title" class="text-lg font-semibold text-navy">{{ $userId ? 'Editar usuario' : 'Nuevo usuario' }}</h2>
                <button type="button" wire:click="cancel" class="text-sm text-slate-500" aria-label="Cerrar">Cerrar</button>
            </div>
            <form wire:submit="save" class="space-y-4">
                <div>
                    <label class="mb-1 block text-sm font-medium">Nombre</label>
                    <input type="text" wire:model="name" class="w-full rounded-lg border-slate-300 text-sm" autocomplete="name">
                    @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Correo</label>
                    <input type="email" wire:model="email" class="w-full rounded-lg border-slate-300 text-sm" autocomplete="off">
                    @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Rol</label>
                    <select wire:model="role" class="w-full rounded-lg border-slate-300 text-sm">
                        @foreach ($roles as $option)
                            <option value="{{ $option->value }}">{{ $option->label() }}</option>
                        @endforeach
                    </select>
                    @error('role') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Contraseña</label>
                    <input type="password" wire:model="password" class="w-full rounded-lg border-slate-300 text-sm" autocomplete="new-password">
                    @if ($userId)
                        <p class="mt-1 text-xs text-slate-500">Déjala en blanco si no quieres cambiarla.</p>
                    @endif
                    @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Confirmar contraseña</label>
                    <input type="password" wire:model="password_confirmation" class="w-full rounded-lg border-slate-300 text-sm" autocomplete="new-password">
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" wire:click="cancel" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">Cancelar</button>
                    <button class="rounded-lg bg-navy px-3 py-2 text-sm font-medium text-white">Guardar</button>
                </div>
            </form>
        </div>
    </div>
@endif
</div>
