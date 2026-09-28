<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Deal;
use App\Models\Person;
use App\Models\User;
use App\Services\DealService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CrmCoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_comercial_can_create_person_deal_and_task(): void
    {
        Queue::fake();
        $this->seedCatalog();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('people.create'))
            ->assertOk();

        $person = Person::factory()->create([
            'owner_id' => $user->id,
            'phone_raw' => '0980918462',
            'name' => 'María Pérez',
        ]);

        $this->assertSame('+593980918462', $person->fresh()->phone_e164);

        $deal = app(DealService::class)->create([
            'person_id' => $person->id,
            'pipeline_id' => \App\Models\Pipeline::query()->where('slug', 'neurobusiness-b2b')->value('id'),
            'owner_id' => $user->id,
            'title' => 'Diagnóstico · María Pérez',
            'source' => 'manual',
        ]);

        Activity::query()->create([
            'person_id' => $person->id,
            'deal_id' => $deal->id,
            'user_id' => $user->id,
            'type' => 'task',
            'title' => 'Llamar mañana',
            'due_at' => now()->addDay(),
        ]);

        $this->actingAs($user)->get(route('deals.index'))->assertOk()->assertSee('Diagnóstico · María Pérez');
        $this->assertDatabaseHas('activities', ['title' => 'Llamar mañana']);
        $this->assertSame('open', $deal->fresh()->status);
    }
}
