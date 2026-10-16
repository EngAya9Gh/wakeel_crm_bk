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
            'category:id,name,color',
            'evaluations:id,ticket_id,rating,type_id,notes'
        ])
        ->addSelect([
            'last_message' => \App\Models\TicketMessage::select('content')
                ->whereColumn('ticket_id', 'tickets.id')
                ->latest('id')
                ->take(1)
        ])
        ->latest();

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

        if ($request->has('rating')) {
            $query->whereHas('evaluations', function($q) use ($request) {
                $q->where('rating', $request->rating);
            });
        }

        if ($request->has('sub_category_id')) {
            $query->where('sub_category_id', $request->sub_category_id);
        }

        if ($request->has('source')) {
            $query->where('source', $request->source);
        }

        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $tickets = $query->paginate($request->integer('per_page', 15));

        // Auto-sync evaluations for tickets that have rating in metadata
        $tickets->getCollection()->transform(function ($t) {
            if (!empty($t->metadata['rating']) && $t->evaluations->isEmpty()) {
                try {
                    $defaultType = \App\Models\EvaluationType::where('tenant_id', $t->tenant_id)->first();
                    $eval = \App\Models\Evaluation::create([
                        'tenant_id' => $t->tenant_id,
                        'client_id' => $t->client_id,
                        'assigned_user_id' => $t->assigned_to,
                        'ticket_id' => $t->id,
                        'type_id' => $defaultType?->id,
                        'rating' => (int) $t->metadata['rating'],
                        'channel' => 'whatsapp',
                        'notes' => 'تقييم عبر واتساب: ' . $t->metadata['rating'] . ' نجوم',
                        'metadata' => ['source' => 'whatsapp_ticket_sync']
                    ]);
                    $t->setRelation('evaluations', collect([$eval]));
                } catch (\Exception $e) {}
            }
            return $t;
        });

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
            'messages.user:id,name',
            'evaluations'
        ]);

        if (!empty($ticket->metadata['rating']) && $ticket->evaluations->isEmpty()) {
            try {
                $defaultType = \App\Models\EvaluationType::where('tenant_id', $ticket->tenant_id)->first();
                $eval = \App\Models\Evaluation::create([
                    'tenant_id' => $ticket->tenant_id,
                    'client_id' => $ticket->client_id,
                    'assigned_user_id' => $ticket->assigned_to,
                    'ticket_id' => $ticket->id,
                    'type_id' => $defaultType?->id,
                    'rating' => (int) $ticket->metadata['rating'],
                    'channel' => 'whatsapp',
                    'notes' => 'تقييم عبر واتساب: ' . $ticket->metadata['rating'] . ' نجوم',
                    'metadata' => ['source' => 'whatsapp_ticket_sync']
                ]);
                $ticket->setRelation('evaluations', collect([$eval]));
            } catch (\Exception $e) {}
        }

        $ticketArray = $ticket->toArray();
        $ticketArray['has_whatsapp_chat'] = ($ticket->source === 'whatsapp' || !empty($ticket->metadata['provider_thread_id']));

        $latestEvaluation = $ticket->evaluations->sortByDesc('id')->first();
        $ticketArray['evaluation'] = $latestEvaluation;
        if (!isset($ticketArray['rating']) || $ticketArray['rating'] === null) {
            $ticketArray['rating'] = $latestEvaluation ? $latestEvaluation->rating : ($ticket->metadata['rating'] ?? null);
        }

        // Live-merge WhatsApp messages from provider if it's a WhatsApp ticket
        if ($ticketArray['has_whatsapp_chat']) {
            try {
                $whatsappService = app(\App\Services\Integrations\Contracts\WhatsAppServiceInterface::class);
                $threadId = $ticket->metadata['provider_thread_id'] ?? null;
                if (!$threadId && $ticket->client?->phone) {
                    $threadId = $whatsappService->findThreadByPhone($ticket->client->phone);
                    if ($threadId) {
                        $meta = is_array($ticket->metadata) ? $ticket->metadata : [];
                        $meta['provider_thread_id'] = $threadId;
                        $ticket->update(['metadata' => $meta]);
                    }
                }

                if ($threadId) {
                    $providerMsgs = $whatsappService->getThreadMessages($threadId);
                    if (!empty($providerMsgs) && is_array($providerMsgs)) {
                        $formattedMsgs = [];
                        foreach ($providerMsgs as $pm) {
                            $isFromMe = !empty($pm['fromMe']);
                            $formattedMsgs[] = [
                                'id' => 'wa_' . ($pm['id'] ?? uniqid()),
                                'ticket_id' => $ticket->id,
                                'user_id' => $isFromMe ? ($ticket->assigned_to ?? 1) : null,
                                'sender_name' => $isFromMe ? 'فريق الدعم (واتساب)' : ($ticket->client?->name ?? 'العميل'),
                                'content' => $pm['content'] ?? $pm['body'] ?? $pm['text'] ?? '',
                                'is_internal' => !empty($pm['isInternal']),
                                'created_at' => $pm['createdAt'] ?? $pm['timestamp'] ?? now()->toIso8601String(),
                                'source' => 'whatsapp',
                                'user' => $isFromMe ? ['id' => $ticket->assigned_to ?? 1, 'name' => 'فريق الدعم (واتساب)'] : null,
                            ];
                        }

                        $allMessages = collect($ticketArray['messages'] ?? [])
                            ->concat($formattedMsgs)
                            ->sortBy(function ($m) {
                                return strtotime((string)($m['created_at'] ?? 'now'));
                            })
                            ->values()
                            ->all();

                        $ticketArray['messages'] = $allMessages;
                    }
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning("Could not live-fetch provider messages in Ticket show: " . $e->getMessage());
            }
        }

        return response()->json([
            'success' => true,
            'data' => $ticketArray
        ]);
    }

    public function update(UpdateTicketRequest $request, Ticket $ticket): JsonResponse
    {
        $data = $request->validated();
        $evaluationData = $data['evaluation'] ?? null;
        unset($data['evaluation']);
        
        if (isset($data['status']) && $data['status'] === 'resolved' && $ticket->status !== 'resolved') {
            $data['resolved_at'] = now();
        }
        
        if (isset($data['status']) && $data['status'] === 'closed' && $ticket->status !== 'closed') {
            $data['closed_at'] = now();
        }

        $ticket->update($data);

        // Process Evaluation if provided
        if ($evaluationData) {
            $ticket->evaluations()->updateOrCreate(
                [
                    'tenant_id' => $ticket->tenant_id,
                    'type_id' => $evaluationData['type_id'],
                ],
                [
                    'client_id' => $ticket->client_id,
                    'assigned_user_id' => $ticket->assigned_to ?? auth()->id(),
                    'rating' => $evaluationData['rating'],
                    'notes' => $evaluationData['notes'] ?? null,
                    'channel' => 'manual',
                    'metadata' => ['source' => 'ticket_closure']
                ]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث التذكرة بنجاح',
            'data' => $ticket->fresh(['evaluations'])
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
