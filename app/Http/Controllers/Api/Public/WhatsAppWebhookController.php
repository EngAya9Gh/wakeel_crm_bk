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
                $client = null;
                if ($phone) {
                    $phone = preg_replace('/[^0-9]/', '', $phone);
                    $client = \App\Models\Client::where('tenant_id', $tenant->id)->where('phone', $phone)->first();
                    if (!$client) {
                        $source = \App\Models\Source::where('name', 'واتساب')->first();
                        $defaultStatus = \App\Models\ClientStatus::where('is_default', true)->first();
                        $client = \App\Models\Client::create([
                            'tenant_id' => $tenant->id,
                            'name' => $name,
                            'phone' => $phone,
                            'status_id' => $defaultStatus ? $defaultStatus->id : 1,
                            'source_id' => $source ? $source->id : null,
                            'priority' => 'medium',
                        ]);
                    }
                }
                return response()->json([
                    'success' => true,
                    'client_id' => $client ? $client->id : null
                ]);
            } catch (\Exception $e) {
                Log::error('WhatsApp Webhook client.sync error: ' . $e->getMessage());
                return response()->json(['success' => false, 'message' => $e->getMessage(), 'trace' => $e->getTraceAsString()], 500);
            }
        }
        
        if ($event === 'ticket.sync') {
            try {
                $phone = $data['phone'] ?? null;
                $categoryName = $data['category_name'] ?? 'عام';
                $providerThreadId = $data['thread_id'] ?? null;
                $providerTicketId = $data['ticket_id'] ?? null;
                $status = $data['status'] ?? 'open'; // open, resolved, closed

                if ($phone && $providerThreadId) {
                    $phone = preg_replace('/[^0-9]/', '', $phone);
                    
                    // 1. Find or Create Client
                    $client = \App\Models\Client::where('tenant_id', $tenant->id)->where('phone', $phone)->first();
                    if (!$client) {
                        $source = \App\Models\Source::where('name', 'واتساب')->first();
                        $client = \App\Models\Client::create([
                            'tenant_id' => $tenant->id,
                            'name' => $data['name'] ?? 'عميل واتساب',
                            'phone' => $phone,
                            'source_id' => $source ? $source->id : null,
                        ]);
                    }

                    // 2. Find or Create Category
                    $category = \App\Models\TicketCategory::firstOrCreate(
                        ['tenant_id' => $tenant->id, 'name' => $categoryName],
                        ['color' => '#6B7280', 'is_active' => true]
                    );

                    // 3. Find or Create Ticket
                    $ticket = null;

                    // Match by provider_ticket_id if provided
                    if ($providerTicketId) {
                        $ticket = \App\Models\Ticket::where('tenant_id', $tenant->id)
                            ->whereJsonContains('metadata->provider_ticket_id', $providerTicketId)
                            ->first();
                    }

                    // Otherwise match by thread
                    if (!$ticket) {
                        $threadQuery = \App\Models\Ticket::where('tenant_id', $tenant->id)
                            ->whereJsonContains('metadata->provider_thread_id', $providerThreadId);

                        if ($status === 'open') {
                            // Only match an existing ACTIVE ticket; if previous is closed, create a new one!
                            $ticket = (clone $threadQuery)->whereIn('status', ['open', 'in_progress', 'pending_client'])->latest('id')->first();
                        } else {
                            // When closing or resolving, target the latest ticket for this thread
                            $ticket = (clone $threadQuery)->latest('id')->first();
                        }
                    }

                    // Extract optional fields
                    $subject = $data['subject'] ?? null;
                    $description = $data['description'] ?? null;
                    $summary = $data['summary'] ?? null;
                    $rating = $data['rating'] ?? null;

                    if ($ticket) {
                        // Update existing ticket
                        $updateData = ['status' => $status, 'category_id' => $category->id];
                        if ($status === 'resolved' && $ticket->status !== 'resolved') $updateData['resolved_at'] = now();
                        if ($status === 'closed' && $ticket->status !== 'closed') $updateData['closed_at'] = now();
                        if ($status === 'open') $updateData['closed_at'] = null; // Reopening
                        
                        if ($subject) $updateData['title'] = $subject;
                        if ($description) $updateData['description'] = $description;
                        
                        // Update metadata safely
                        $metadata = is_array($ticket->metadata) ? $ticket->metadata : (json_decode($ticket->metadata ?? '[]', true) ?? []);
                        if ($providerTicketId) $metadata['provider_ticket_id'] = $providerTicketId;
                        if ($summary) $metadata['ai_summary'] = $summary;
                        if ($rating) $metadata['rating'] = $rating;
                        $updateData['metadata'] = $metadata;

                        $ticket->update($updateData);
                    } else {
                        // Create brand new ticket
                        $lastTicket = \App\Models\Ticket::where('tenant_id', $tenant->id)->latest('id')->first();
                        $ticketNumber = 'TKT-' . str_pad((string)(($lastTicket ? $lastTicket->id : 0) + 1), 6, '0', STR_PAD_LEFT);

                        $ticket = \App\Models\Ticket::create([
                            'tenant_id' => $tenant->id,
                            'ticket_number' => $ticketNumber,
                            'client_id' => $client->id,
                            'category_id' => $category->id,
                            'title' => $subject ?? ('محادثة واتساب - ' . $client->name),
                            'description' => $description ?? '',
                            'status' => $status,
                            'source' => 'whatsapp',
                            'metadata' => array_filter([
                                'provider_thread_id' => $providerThreadId,
                                'provider_ticket_id' => $providerTicketId,
                                'ai_summary' => $summary,
                                'rating' => $rating,
                            ])
                        ]);
                    }

                    return response()->json([
                        'success' => true,
                        'ticket_id' => $ticket->id,
                        'ticket_number' => $ticket->ticket_number,
                        'status' => $ticket->status
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('WhatsApp Webhook ticket.sync error: ' . $e->getMessage());
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }
        }
        
        return response()->json(['success' => true]);
    }
}
