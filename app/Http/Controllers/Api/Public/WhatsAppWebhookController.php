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
        TenantContext::set($tenant);

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
        
        if ($event === 'ticket.sync') {
            try {
                $phone = $data['phone'] ?? null;
                $categoryName = $data['category_name'] ?? 'دعم فني';
                $providerThreadId = $data['thread_id'] ?? null;
                $status = $data['status'] ?? 'open'; // open, resolved, closed

                if ($phone && $providerThreadId) {
                    $phone = preg_replace('/[^0-9]/', '', $phone);
                    
                    // 1. Find or Create Client
                    $client = \App\Models\Client::where('tenant_id', $tenant->id)->where('phone', $phone)->first();
                    if (!$client) {
                        $source = \App\Models\Source::where('name', 'واتساب')->first();
                        $client = \App\Models\Client::create([
                            'tenant_id' => $tenant->id,
                            'name' => $data['name'] ?? 'WhatsApp Lead',
                            'phone' => $phone,
                            'source_id' => $source ? $source->id : null,
                        ]);
                    }

                    // 2. Find or Create Category (The Magic Link!)
                    $category = \App\Models\TicketCategory::firstOrCreate(
                        ['tenant_id' => $tenant->id, 'name' => $categoryName],
                        ['color' => '#6B7280', 'is_active' => true] // لون رمادي افتراضي
                    );

                    // 3. Find or Create Ticket by provider_thread_id
                    $ticket = \App\Models\Ticket::where('tenant_id', $tenant->id)
                        ->whereJsonContains('metadata->provider_thread_id', $providerThreadId)
                        ->first();

                    if ($ticket) {
                        // Update existing ticket
                        $updateData = ['status' => $status, 'category_id' => $category->id];
                        if ($status === 'resolved' && $ticket->status !== 'resolved') $updateData['resolved_at'] = now();
                        if ($status === 'closed' && $ticket->status !== 'closed') $updateData['closed_at'] = now();
                        $ticket->update($updateData);
                    } else {
                        // Create new ticket
                        $lastTicket = \App\Models\Ticket::where('tenant_id', $tenant->id)->latest('id')->first();
                        $ticketNumber = 'TKT-' . str_pad((string)(($lastTicket ? $lastTicket->id : 0) + 1), 6, '0', STR_PAD_LEFT);

                        \App\Models\Ticket::create([
                            'tenant_id' => $tenant->id,
                            'ticket_number' => $ticketNumber,
                            'client_id' => $client->id,
                            'category_id' => $category->id,
                            'title' => 'محادثة واتساب - ' . $client->name,
                            'status' => $status,
                            'source' => 'whatsapp',
                            'metadata' => ['provider_thread_id' => $providerThreadId]
                        ]);
                    }
                }
            } catch (\Exception $e) {
                Log::error('WhatsApp Webhook ticket.sync error: ' . $e->getMessage());
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }
        }
        
        // Depending on event type (e.g., 'message.incoming' or 'message.status') 
        // we can dispatch jobs or process it directly.
        // TODO: Add further event processing logic here when needed.

        return response()->json(['success' => true]);
    }
}
