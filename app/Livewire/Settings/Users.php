<?php

namespace App\Livewire\Settings;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Usuarios')]
class Users extends Component
{
    public ?int $userId = null;

    public string $name = '';

    public string $email = '';

    public string $role = 'comercial';

    public string $password = '';

    public string $password_confirmation = '';

    public bool $showModal = false;

    public function mount(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
    }

    public function openCreate(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $this->resetForm();
        $this->showModal = true;
    }

    public function edit(int $userId): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $user = User::query()->findOrFail($userId);
        $this->userId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role = $user->role?->value ?? Role::Comercial->value;
        $this->password = '';
        $this->password_confirmation = '';
        $this->resetValidation();
        $this->showModal = true;
    }

    public function cancel(): void
    {
        $this->resetForm();
    }

    public function save(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->userId)],
            'role' => ['required', Rule::enum(Role::class)],
            'password' => [$this->userId ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
        ]);

        $role = Role::from($data['role']);

        if ($this->userId) {
            $user = User::query()->findOrFail($this->userId);
            if ($this->removesLastAdmin($user, $role)) {
                $this->addError('role', 'Debe quedar al menos un usuario de Administración.');

                return;
            }

            $user->name = $data['name'];
            $user->email = $data['email'];
            $user->role = $role;
            if ($this->password !== '') {
                $user->password = $this->password;
            }
            $user->save();
            session()->flash('status', 'Usuario actualizado.');
        } else {
            User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'role' => $role,
                'password' => $this->password,
                'email_verified_at' => now(),
            ]);
            session()->flash('status', 'Usuario creado.');
        }

        $this->resetForm();
    }

    public function render()
    {
        return view('livewire.settings.users', [
            'users' => User::query()->orderBy('name')->get(),
            'roles' => Role::cases(),
        ]);
    }

    private function removesLastAdmin(User $user, Role $newRole): bool
    {
        if ($user->role !== Role::Admin || $newRole === Role::Admin) {
            return false;
        }

        return User::query()->where('role', Role::Admin)->count() <= 1;
    }

    private function resetForm(): void
    {
        $this->userId = null;
        $this->name = '';
        $this->email = '';
        $this->role = Role::Comercial->value;
        $this->password = '';
        $this->password_confirmation = '';
        $this->showModal = false;
        $this->resetValidation();
    }
}
