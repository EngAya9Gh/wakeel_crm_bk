<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Services\Integrations\LeadIntegrationService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class WebhookController extends Controller
{
    public function __construct(
        private readonly LeadIntegrationService $integrationService
    ) {}

    public function verifyMeta(Request $request, string $token)
    {
        // Facebook webhook verification
        $tenant = Tenant::where('webhook_token', $token)->first();
        if (!$tenant) {
            return response()->json(['error' => 'Invalid token'], 404);
        }

        $mode = $request->query('hub_mode');
        $challenge = $request->query('hub_challenge');
        $verifyToken = (string) $request->query('hub_verify_token', '');

        if ($mode !== 'subscribe' || !$challenge) {
            return response()->json(['error' => 'Invalid verification request'], 400);
        }

        $integration = $tenant->integrations()->where('platform', 'meta')->where('is_active', true)->first();
        $credentials = $integration?->credentials ?? [];

        // The "Verify token" entered in the Meta dashboard must be one of the tenant's own secrets
        $acceptedTokens = array_filter([
            $credentials['webhook_secret'] ?? null,
            $credentials['verify_token'] ?? null,
            $tenant->webhook_token,
        ], fn ($value) => is_string($value) && $value !== '');

        foreach ($acceptedTokens as $accepted) {
            if ($verifyToken !== '' && hash_equals($accepted, $verifyToken)) {
                return response((string) $challenge, 200);
            }
        }

        return response()->json(['error' => 'Invalid verify token'], 403);
    }

    public function receiveMeta(Request $request, string $token)
    {
        $tenant = Tenant::where('webhook_token', $token)->first();
        if (!$tenant) {
            return response()->json(['error' => 'Invalid token'], 404);
        }

        // When the tenant saved its Meta app secret, only accept payloads signed by Meta
        $integration = $tenant->integrations()->where('platform', 'meta')->first();
        $appSecret = $integration?->credentials['app_secret'] ?? null;
        if (is_string($appSecret) && $appSecret !== '') {
            $expected = 'sha256=' . hash_hmac('sha256', $request->getContent(), $appSecret);
            if (!hash_equals($expected, (string) $request->header('X-Hub-Signature-256', ''))) {
                return response()->json(['error' => 'Invalid signature'], 403);
            }
        }

        $payload = $request->all();

        // Process in background queue would be ideal, but direct call for MVP
        $this->integrationService->handleMetaWebhook($tenant, $payload);

        // Always return 200 OK so Meta doesn't retry infinitely
        return response()->json(['success' => true], 200);
    }

    public function receiveTikTok(Request $request, string $token)
    {
        $tenant = Tenant::where('webhook_token', $token)->first();
        if (!$tenant) {
            return response()->json(['error' => 'Invalid token'], 404);
        }

        $payload = $request->all();

        $this->integrationService->handleTikTokWebhook($tenant, $payload);

        return response()->json(['success' => true], 200);
    }
}
