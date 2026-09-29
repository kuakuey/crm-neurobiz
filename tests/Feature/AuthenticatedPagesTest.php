<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticatedPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_core_pages_render_for_admin(): void
    {
        $this->seedCatalog();
        $user = User::factory()->create(['role' => \App\Enums\Role::Admin]);

        $this->actingAs($user);

        $this->get('/dashboard')->assertOk()->assertSee('Leads esta semana')->assertSee('aria-label="Menú"', false)->assertSee('Cerrar menú');
        $this->get('/people')->assertOk()->assertSee('Nueva persona');
        $this->get('/people/create')->assertOk()->assertSee('Nombre');
        $this->get('/organizations')->assertOk()->assertSee('Nueva empresa');
        $this->get('/deals')->assertOk()->assertSee('NeuroBusiness B2B');
        $this->get('/deals/create')->assertOk()->assertSee('Nuevo deal');
        $this->get('/activities')->assertOk()->assertSee('Pendientes');
        $this->get('/reports')->assertOk()->assertSee('B2B abiertos');
        $this->get('/settings/integrations')->assertOk()->assertSee('Chatwoot');
        $this->get('/settings/users')->assertOk()->assertSee('Nuevo usuario');
        $this->get('/embed/chatwoot')->assertOk()->assertSee('Ficha CRM');
    }
}
