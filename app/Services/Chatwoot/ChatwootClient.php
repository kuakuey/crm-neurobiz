<?php

namespace App\Services\Chatwoot;

use App\Models\Integration;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatwootClient
{
    public function __construct(private ?Integration $integration = null)
    {
        $this->integration ??= Integration::active('chatwoot');
    }

    public function configured(): bool
    {
        return $this->integration !== null
            && filled($this->integration->base_url)
            && filled($this->integration->credential('access_token'))
            && filled($this->integration->credential('account_id'));
    }

    public function accountId(): int
    {
        return (int) $this->integration?->credential('account_id');
    }

    public function origin(): string
    {
        return rtrim((string) ($this->integration?->credential('dashboard_origin') ?: $this->integration?->base_url), '/');
    }

    public function webhookSecret(): ?string
    {
        return $this->integration?->credential('webhook_secret');
    }

    public function findContact(int $id): ?array
    {
        $response = $this->http()->get($this->url("contacts/{$id}"));

        return $response->successful() ? $response->json() : null;
    }

    public function searchContacts(string $query): array
    {
        $response = $this->http()->get($this->url('contacts/search'), ['q' => $query]);

        return $response->json('payload') ?? [];
    }

    public function createContact(array $data): ?array
    {
        $response = $this->http()->post($this->url('contacts'), $data);

        if (! $response->successful()) {
            Log::warning('Chatwoot create contact failed', ['body' => $response->body()]);

            return null;
        }

        return $response->json('payload') ?? $response->json();
    }

    public function updateContact(int $id, array $data): ?array
    {
        $response = $this->http()->patch($this->url("contacts/{$id}"), $data);

        return $response->successful() ? ($response->json('payload') ?? $response->json()) : null;
    }

    public function updateConversationCustomAttributes(int $conversationId, array $attributes): void
    {
        $this->http()->post($this->url("conversations/{$conversationId}/custom_attributes"), [
            'custom_attributes' => $attributes,
            'merge' => true,
        ]);
    }

    public function addPrivateNote(int $conversationId, string $content): void
    {
        $this->http()->post($this->url("conversations/{$conversationId}/messages"), [
            'content' => $content,
            'private' => true,
            'message_type' => 'outgoing',
        ]);
    }

    private function http(): PendingRequest
    {
        return Http::timeout(20)
            ->acceptJson()
            ->withHeaders([
                'api_access_token' => $this->integration?->credential('access_token'),
            ]);
    }

    private function url(string $path): string
    {
        $base = rtrim((string) $this->integration?->base_url, '/');

        return $base.'/api/v1/accounts/'.$this->accountId().'/'.ltrim($path, '/');
    }
}
