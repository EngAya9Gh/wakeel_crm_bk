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
        $messages = $ticket->messages()->with('user:id,name')->latest()->paginate(20);
        return response()->json([
            'success' => true,
            'data' => $messages
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
