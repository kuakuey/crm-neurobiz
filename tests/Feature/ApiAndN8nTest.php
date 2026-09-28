<?php

namespace Tests\Feature;

use App\Models\Deal;
use App\Models\Person;
use App\Models\Pipeline;
use App\Models\User;
use App\Services\DealService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiAndN8nTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        $this->seedCatalog();
    }

    public function test_n8n_can_upsert_person_and_change_stage(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/people', [
            'name' => 'Carlos',
            'phone' => '0991234567',
            'source' => 'n8n',
        ])->assertCreated();

        $person = Person::query()->where('phone_e164', '+593991234567')->firstOrFail();
        $deal = app(DealService::class)->create([
            'person_id' => $person->id,
            'pipeline_id' => Pipeline::query()->where('slug', 'neurobusiness-b2b')->value('id'),
            'owner_id' => $user->id,
            'title' => 'Lead Carlos',
        ]);

        $this->patchJson("/api/v1/deals/{$deal->id}/stage", [
            'stage' => 'calificado',
        ])->assertOk()->assertJsonPath('data.stage.slug', 'calificado');

        $this->postJson('/api/v1/activities', [
            'person_id' => $person->id,
            'deal_id' => $deal->id,
            'title' => 'Follow-up n8n',
            'type' => 'task',
        ])->assertCreated();
    }

    public function test_changing_stage_to_won_sets_status(): void
    {
        $user = User::factory()->create();
        $person = Person::factory()->create(['owner_id' => $user->id]);
        $deal = app(DealService::class)->create([
            'person_id' => $person->id,
            'pipeline_id' => Pipeline::query()->where('slug', 'neurobusiness-b2b')->value('id'),
            'owner_id' => $user->id,
            'title' => 'Sprint',
        ]);

        app(DealService::class)->changeStage($deal, 'ganado');

        $this->assertSame('won', $deal->fresh()->status);
    }
}
