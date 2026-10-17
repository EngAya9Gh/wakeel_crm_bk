<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Tickets;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tickets\StoreTicketMessageRequest;
use App\Models\Ticket;
use App\Services\Tickets\TicketService;
use Illuminate\Http\JsonResponse;

class TicketMessageController extends Controller
{
    public function __construct(private readonly TicketService $ticketService) {}

    public function index(Ticket $ticket): JsonResponse
    {
        $localMessages = $ticket->messages()->with('user:id,name')->get();
        $formattedMsgs = [];

        // Check if this ticket is linked to a WhatsApp thread
        $threadId = $ticket->provider_thread_id ?? ($ticket->metadata['provider_thread_id'] ?? null);
        if ($threadId || $ticket->source === 'whatsapp') {
            try {
                $whatsappService = app(\App\Services\Integrations\Contracts\WhatsAppServiceInterface::class);
                if (!$threadId && $ticket->client?->phone) {
                    $threadId = $whatsappService->findThreadByPhone($ticket->client->phone);
                    if ($threadId) {
                        $ticket->update(['provider_thread_id' => $threadId]);
                    }
                }

                if ($threadId) {
                    $providerMsgs = $whatsappService->getThreadMessages((string)$threadId);
                    if (!empty($providerMsgs) && is_array($providerMsgs)) {
                        $providerTicketId = $ticket->provider_ticket_id ?? ($ticket->metadata['provider_ticket_id'] ?? null);
                        $ticketCreatedAt = $ticket->created_at ? strtotime((string)$ticket->created_at) : null;
                        $ticketClosedAt = $ticket->closed_at ? strtotime((string)$ticket->closed_at) : null;

                        // Find previous closed ticket for this client to determine start of conversation window
                        $prevTicket = \App\Models\Ticket::where('tenant_id', $ticket->tenant_id)
                            ->where('client_id', $ticket->client_id)
                            ->where('id', '<', $ticket->id)
                            ->latest('id')
                            ->first();
                        $windowStart = ($prevTicket && $prevTicket->closed_at)
                            ? strtotime((string)$prevTicket->closed_at)
                            : ($ticketCreatedAt ? ($ticketCreatedAt - (7 * 86400)) : (time() - (7 * 86400)));

                        $seenProviderIds = [];
                        foreach ($providerMsgs as $pm) {
                            if (!is_array($pm)) continue;

                            $rawId = $pm['id'] ?? ($pm['_id'] ?? null);
                            if ($rawId && isset($seenProviderIds[$rawId])) {
                                continue;
                            }
                            if ($rawId) {
                                $seenProviderIds[$rawId] = true;
                            }

                            // Filter messages for this ticket if provider_ticket_id exists or timeframe matches
                            if ($providerTicketId || $ticketCreatedAt) {
                                $msgTicketId = $pm['ticketId'] ?? null;
                                $msgTime = strtotime($pm['createdAt'] ?? '');
                                
                                $isMatch = false;
                                if ($providerTicketId && $msgTicketId === $providerTicketId) {
                                    $isMatch = true;
                                } elseif (empty($msgTicketId) && $ticketCreatedAt && $msgTime) {
                                    // Messages sent before or during this ticket without a specific ticket ID
                                    // Include messages from windowStart (since last ticket or up to 7 days) up to ticket close
                                    $start = max($windowStart, $ticketCreatedAt - (7 * 86400));
                                    $end = $ticketClosedAt ? ($ticketClosedAt + 300) : (time() + 3600);
                                    if ($msgTime >= $start && $msgTime <= $end) {
                                        $isMatch = true;
                                    }
                                } elseif ($ticketCreatedAt && $msgTime) {
                                    $start = max($windowStart, $ticketCreatedAt - (7 * 86400));
                                    $end = $ticketClosedAt ? ($ticketClosedAt + 300) : (time() + 3600);
                                    
                                    if ($msgTime >= $start && $msgTime <= $end) {
                                        if (empty($msgTicketId) || $msgTicketId === $providerTicketId) {
                                            $isMatch = true;
                                        } elseif (str_contains($pm['content'] ?? '', 'تقييم') || str_contains($pm['content'] ?? '', 'شكراً')) {
                                            $isMatch = true;
                                        }
                                    }
                                }
                                
                                if (!$isMatch) {
                                    continue;
                                }
                            }

                            $pmFormatted = $this->formatProviderMessage($pm, $ticket);

                            // Deduplicate: If an agent sent this message from CRM, it already exists in $localMessages.
                            // Dropping the provider echo preserves the original author name (e.g. رغد الشامل) and prevents duplicate.
                            if (!empty($pmFormatted['user_id'])) {
                                $isDuplicate = $localMessages->contains(function ($local) use ($pmFormatted) {
                                    if (trim((string)$local->content) !== trim((string)$pmFormatted['content'])) {
                                        return false;
                                    }
                                    $localTime = strtotime((string)$local->created_at);
                                    $providerTime = strtotime((string)$pmFormatted['created_at']);
                                    return abs($localTime - $providerTime) <= 180;
                                });

                                if ($isDuplicate) {
                                    continue;
                                }
                            }

                            $formattedMsgs[] = $pmFormatted;
                        }

                        // Fallback: If no messages matched this ticket specifically, but the thread has messages and local messages is empty, show the recent thread messages
                        if (empty($formattedMsgs) && $localMessages->isEmpty()) {
                            foreach (array_slice($providerMsgs, -30) as $pm) {
                                if (is_array($pm)) {
                                    $formattedMsgs[] = $this->formatProviderMessage($pm, $ticket);
                                }
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning("Could not live-fetch provider messages in TicketMessageController index: " . $e->getMessage());
            }
        }

        // Merge local messages and WhatsApp messages, sorted by created_at DESC (as expected by latest() and Flutter chat view)
        $allMessages = collect($localMessages->toArray())
            ->concat($formattedMsgs)
            ->sortByDesc(function ($m) {
                return strtotime((string)($m['created_at'] ?? 'now'));
            })
            ->values();

        $page = (int) request()->input('page', 1);
        $perPage = 50;
        $sliced = $allMessages->slice(($page - 1) * $perPage, $perPage)->values();

        $paginated = new \Illuminate\Pagination\LengthAwarePaginator(
            $sliced,
            $allMessages->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return response()->json([
            'success' => true,
            'data' => $paginated
        ]);
    }

    public function store(StoreTicketMessageRequest $request, Ticket $ticket): JsonResponse
    {
        $data = $request->validated();
        
        // Populate standard fields for dashboard reply
        $data['user_id'] = auth()->id();
        $data['sender_name'] = auth()->user()->name ?? 'System';
        $data['sender_email'] = auth()->user()->email ?? null;

        $message = $this->ticketService->addMessage($ticket, $data);

        // Forward to WhatsApp thread:
        // - If normal message: sends message to customer on WhatsApp
        // - If internal note: sends team note to provider (isInternal: true) without texting the customer
        $threadId = $ticket->provider_thread_id ?? ($ticket->metadata['provider_thread_id'] ?? null);
        if ($ticket->source === 'whatsapp' || !empty($threadId)) {
            try {
                $whatsappService = app(\App\Services\Integrations\Contracts\WhatsAppServiceInterface::class);
                if (!$threadId && $ticket->client?->phone) {
                    $threadId = $whatsappService->findThreadByPhone($ticket->client->phone);
                    if ($threadId) {
                        $ticket->update(['provider_thread_id' => $threadId]);
                    }
                }
                if ($threadId) {
                    $isInternal = !empty($data['is_internal']);
                    $whatsappService->replyToThread($threadId, $data['content'], 'text', null, $isInternal);
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Failed to forward ticket reply to WhatsApp: " . $e->getMessage());
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'تم إضافة الرد بنجاح',
            'data' => $message->load('user:id,name')
        ], 201);
    }

    public function providerMessages(Ticket $ticket, \App\Services\Integrations\Contracts\WhatsAppServiceInterface $whatsappService): JsonResponse
    {
        $phone = $ticket->client?->phone;
        if (!$phone) {
            return response()->json(['success' => false, 'message' => 'العميل لا يملك رقم هاتف'], 400);
        }

        // 1. Try to get thread_id from dedicated column, then metadata
        $threadId = $ticket->provider_thread_id ?? ($ticket->metadata['provider_thread_id'] ?? null);
        
        // 2. If not found, ask Provider via findThreadByPhone
        if (!$threadId) {
            $threadId = $whatsappService->findThreadByPhone($phone);
            if ($threadId) {
                $ticket->update(['provider_thread_id' => $threadId]);
            }
        }

        if (!$threadId) {
            return response()->json(['success' => true, 'data' => []]); // No chat history found
        }

        // 3. Fetch messages from Provider
        $messages = $whatsappService->getThreadMessages($threadId);

        return response()->json([
            'success' => true,
            'data' => $messages
        ]);
    }

    public function sendProviderMessage(\Illuminate\Http\Request $request, Ticket $ticket, \App\Services\Integrations\Contracts\WhatsAppServiceInterface $whatsappService): JsonResponse
    {
        $request->validate(['content' => 'required|string']);
        
        $phone = $ticket->client?->phone;
        if (!$phone) {
            return response()->json(['success' => false, 'message' => 'العميل لا يملك رقم هاتف'], 400);
        }

        $threadId = $ticket->metadata['provider_thread_id'] ?? $whatsappService->findThreadByPhone($phone);
        
        if (!$threadId) {
            return response()->json(['success' => false, 'message' => 'لا توجد محادثة مفتوحة مع هذا العميل في المزود'], 400);
        }

        $content = $request->input('content');
        $success = $whatsappService->replyToThread($threadId, $content, 'text');

        if ($success) {
            return response()->json(['success' => true, 'message' => 'تم الإرسال بنجاح']);
        }

        return response()->json(['success' => false, 'message' => 'فشل الإرسال عبر المزود'], 500);
    }

    private function formatProviderMessage(array $pm, Ticket $ticket): array
    {
        // 1. Determine direction (fromMe / incoming / outgoing)
        $isFromMe = !empty($pm['fromMe']) 
            || (isset($pm['from_me']) && $pm['from_me'])
            || (isset($pm['direction']) && in_array(strtolower((string)$pm['direction']), ['outgoing', 'outbound', 'out']))
            || (isset($pm['sender']) && in_array(strtolower((string)$pm['sender']), ['me', 'agent', 'system', 'business', 'user']))
            || (!empty($pm['userId']) || !empty($pm['user_id']));

        $senderName = $isFromMe 
            ? ($ticket->assignedTo?->name ?? 'فريق الدعم (واتساب)') 
            : ($ticket->client?->name ?? 'العميل');

        // 2. Extract content thoroughly
        $content = '';
        foreach (['content', 'body', 'text', 'caption', 'msg'] as $field) {
            if (!empty($pm[$field]) && is_string($pm[$field])) {
                $content = trim($pm[$field]);
                break;
            }
        }

        if (empty($content) && !empty($pm['message'])) {
            if (is_string($pm['message'])) {
                $content = trim($pm['message']);
            } elseif (is_array($pm['message'])) {
                $msg = $pm['message'];
                $content = $msg['conversation'] 
                    ?? $msg['text'] 
                    ?? $msg['extendedTextMessage']['text'] 
                    ?? $msg['caption'] 
                    ?? $msg['imageMessage']['caption'] 
                    ?? $msg['videoMessage']['caption'] 
                    ?? $msg['buttonsResponseMessage']['selectedDisplayText'] 
                    ?? $msg['templateButtonReplyMessage']['selectedDisplayText'] 
                    ?? $msg['listResponseMessage']['title'] 
                    ?? '';
            }
        }

        if (empty($content) && !empty($pm['payload'])) {
            if (is_string($pm['payload'])) {
                $content = trim($pm['payload']);
            } elseif (is_array($pm['payload'])) {
                $content = $pm['payload']['text'] ?? $pm['payload']['content'] ?? $pm['payload']['body'] ?? '';
            }
        }

        if (empty($content)) {
            // Check all other string properties that aren't metadata/IDs
            foreach ($pm as $key => $val) {
                if (is_string($val) && !in_array($key, ['id', '_id', 'threadId', 'thread_id', 'type', 'status', 'direction', 'createdAt', 'updatedAt', 'timestamp', 'from', 'to', 'tenantId', 'channelId', 'channel_id'])) {
                    if (strlen(trim($val)) > 0 && !filter_var($val, FILTER_VALIDATE_URL)) {
                        $content = trim($val);
                        break;
                    }
                }
            }
        }

        $mediaUrl = $pm['mediaUrl'] ?? $pm['media'] ?? $pm['url'] ?? $pm['fileUrl'] ?? null;
        if ($mediaUrl && is_string($mediaUrl) && str_starts_with($mediaUrl, '/')) {
            $base = config('services.whatsapp.base_url', 'https://provider.wakeel.cc');
            $scheme = parse_url($base, PHP_URL_SCHEME) ?? 'https';
            $host = parse_url($base, PHP_URL_HOST) ?? 'provider.wakeel.cc';
            $port = parse_url($base, PHP_URL_PORT) ? ':' . parse_url($base, PHP_URL_PORT) : '';
            $mediaUrl = "{$scheme}://{$host}{$port}" . $mediaUrl;
        }

        if (empty($content) && (!empty($mediaUrl) || !empty($pm['hasMedia']) || (!empty($pm['type']) && in_array(strtolower((string)$pm['type']), ['image', 'video', 'audio', 'document', 'voice', 'ptt'])))) {
            $type = strtolower((string)($pm['type'] ?? 'file'));
            $content = match($type) {
                'audio', 'voice', 'ptt' => '🎤 رسالة صوتية',
                'image' => '📷 صورة',
                'video' => '🎥 مقطع فيديو',
                'document' => '📄 مستند',
                default => '📁 ملف وسائط'
            };
        }

        $attachments = $mediaUrl ? [['url' => $mediaUrl, 'type' => $pm['type'] ?? 'file']] : null;

        $rawId = $pm['id'] ?? ($pm['_id'] ?? uniqid());
        $numericId = (int) (abs(crc32('wa_' . $rawId)) % 2000000000 + 1000);
        $userId = $isFromMe ? ($ticket->assigned_to ?? 1) : null;
        $senderEmail = $isFromMe ? ($ticket->assignedTo?->email ?? 'support@crm.test') : ($ticket->client?->email ?? null);

        // Always provide a valid user object structure to satisfy strict Dart/Flutter models
        $userObj = [
            'id' => $userId ?? 0,
            'name' => $senderName,
            'email' => $senderEmail,
            'permissions_list' => [],
            'role' => null,
        ];

        $tz = \App\Services\TenantContext::isResolved() 
            ? \App\Services\TenantContext::timezone() 
            : config('app.timezone', 'Asia/Riyadh');
        $rawCreatedAt = !empty($pm['createdAt']) ? $pm['createdAt'] : 'now';
        $rawUpdatedAt = !empty($pm['updatedAt']) ? $pm['updatedAt'] : $rawCreatedAt;

        $msgCreatedAt = \Carbon\Carbon::parse($rawCreatedAt)->setTimezone($tz)->toIso8601String();
        $msgUpdatedAt = \Carbon\Carbon::parse($rawUpdatedAt)->setTimezone($tz)->toIso8601String();

        return [
            'id' => $numericId,
            'ticket_id' => $ticket->id,
            'user_id' => $userId,
            'sender_name' => $senderName,
            'sender_email' => $senderEmail,
            'content' => $content,
            'is_internal' => !empty($pm['isInternal']),
            'attachments' => $attachments,
            'created_at' => $msgCreatedAt,
            'updated_at' => $msgUpdatedAt,
            'source' => 'whatsapp',
            'user' => $userObj,
        ];
    }
}
