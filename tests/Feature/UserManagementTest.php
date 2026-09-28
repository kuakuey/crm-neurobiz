<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Livewire\Settings\Users;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_edit_password_and_role(): void
    {
        $admin = User::factory()->create([
            'role' => Role::Admin,
            'email' => 'admin@example.test',
        ]);

        $this->actingAs($admin)
            ->get(route('settings.users'))
            ->assertOk()
            ->assertSee('Nuevo usuario')
            ->assertSee('admin@example.test')
            ->assertSee('Usuarios')
            ->assertDontSee('Confirmar contraseña');

        Livewire::actingAs($admin)
            ->test(Users::class)
            ->call('openCreate')
            ->assertSet('showModal', true)
            ->assertSee('Confirmar contraseña')
            ->set('name', 'Laura Gómez')
            ->set('email', 'laura@example.test')
            ->set('role', Role::Comercial->value)
            ->set('password', 'clave-segura')
            ->set('password_confirmation', 'clave-segura')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showModal', false);

        $laura = User::query()->where('email', 'laura@example.test')->firstOrFail();
        $this->assertSame(Role::Comercial, $laura->role);
        $this->assertTrue(Hash::check('clave-segura', $laura->password));

        Livewire::actingAs($admin)
            ->test(Users::class)
            ->call('edit', $laura->id)
            ->set('role', Role::Coach->value)
            ->set('password', 'otra-clave-9')
            ->set('password_confirmation', 'otra-clave-9')
            ->call('save')
            ->assertHasNoErrors();

        $laura->refresh();
        $this->assertSame(Role::Coach, $laura->role);
        $this->assertTrue(Hash::check('otra-clave-9', $laura->password));
        $this->assertTrue(Auth::attempt(['email' => 'laura@example.test', 'password' => 'otra-clave-9']));
    }

    public function test_blank_password_keeps_the_current_one(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin]);
        $user = User::factory()->create([
            'role' => Role::Comercial,
            'password' => 'original-pass',
        ]);

        Livewire::actingAs($admin)
            ->test(Users::class)
            ->call('edit', $user->id)
            ->set('name', 'Nombre nuevo')
            ->set('password', '')
            ->set('password_confirmation', '')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Nombre nuevo', $user->fresh()->name);
        $this->assertTrue(Hash::check('original-pass', $user->fresh()->password));
    }

    public function test_non_admin_cannot_open_the_page(): void
    {
        $comercial = User::factory()->create(['role' => Role::Comercial]);

        $this->actingAs($comercial)
            ->get(route('settings.users'))
            ->assertForbidden();

        $this->actingAs($comercial)->get('/dashboard')->assertOk()->assertDontSee(route('settings.users'), false);
    }

    public function test_last_admin_role_cannot_be_removed(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin, 'name' => 'Único admin']);

        Livewire::actingAs($admin)
            ->test(Users::class)
            ->call('edit', $admin->id)
            ->set('role', Role::Comercial->value)
            ->call('save')
            ->assertHasErrors('role');

        $this->assertSame(Role::Admin, $admin->fresh()->role);
    }
}
