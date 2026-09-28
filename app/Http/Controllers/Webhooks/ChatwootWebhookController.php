<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Integration;
use App\Services\Chatwoot\ChatwootSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatwootWebhookController extends Controller
{
    public function __invoke(Request $request, ChatwootSyncService $sync): JsonResponse
    {
        $raw = $request->getContent();
        $integration = Integration::ofType('chatwoot');
        $secret = (string) ($integration?->credential('webhook_secret') ?? '');
        $header = (string) $request->header('X-Chatwoot-Signature', '');
        $signatureOk = $this->validSignature($raw, $secret, $header);

        if ($secret !== '' && ! $signatureOk) {
            $sync->handleWebhook($request->all(), false, sha1($raw));

            return response()->json(['ok' => false, 'error' => 'invalid_signature'], 401);
        }

        $sync->handleWebhook($request->all(), $secret === '' || $signatureOk, sha1($raw));

        return response()->json(['ok' => true]);
    }

    private function validSignature(string $raw, string $secret, string $header): bool
    {
        if ($secret === '' || $header === '') {
            return false;
        }

        $expected = hash_hmac('sha256', $raw, $secret);

        return hash_equals($expected, $header) || hash_equals('sha256='.$expected, $header);
    }
}
