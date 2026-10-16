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
            $fromPhone = $payload['from'] ?? $payload['phone'] ?? null;
            
            if ($threadId) {
                // Pass the tenant ID to the event so it can be scoped properly
                \App\Events\NewWhatsAppMessageReceived::dispatch($payload, (string) $threadId, $tenant->id);
            }

            // Check if this incoming message is an evaluation rating for a recently closed ticket
            $text = trim((string)($payload['body'] ?? $payload['text'] ?? $payload['message'] ?? $payload['content'] ?? ''));
            if (empty($text) && !empty($payload['button_reply']['id'])) {
                $text = (string)$payload['button_reply']['id'];
            }
            if (empty($text) && !empty($payload['button_reply']['title'])) {
                $text = (string)$payload['button_reply']['title'];
            }

            $ratingVal = null;
            if (preg_match('/^[1-5]$/', $text)) {
                $ratingVal = (int) $text;
            } elseif (preg_match('/(?:rating|star|stars|نجوم|نجمة)[_\s-]*([1-5])/iu', $text, $m)) {
                $ratingVal = (int) $m[1];
            } elseif (preg_match('/([1-5])\s*(?:star|stars|نجوم|نجمة)/iu', $text, $m)) {
                $ratingVal = (int) $m[1];
            } elseif (mb_substr_count($text, '⭐') >= 1 && mb_substr_count($text, '⭐') <= 5) {
                $ratingVal = mb_substr_count($text, '⭐');
            }

            if ($ratingVal !== null && $ratingVal >= 1 && $ratingVal <= 5) {
                // Find latest closed ticket for this thread or phone
                $targetTicket = null;
                if ($threadId) {
                    $targetTicket = \App\Models\Ticket::where('tenant_id', $tenant->id)
                        ->whereJsonContains('metadata->provider_thread_id', (string)$threadId)
                        ->where('status', 'closed')
                        ->latest('closed_at')
                        ->first();
                }

                if (!$targetTicket && $fromPhone) {
                    $foundClient = \App\Models\Client::findByPhoneFlexible($tenant->id, (string)$fromPhone);
                    if ($foundClient) {
                        $targetTicket = \App\Models\Ticket::where('tenant_id', $tenant->id)
                            ->where('client_id', $foundClient->id)
                            ->where('status', 'closed')
                            ->latest('closed_at')
                            ->first();
                    }
                }

                // If not closed, find the latest ticket in general
                if (!$targetTicket && $threadId) {
                    $targetTicket = \App\Models\Ticket::where('tenant_id', $tenant->id)
                        ->whereJsonContains('metadata->provider_thread_id', (string)$threadId)
                        ->latest('id')
                        ->first();
                }

                if ($targetTicket) {
                    $defaultType = \App\Models\EvaluationType::where('tenant_id', $tenant->id)->first();
                    $targetTicket->evaluations()->updateOrCreate(
                        [
                            'tenant_id' => $tenant->id,
                            'ticket_id' => $targetTicket->id,
                        ],
                        [
                            'client_id' => $targetTicket->client_id,
                            'assigned_user_id' => $targetTicket->assigned_to,
                            'type_id' => $defaultType ? $defaultType->id : null,
                            'rating' => $ratingVal,
                            'channel' => 'whatsapp',
                            'notes' => 'تقييم عبر واتساب: ' . $ratingVal . ' نجوم',
                            'metadata' => [
                                'raw_message' => $text,
                                'source' => 'whatsapp_incoming_rating'
                            ]
                        ]
                    );

                    $metadata = is_array($targetTicket->metadata) ? $targetTicket->metadata : (json_decode($targetTicket->metadata ?? '[]', true) ?? []);
                    $metadata['rating'] = $ratingVal;
                    $targetTicket->update(['metadata' => $metadata]);

                    Log::info("Recorded WhatsApp Evaluation: {$ratingVal} stars for Ticket #{$targetTicket->ticket_number}");
                }
            }
        }
        
        if ($event === 'client.sync') {
            try {
                $phone = $data['phone'] ?? null;
                $name = $data['name'] ?? 'WhatsApp Lead';
                $client = null;
                if ($phone) {
                    // Use flexible search to avoid duplicates due to + or leading zeros
                    $client = \App\Models\Client::findByPhoneFlexible($tenant->id, (string)$phone);
                    if (!$client) {
                        $digits = preg_replace('/[^0-9]/', '', (string)$phone);
                        $source = \App\Models\Source::where('name', 'واتساب')->first();
                        $defaultStatus = \App\Models\ClientStatus::where('is_default', true)->first();
                        $client = \App\Models\Client::create([
                            'tenant_id' => $tenant->id,
                            'name' => $name,
                            'phone' => $digits ?: $phone,
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
                    // 1. Find or Create Client flexibly
                    $client = \App\Models\Client::findByPhoneFlexible($tenant->id, (string)$phone);
                    if (!$client) {
                        $digits = preg_replace('/[^0-9]/', '', (string)$phone);
                        $source = \App\Models\Source::where('name', 'واتساب')->first();
                        $client = \App\Models\Client::create([
                            'tenant_id' => $tenant->id,
                            'name' => $data['name'] ?? 'عميل واتساب',
                            'phone' => $digits ?: $phone,
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

                        // If rating is provided in ticket.sync, record evaluation
                        if ($rating) {
                            $ratingVal = (int) preg_replace('/[^0-9]/', '', (string)$rating);
                            if ($ratingVal >= 1 && $ratingVal <= 5) {
                                $defaultType = \App\Models\EvaluationType::where('tenant_id', $tenant->id)->first();
                                $ticket->evaluations()->updateOrCreate(
                                    [
                                        'tenant_id' => $tenant->id,
                                        'ticket_id' => $ticket->id,
                                    ],
                                    [
                                        'client_id' => $ticket->client_id,
                                        'assigned_user_id' => $ticket->assigned_to,
                                        'type_id' => $defaultType ? $defaultType->id : null,
                                        'rating' => $ratingVal,
                                        'channel' => 'whatsapp',
                                        'notes' => 'تقييم عبر واتساب: ' . $ratingVal . ' نجوم',
                                        'metadata' => ['source' => 'whatsapp_ticket_sync']
                                    ]
                                );
                            }
                        }
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

                        if ($rating) {
                            $ratingVal = (int) preg_replace('/[^0-9]/', '', (string)$rating);
                            if ($ratingVal >= 1 && $ratingVal <= 5) {
                                $defaultType = \App\Models\EvaluationType::where('tenant_id', $tenant->id)->first();
                                $ticket->evaluations()->create([
                                    'tenant_id' => $tenant->id,
                                    'client_id' => $client->id,
                                    'assigned_user_id' => $ticket->assigned_to,
                                    'type_id' => $defaultType ? $defaultType->id : null,
                                    'rating' => $ratingVal,
                                    'channel' => 'whatsapp',
                                    'notes' => 'تقييم عبر واتساب: ' . $ratingVal . ' نجوم',
                                    'metadata' => ['source' => 'whatsapp_ticket_sync']
                                ]);
                            }
                        }
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
