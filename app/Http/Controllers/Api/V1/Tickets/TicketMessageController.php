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
}
