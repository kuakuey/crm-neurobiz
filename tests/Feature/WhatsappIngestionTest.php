<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Livewire\People\Index;
use App\Models\Activity;
use App\Models\Channel;
use App\Models\Organization;
use App\Models\Person;
use App\Models\User;
use App\Services\WhatsappIngestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;
use Tests\TestCase;

class WhatsappIngestionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        $this->seedCatalog();
    }

    public function test_new_number_creates_visible_unassigned_whatsapp_contact(): void
    {
        $tokenUser = User::factory()->create();
        Sanctum::actingAs($tokenUser);

        $this->postJson('/api/v1/whatsapp/messages', [
            'phone' => '+593 99 123 4567',
            'first_name' => 'Ana Ruiz',
            'result' => 'Hola, quiero información',
            'external_ref' => 'chatwoot:msg:1001',
        ])->assertCreated()
            ->assertJsonPath('data.created_contact', true)
            ->assertJsonPath('data.created_activity', true)
            ->assertJsonPath('data.contact.owner_user_id', null)
            ->assertJsonPath('data.contact.source', 'WhatsApp')
            ->assertJsonPath('data.contact.next_action', 'Responder mensaje entrante')
            ->assertJsonPath('data.contact.phone_normalized', '593991234567')
            ->assertJsonPath('data.activity.owner_user_id', null)
            ->assertJsonPath('data.activity.activity_type', 'WhatsApp')
            ->assertJsonPath('data.activity.result', 'Hola, quiero información')
            ->assertJsonPath('data.activity.completed', false)
            ->assertJsonPath('data.activity.external_ref', 'chatwoot:msg:1001');

        $person = Person::query()->where('phone_normalized', '593991234567')->firstOrFail();
        $this->assertNull($person->owner_id);
        $this->assertSame('WhatsApp', $person->leadSource?->name);
        $this->assertNotSame($tokenUser->id, $person->owner_id);

        $comercial = User::factory()->create();
        $this->actingAs($comercial)
            ->get(route('contactos.index'))
            ->assertOk()
            ->assertSee('Ana Ruiz')
            ->assertSee('WhatsApp')
            ->assertSee('Responder mensaje entrante')
            ->assertSee('Sin gestor')
            ->assertSee('Por responder')
            ->assertDontSee('Leads sin asignar');
    }

    public function test_same_number_in_other_formats_does_not_duplicate_the_contact(): void
    {
        $service = app(WhatsappIngestionService::class);

        $first = $service->ingest([
            'phone' => '+593 99 123 4567',
            'first_name' => 'Ana Ruiz',
            'result' => 'Primer mensaje',
            'external_ref' => 'chatwoot:msg:2001',
        ]);

        $second = $service->ingest([
            'phone' => '0991234567',
            'first_name' => 'Ana Ruiz',
            'result' => 'Segundo mensaje',
            'external_ref' => 'chatwoot:msg:2002',
        ]);

        $this->assertFalse($second['created_contact']);
        $this->assertTrue($second['created_activity']);
        $this->assertSame($first['person']->id, $second['person']->id);
        $this->assertSame(1, Person::query()->where('phone_normalized', '593991234567')->count());
        $this->assertSame(2, Activity::query()->where('person_id', $first['person']->id)->count());

        $matches = $service->findDuplicates('593991234567', null, 'Otra persona', null);
        $this->assertCount(1, $matches);
        $this->assertSame($first['person']->id, $matches->first()->id);
    }

    public function test_repeated_external_ref_does_not_duplicate_the_activity(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/whatsapp/messages', [
            'phone' => '0991234567',
            'first_name' => 'Ana Ruiz',
            'result' => 'Mensaje original',
            'external_ref' => 'chatwoot:msg:3001',
        ])->assertCreated();

        $this->postJson('/api/v1/whatsapp/messages', [
            'phone' => '+593 99 123 4567',
            'first_name' => 'Ana Ruiz',
            'result' => 'Mensaje original',
            'external_ref' => 'chatwoot:msg:3001',
        ])->assertOk()
            ->assertJsonPath('data.created_contact', false)
            ->assertJsonPath('data.created_activity', false);

        $this->assertSame(1, Person::query()->where('phone_normalized', '593991234567')->count());
        $this->assertSame(1, Activity::query()->where('external_ref', 'chatwoot:msg:3001')->count());
    }

    public function test_messages_without_external_ref_can_repeat_for_the_same_contact(): void
    {
        $service = app(WhatsappIngestionService::class);

        $service->ingest([
            'phone' => '0981000001',
            'first_name' => 'Luis',
            'result' => 'Uno',
        ]);
        $service->ingest([
            'phone' => '593981000001',
            'first_name' => 'Luis',
            'result' => 'Dos',
        ]);

        $person = Person::query()->where('phone_normalized', '593981000001')->firstOrFail();
        $this->assertSame(2, $person->activities()->count());
        $this->assertSame(2, Activity::query()->where('person_id', $person->id)->whereNull('external_ref')->count());
    }

    public function test_unassigned_filter_is_visible_to_commercial_director_and_can_assign(): void
    {
        $service = app(WhatsappIngestionService::class);
        $result = $service->ingest([
            'phone' => '0998887766',
            'first_name' => 'Lead libre',
            'result' => 'Necesito que me llamen',
            'external_ref' => 'chatwoot:msg:4001',
        ]);

        $comercial = User::factory()->create(['role' => Role::Comercial]);
        $director = User::factory()->create(['role' => Role::Direccion, 'name' => 'Directora']);
        $gestor = User::factory()->create(['role' => Role::Comercial, 'name' => 'Gestor Norte']);

        $this->actingAs($comercial)
            ->get(route('contactos.index', ['filtro' => 'sin-asignar']))
            ->assertForbidden();

        $this->actingAs($director)
            ->get(route('contactos.index', ['filtro' => 'sin-asignar']))
            ->assertOk()
            ->assertSee('Leads sin asignar')
            ->assertSee('Lead libre')
            ->assertSee('Asignar');

        Livewire::actingAs($director)
            ->test(Index::class)
            ->set('filtro', 'sin-asignar')
            ->set('assignee', [$result['person']->id => $gestor->id])
            ->call('assignOwner', $result['person']->id)
            ->assertHasNoErrors();

        $this->assertSame($gestor->id, $result['person']->fresh()->owner_id);

        $this->actingAs($director)
            ->get(route('contactos.index', ['filtro' => 'sin-asignar']))
            ->assertOk()
            ->assertDontSee('Lead libre');
    }

    public function test_contact_timeline_shows_whatsapp_message_and_manual_opportunity_link(): void
    {
        $gestor = User::factory()->create();
        $result = app(WhatsappIngestionService::class)->ingest([
            'phone' => '0991234567',
            'first_name' => 'Ana Ruiz',
            'result' => 'Hola, quiero información',
            'external_ref' => 'chatwoot:msg:5001',
        ]);

        $this->actingAs($gestor)
            ->get(route('people.show', $result['person']))
            ->assertOk()
            ->assertSee('WhatsApp')
            ->assertSee('Hola, quiero información')
            ->assertSee('Crear oportunidad')
            ->assertSee(route('deals.create', ['person_id' => $result['person']->id]), false);

        $this->actingAs($gestor)
            ->get(route('deals.create', ['person_id' => $result['person']->id]))
            ->assertOk()
            ->assertSee('Ana Ruiz');
    }

    public function test_response_indicator_requires_due_action_and_open_inbound_activity(): void
    {
        $channelId = Channel::query()->where('name', 'WhatsApp')->value('id');
        $viewer = User::factory()->create();

        $due = Person::factory()->create([
            'owner_id' => null,
            'name' => 'Contacto vencido',
            'next_action' => 'Responder mensaje entrante',
            'next_action_at' => now()->subHour(),
        ]);
        Activity::query()->create([
            'person_id' => $due->id,
            'channel_id' => $channelId,
            'user_id' => null,
            'type' => 'WhatsApp',
            'title' => 'Mensaje de WhatsApp',
            'body' => 'sigo aquí',
            'is_done' => false,
        ]);

        $future = Person::factory()->create([
            'owner_id' => null,
            'name' => 'Contacto futuro',
            'next_action' => 'Llamar luego',
            'next_action_at' => now()->addDays(3),
        ]);
        Activity::query()->create([
            'person_id' => $future->id,
            'channel_id' => $channelId,
            'user_id' => null,
            'type' => 'WhatsApp',
            'title' => 'Mensaje de WhatsApp',
            'body' => 'para después',
            'is_done' => false,
        ]);

        $done = Person::factory()->create([
            'owner_id' => null,
            'name' => 'Contacto atendido',
            'next_action' => 'Responder mensaje entrante',
            'next_action_at' => now()->subDay(),
        ]);
        Activity::query()->create([
            'person_id' => $done->id,
            'channel_id' => $channelId,
            'user_id' => null,
            'type' => 'WhatsApp',
            'title' => 'Mensaje de WhatsApp',
            'body' => 'ya respondido',
            'is_done' => true,
            'done_at' => now(),
        ]);

        $response = $this->actingAs($viewer)->get(route('contactos.index'));
        $response->assertOk()->assertSee('Contacto vencido')->assertSee('Contacto futuro')->assertSee('Contacto atendido');
        $this->assertSame(1, substr_count($response->getContent(), 'Por responder'));
    }

    public function test_anonymous_client_cannot_read_or_write_ingestion_data(): void
    {
        $this->get(route('contactos.index'))->assertRedirect(route('login'));
        $this->getJson('/api/v1/channels')->assertUnauthorized();
        $this->getJson('/api/v1/lead-sources')->assertUnauthorized();
        $this->postJson('/api/v1/contacts/find-duplicates', ['phone' => '0991234567'])->assertUnauthorized();
        $this->postJson('/api/v1/whatsapp/messages', [
            'phone' => '0991234567',
            'result' => 'no autorizado',
        ])->assertUnauthorized();
        $this->postJson('/api/v1/activities', ['title' => 'no'])->assertUnauthorized();
        $this->getJson('/api/v1/people/1')->assertUnauthorized();
        $this->assertSame(0, Person::query()->count());
    }

    public function test_duplicate_search_uses_email_or_name_and_company_when_phone_is_absent(): void
    {
        $service = app(WhatsappIngestionService::class);
        $org = Organization::factory()->create(['name' => 'Acme']);
        $person = Person::factory()->create([
            'name' => 'Carla Mora',
            'email' => 'carla@acme.test',
            'phone_raw' => null,
            'phone_e164' => null,
        ]);
        $person->organizations()->attach($org->id, ['role' => 'decisor']);

        $byEmail = $service->findDuplicates(null, 'Carla@acme.test', null, null);
        $this->assertCount(1, $byEmail);
        $this->assertSame($person->id, $byEmail->first()->id);

        $byCompany = $service->findDuplicates(null, null, 'Carla Mora', $org->id);
        $this->assertCount(1, $byCompany);

        $this->assertCount(0, $service->findDuplicates(null, null, 'Carla Mora', null));
    }

    public function test_channels_and_lead_sources_include_planned_catalogs(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/v1/channels?name=WhatsApp')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'WhatsApp');

        $this->getJson('/api/v1/lead-sources')
            ->assertOk()
            ->assertJsonFragment(['name' => 'WhatsApp'])
            ->assertJsonFragment(['name' => 'Instagram'])
            ->assertJsonFragment(['name' => 'Facebook'])
            ->assertJsonFragment(['name' => 'Email']);
    }
}
