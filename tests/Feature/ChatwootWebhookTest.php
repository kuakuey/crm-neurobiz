<?php

namespace Tests\Feature;

use App\Models\InboundWebhookLog;
use App\Models\Integration;
use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ChatwootWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        $this->seedCatalog();
        User::factory()->create();
    }

    public function test_conversation_creates_person_and_deal(): void
    {
        $payload = $this->conversationPayload();

        $this->postJson('/webhooks/chatwoot', $payload)->assertOk();

        $person = Person::query()->where('phone_e164', '+593980918462')->first();
        $this->assertNotNull($person);
        $this->assertSame('Ana Lead', $person->name);
        $this->assertTrue($person->deals()->where('status', 'open')->exists());
        $this->assertDatabaseHas('inbound_webhook_logs', [
            'provider' => 'chatwoot',
            'processed' => true,
        ]);
    }

    public function test_duplicate_payload_is_idempotent(): void
    {
        $payload = $this->conversationPayload();
        $this->postJson('/webhooks/chatwoot', $payload)->assertOk();
        $this->postJson('/webhooks/chatwoot', $payload)->assertOk();

        $this->assertSame(1, Person::query()->count());
        $this->assertSame(1, InboundWebhookLog::query()->count());
        $this->assertTrue(InboundWebhookLog::query()->where('skip_reason', 'duplicate')->exists());
    }

    public function test_crm_echo_is_skipped(): void
    {
        $payload = $this->conversationPayload();
        $payload['conversation']['custom_attributes'] = [
            'crm_sync_source' => 'crm',
            'crm_synced_at' => now()->toIso8601String(),
        ];

        $this->postJson('/webhooks/chatwoot', $payload)->assertOk();

        $this->assertDatabaseHas('inbound_webhook_logs', [
            'skip_reason' => 'crm_echo',
        ]);
        $this->assertSame(0, Person::query()->count());
    }

    public function test_invalid_signature_is_rejected_when_secret_configured(): void
    {
        Integration::query()->create([
            'type' => 'chatwoot',
            'name' => 'Chatwoot',
            'base_url' => 'https://chatwoot.test',
            'is_active' => true,
            'credentials' => [
                'account_id' => '1',
                'access_token' => 'token',
                'webhook_secret' => 'super-secret',
            ],
        ]);

        $this->postJson('/webhooks/chatwoot', $this->conversationPayload(), [
            'X-Chatwoot-Signature' => 'invalid',
        ])->assertUnauthorized();
    }

    private function conversationPayload(): array
    {
        return [
            'event' => 'conversation_created',
            'contact' => [
                'id' => 88,
                'name' => 'Ana Lead',
                'email' => 'ana@empresa.com',
                'phone_number' => '0980918462',
                'identifier' => 'wa-ana',
            ],
            'conversation' => [
                'id' => 501,
                'display_id' => 12,
                'channel' => 'Channel::Whatsapp',
            ],
        ];
    }
}
