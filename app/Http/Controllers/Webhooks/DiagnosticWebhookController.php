<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Integration;
use App\Services\DiagnosticIngestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DiagnosticWebhookController extends Controller
{
    public function __invoke(Request $request, DiagnosticIngestionService $ingestion): JsonResponse
    {
        $raw = $request->getContent();
        $integration = Integration::ofType('n8n');
        $secret = (string) ($integration?->credential('hmac_secret') ?? $integration?->credential('diagnostic_secret') ?? '');
        $header = (string) $request->header('X-CRM-Signature', $request->header('X-Diagnostic-Signature', ''));
        $ok = $secret === '' || $this->valid($raw, $secret, $header);

        if (! $ok) {
            $ingestion->ingest($request->all(), false, sha1($raw));

            return response()->json(['ok' => false, 'error' => 'invalid_signature'], 401);
        }

        $result = $ingestion->ingest($request->all(), true, sha1($raw));

        return response()->json($result, ($result['ok'] ?? false) ? 200 : 422);
    }

    private function valid(string $raw, string $secret, string $header): bool
    {
        if ($header === '') {
            return false;
        }

        $expected = hash_hmac('sha256', $raw, $secret);

        return hash_equals($expected, $header) || hash_equals('sha256='.$expected, $header);
    }
}
