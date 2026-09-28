<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Deal;
use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class DiagnosticIngestionTest extends TestCase
{
    use RefreshDatabase;

    public function test_free_diagnostic_moves_deal_to_calificado_and_schedules_call(): void
    {
        Queue::fake();
        $this->seedCatalog();
        User::factory()->create();

        $response = $this->postJson('/webhooks/diagnostico', [
            'name' => 'Empresa Andina',
            'phone' => '0987654321',
            'email' => 'ceo@andina.test',
            'risk_level' => 'alto',
            'scores' => ['liderazgo' => 2, 'ejecucion' => 1],
        ]);

        $response->assertOk()->assertJsonPath('ok', true);

        $person = Person::query()->where('email', 'ceo@andina.test')->first();
        $this->assertNotNull($person);
        $this->assertSame('diagnostico_gratuito', $person->source);

        $deal = Deal::query()->where('person_id', $person->id)->first();
        $this->assertSame('calificado', $deal->stage->slug);
        $this->assertSame('alto', $deal->diagnostic_risk_level);

        $this->assertTrue(
            Activity::query()->where('person_id', $person->id)->where('title', 'like', '%diagnóstico%')->exists()
        );
    }
}
