<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Models\Tenant;
use App\Services\TenantContext;

class WhatsAppWebhookController extends Controller
{
    /**
     * Handle incoming webhook requests from WhatsApp Provider.
     */
    public function handle(Request $request, int $tenantId): JsonResponse
    {
        $tenant = Tenant::find($tenantId);

        if (!$tenant || !$tenant->is_active) {
            Log::warning('WhatsApp Webhook attempt for invalid or inactive tenant', ['tenant_id' => $tenantId]);
            return response()->json(['success' => false, 'message' => 'Not Found'], 404);
        }

        $secretKey = $tenant->settings['whatsapp_webhook_secret'] ?? null;
        
        // Verify Webhook Key
        $providedKey = $request->header('X-Webhook-Key');
        
        if (empty($secretKey) || !$providedKey || $providedKey !== $secretKey) {
            Log::warning('Unauthorized WhatsApp Webhook attempt', [
                'ip' => $request->ip(),
                'tenant_id' => $tenantId,
                'provided_key' => $providedKey
            ]);
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // Set Tenant Context for the remainder of the request
        TenantContext::setTenantId($tenant->id);

        // Process Event
        $event = $request->input('event');
        $data = $request->input('data');
        
        Log::info("WhatsApp Webhook Received: {$event}", ['data' => $data]);
        
        // Dispatch event if it's a message
        if ($event === 'message.incoming') {
            $payload = $data;
            $threadId = $payload['thread_id'] ?? $payload['from'] ?? null;
            
            if ($threadId) {
                // Pass the tenant ID to the event so it can be scoped properly
                \App\Events\NewWhatsAppMessageReceived::dispatch($payload, (string) $threadId, $tenant->id);
            }
        }
        
        // Depending on event type (e.g., 'message.incoming' or 'message.status') 
        // we can dispatch jobs or process it directly.
        // TODO: Add further event processing logic here when needed.

        return response()->json(['success' => true]);
    }
}
