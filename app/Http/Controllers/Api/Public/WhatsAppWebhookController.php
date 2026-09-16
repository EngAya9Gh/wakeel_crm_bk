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
        
        if ($event === 'client.sync') {
            try {
                $phone = $data['phone'] ?? null;
                $name = $data['name'] ?? 'WhatsApp Lead';
                if ($phone) {
                    $phone = preg_replace('/[^0-9]/', '', $phone);
                    $existingClient = \App\Models\Client::where('tenant_id', $tenant->id)->where('phone', $phone)->first();
                    if (!$existingClient) {
                        $source = \App\Models\Source::where('name', 'واتساب')->first();
                        $defaultStatus = \App\Models\ClientStatus::where('is_default', true)->first();
                        \App\Models\Client::create([
                            'tenant_id' => $tenant->id,
                            'name' => $name,
                            'phone' => $phone,
                            'status_id' => $defaultStatus ? $defaultStatus->id : 1,
                            'source_id' => $source ? $source->id : null,
                            'priority' => 'medium',
                        ]);
                    }
                }
            } catch (\Exception $e) {
                Log::error('WhatsApp Webhook client.sync error: ' . $e->getMessage());
                return response()->json(['success' => false, 'message' => $e->getMessage(), 'trace' => $e->getTraceAsString()], 500);
            }
        }
        
        // Depending on event type (e.g., 'message.incoming' or 'message.status') 
        // we can dispatch jobs or process it directly.
        // TODO: Add further event processing logic here when needed.

        return response()->json(['success' => true]);
    }
}
