<div class="mx-auto max-w-2xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <form wire:submit="save" class="space-y-4">
        <div>
            <label class="mb-1 block text-sm font-medium">Nombre</label>
            <input type="text" wire:model="name" class="w-full rounded-lg border-slate-300 text-sm">
            @error('name') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-medium">Teléfono</label>
                <input type="text" wire:model="phone_raw" placeholder="09xxxxxxxx o +593..." class="w-full rounded-lg border-slate-300 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Email</label>
                <input type="email" wire:model="email" class="w-full rounded-lg border-slate-300 text-sm">
            </div>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-medium">Origen</label>
                <select wire:model="source" class="w-full rounded-lg border-slate-300 text-sm">
                    @foreach (['manual'=>'Manual','whatsapp'=>'WhatsApp','web'=>'Web','diagnostico_gratuito'=>'Diagnóstico gratuito','chatwoot'=>'Chatwoot','referral'=>'Referido'] as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Owner</label>
                <select wire:model="owner_id" class="w-full rounded-lg border-slate-300 text-sm">
                    <option value="">—</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-medium">Empresa</label>
                <select wire:model="organization_id" class="w-full rounded-lg border-slate-300 text-sm">
                    <option value="">—</option>
                    @foreach ($organizations as $org)
                        <option value="{{ $org->id }}">{{ $org->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Rol en la empresa</label>
                <select wire:model="org_role" class="w-full rounded-lg border-slate-300 text-sm">
                    <option value="decisor">Decisor</option>
                    <option value="rrhh">RRHH</option>
                    <option value="sponsor">Sponsor</option>
                    <option value="otro">Otro</option>
                </select>
            </div>
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium">Notas</label>
            <textarea wire:model="notes" rows="4" class="w-full rounded-lg border-slate-300 text-sm"></textarea>
        </div>
        <button class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white">Guardar</button>
    </form>
</div>
