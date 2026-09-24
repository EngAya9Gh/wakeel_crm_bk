<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Tickets;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tickets\StoreTicketRequest;
use App\Http\Requests\Tickets\UpdateTicketRequest;
use App\Models\Ticket;
use App\Services\Tickets\TicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function __construct(private readonly TicketService $ticketService) {}

    public function index(Request $request): JsonResponse
    {
        $query = Ticket::with([
            'client:id,name,phone', 
            'creator:id,name', 
            'assignedTo:id,name', 
            'category:id,name,color'
        ])->latest();

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        if ($request->has('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }
        
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $tickets = $query->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $tickets
        ]);
    }

    public function store(StoreTicketRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['tenant_id'] = auth()->user()->tenant_id ?? 1;
        $data['user_id'] = auth()->id();

        if (empty($data['source'])) {
            $data['source'] = 'manual';
        }

        $ticket = $this->ticketService->createTicket($data);

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء التذكرة بنجاح',
            'data' => $ticket
        ], 201);
    }

    public function show(Ticket $ticket): JsonResponse
    {
        $ticket->load([
            'client:id,name,phone,email', 
            'creator:id,name', 
            'assignedTo:id,name', 
            'category:id,name,color',
            'messages.user:id,name'
        ]);
        
        return response()->json([
            'success' => true,
            'data' => $ticket
        ]);
    }

    public function update(UpdateTicketRequest $request, Ticket $ticket): JsonResponse
    {
        $data = $request->validated();
        
        if (isset($data['status']) && $data['status'] === 'resolved' && $ticket->status !== 'resolved') {
            $data['resolved_at'] = now();
        }
        
        if (isset($data['status']) && $data['status'] === 'closed' && $ticket->status !== 'closed') {
            $data['closed_at'] = now();
        }

        $ticket->update($data);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث التذكرة بنجاح',
            'data' => $ticket->fresh()
        ]);
    }

    public function destroy(Ticket $ticket): JsonResponse
    {
        $ticket->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف التذكرة بنجاح'
        ]);
    }
}
