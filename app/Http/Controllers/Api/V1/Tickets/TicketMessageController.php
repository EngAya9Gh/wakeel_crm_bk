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
        $threadId = $ticket->metadata['provider_thread_id'] ?? null;
        if ($threadId || $ticket->source === 'whatsapp') {
            try {
                $whatsappService = app(\App\Services\Integrations\Contracts\WhatsAppServiceInterface::class);
                if (!$threadId && $ticket->client?->phone) {
                    $threadId = $whatsappService->findThreadByPhone($ticket->client->phone);
                    if ($threadId) {
                        $meta = is_array($ticket->metadata) ? $ticket->metadata : [];
                        $meta['provider_thread_id'] = $threadId;
                        $ticket->update(['metadata' => $meta]);
                    }
                }

                if ($threadId) {
                    $providerMsgs = $whatsappService->getThreadMessages((string)$threadId);
                    if (!empty($providerMsgs) && is_array($providerMsgs)) {
                        foreach ($providerMsgs as $pm) {
                            $isFromMe = !empty($pm['fromMe']);
                            $senderName = $isFromMe 
                                ? ($ticket->assignedTo?->name ?? 'فريق الدعم') 
                                : ($ticket->client?->name ?? 'العميل');

                            $formattedMsgs[] = [
                                'id' => 'wa_' . ($pm['id'] ?? uniqid()),
                                'ticket_id' => $ticket->id,
                                'user_id' => $isFromMe ? ($ticket->assigned_to ?? 1) : null,
                                'sender_name' => $senderName,
                                'sender_email' => null,
                                'content' => $pm['content'] ?? $pm['body'] ?? $pm['text'] ?? '',
                                'is_internal' => !empty($pm['isInternal']),
                                'attachments' => !empty($pm['mediaUrl']) ? [['url' => $pm['mediaUrl']]] : null,
                                'created_at' => $pm['createdAt'] ?? $pm['timestamp'] ?? now()->toIso8601String(),
                                'updated_at' => $pm['createdAt'] ?? $pm['timestamp'] ?? now()->toIso8601String(),
                                'source' => 'whatsapp',
                                'user' => $isFromMe ? ['id' => $ticket->assigned_to ?? 1, 'name' => $senderName, 'permissions_list' => [], 'role' => null] : null,
                            ];
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

        // If it's a WhatsApp ticket and NOT an internal note, automatically forward to customer via WhatsApp!
        if (empty($data['is_internal']) && ($ticket->source === 'whatsapp' || !empty($ticket->metadata['provider_thread_id']))) {
            try {
                $whatsappService = app(\App\Services\Integrations\Contracts\WhatsAppServiceInterface::class);
                $threadId = $ticket->metadata['provider_thread_id'] ?? null;
                if (!$threadId && $ticket->client?->phone) {
                    $threadId = $whatsappService->findThreadByPhone($ticket->client->phone);
                }
                if ($threadId) {
                    $whatsappService->replyToThread($threadId, $data['content'], 'text');
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

        // 1. Try to get thread_id from ticket metadata
        $threadId = $ticket->metadata['provider_thread_id'] ?? null;
        
        // 2. If not found, ask Provider via findThreadByPhone
        if (!$threadId) {
            $threadId = $whatsappService->findThreadByPhone($phone);
            if ($threadId) {
                // Save it to metadata for future speed
                $metadata = $ticket->metadata ?? [];
                $metadata['provider_thread_id'] = $threadId;
                $ticket->update(['metadata' => $metadata]);
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
}
