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
            $query->where(function($q) use ($request) {
                $q->where('tickets.rating', $request->rating)
                  ->orWhereHas('evaluations', function($sub) use ($request) {
                      $sub->where('rating', $request->rating);
                  });
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

        // Auto-sync evaluations for tickets that have rating
        $tickets->getCollection()->transform(function ($t) {
            $effectiveRating = $t->rating ?? ($t->metadata['rating'] ?? null);
            if (!empty($effectiveRating) && $t->evaluations->isEmpty()) {
                try {
                    $defaultType = \App\Models\EvaluationType::where('tenant_id', $t->tenant_id)->first();
                    $eval = \App\Models\Evaluation::create([
                        'tenant_id' => $t->tenant_id,
                        'client_id' => $t->client_id,
                        'assigned_user_id' => $t->assigned_to,
                        'ticket_id' => $t->id,
                        'type_id' => $defaultType?->id,
                        'rating' => (int) $effectiveRating,
                        'channel' => 'whatsapp',
                        'notes' => 'تقييم عبر واتساب: ' . $effectiveRating . ' نجوم',
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

        $effectiveRating = $ticket->rating ?? ($ticket->metadata['rating'] ?? null);
        if (!empty($effectiveRating) && $ticket->evaluations->isEmpty()) {
            try {
                $defaultType = \App\Models\EvaluationType::where('tenant_id', $ticket->tenant_id)->first();
                $eval = \App\Models\Evaluation::create([
                    'tenant_id' => $ticket->tenant_id,
                    'client_id' => $ticket->client_id,
                    'assigned_user_id' => $ticket->assigned_to,
                    'ticket_id' => $ticket->id,
                    'type_id' => $defaultType?->id,
                    'rating' => (int) $effectiveRating,
                    'channel' => 'whatsapp',
                    'notes' => 'تقييم عبر واتساب: ' . $effectiveRating . ' نجوم',
                    'metadata' => ['source' => 'whatsapp_ticket_sync']
                ]);
                $ticket->setRelation('evaluations', collect([$eval]));
            } catch (\Exception $e) {}
        }

        $ticketArray = $ticket->toArray();
        $ticketArray['has_whatsapp_chat'] = ($ticket->source === 'whatsapp' || !empty($ticket->provider_thread_id) || !empty($ticket->metadata['provider_thread_id']));

        $latestEvaluation = $ticket->evaluations->sortByDesc('id')->first();
        $ticketArray['evaluation'] = $latestEvaluation;
        if (!isset($ticketArray['rating']) || $ticketArray['rating'] === null) {
            $ticketArray['rating'] = $latestEvaluation ? $latestEvaluation->rating : $effectiveRating;
        }

        // Live-merge WhatsApp messages from provider if it's a WhatsApp ticket
        if ($ticketArray['has_whatsapp_chat']) {
            try {
                $whatsappService = app(\App\Services\Integrations\Contracts\WhatsAppServiceInterface::class);
                $threadId = $ticket->provider_thread_id ?? ($ticket->metadata['provider_thread_id'] ?? null);
                if (!$threadId && $ticket->client?->phone) {
                    $threadId = $whatsappService->findThreadByPhone($ticket->client->phone);
                    if ($threadId) {
                        $ticket->update(['provider_thread_id' => $threadId]);
                    }
                }

                if ($threadId) {
                    $providerMsgs = $whatsappService->getThreadMessages($threadId);
                    if (!empty($providerMsgs) && is_array($providerMsgs)) {
                        $providerTicketId = $ticket->provider_ticket_id ?? ($ticket->metadata['provider_ticket_id'] ?? null);
                        $ticketCreatedAt = $ticket->created_at ? strtotime((string)$ticket->created_at) : null;
                        $ticketClosedAt = (!in_array($ticket->status, ['open', 'in_progress', 'pending_client']) && $ticket->closed_at) ? strtotime((string)$ticket->closed_at) : null;
                        $formattedMsgs = [];

                        // Find previous closed ticket for this client to determine start of conversation window
                        $prevTicket = \App\Models\Ticket::where('tenant_id', $ticket->tenant_id)
                            ->where('client_id', $ticket->client_id)
                            ->where('id', '<', $ticket->id)
                            ->latest('id')
                            ->first();
                        $windowStart = ($prevTicket && $prevTicket->closed_at)
                            ? strtotime((string)$prevTicket->closed_at)
                            : ($ticketCreatedAt ? ($ticketCreatedAt - (7 * 86400)) : (time() - (7 * 86400)));
                        $prevProviderTicketId = $prevTicket?->provider_ticket_id ?? ($prevTicket?->metadata['provider_ticket_id'] ?? null);

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

                            $content = $pm['content'] ?? ($pm['message'] ?? '');
                            $isEvaluationMsg = str_contains($content, 'تقييم') || str_contains($content, 'شكراً لتقييمك') || str_contains($content, 'RATE_');

                            // Evaluation messages belong to the closed ticket that was evaluated, NEVER to an open ticket!
                            if ($isEvaluationMsg && $ticket->status === 'open') {
                                continue;
                            }

                            // Filter messages for this ticket if provider_ticket_id exists or timeframe matches
                            if ($providerTicketId || $ticketCreatedAt) {
                                $msgTicketId = $pm['ticketId'] ?? null;
                                $msgTime = strtotime($pm['createdAt'] ?? '');
                                
                                $isMatch = false;
                                if ($providerTicketId && $msgTicketId === $providerTicketId) {
                                    if (!$isEvaluationMsg || $ticket->status !== 'open') {
                                        $isMatch = true;
                                    }
                                } elseif ($ticketCreatedAt && $msgTime) {
                                    $start = max($windowStart, $ticketCreatedAt - (7 * 86400));
                                    $end = $ticketClosedAt ? ($ticketClosedAt + 300) : (time() + 3600);
                                    
                                    if ($msgTime >= $start && $msgTime <= $end) {
                                        if (empty($msgTicketId) || $msgTicketId === $providerTicketId || ($prevProviderTicketId && $msgTicketId === $prevProviderTicketId)) {
                                            $isMatch = true;
                                        }
                                    }
                                }
                                
                                if (!$isMatch) {
                                    continue;
                                }
                            }

                            $formattedMsgs[] = $this->formatProviderMessage($pm, $ticket, $ticketArray['messages'] ?? []);
                        }

                        // Fallback: If no messages matched this ticket specifically, but the thread has messages and local messages is empty, show the recent thread messages
                        if (empty($formattedMsgs) && empty($ticketArray['messages'])) {
                            foreach (array_slice($providerMsgs, -30) as $pm) {
                                if (is_array($pm)) {
                                    $formattedMsgs[] = $this->formatProviderMessage($pm, $ticket, $ticketArray['messages'] ?? []);
                                }
                            }
                        }

                        $isWhatsApp = ($ticket->source === 'whatsapp' || !empty($threadId));

                        if ($isWhatsApp && !empty($formattedMsgs)) {
                            // As requested: For WhatsApp tickets, the WhatsApp provider is the single source of truth for the conversation.
                            // We do NOT include local chat messages (is_internal = false) to prevent duplication.
                            // Only local internal team notes (is_internal = true) that are not already present in the provider stream are merged.
                            $localInternalNotes = collect($ticketArray['messages'] ?? [])->filter(function ($m) use ($formattedMsgs) {
                                $isInternal = is_array($m) ? (!empty($m['is_internal'])) : (!empty($m->is_internal));
                                if (!$isInternal) {
                                    return false;
                                }
                                $content = is_array($m) ? ($m['content'] ?? '') : ($m->content ?? '');
                                foreach ($formattedMsgs as $pm) {
                                    if (!empty($pm['is_internal']) && trim((string)$pm['content']) === trim((string)$content)) {
                                        return false;
                                    }
                                }
                                return true;
                            })->values()->all();

                            $allMessages = collect($formattedMsgs)
                                ->concat($localInternalNotes)
                                ->sortBy(function ($m) {
                                    return strtotime((string)($m['created_at'] ?? 'now'));
                                })
                                ->values()
                                ->all();

                            $ticketArray['messages'] = $allMessages;
                        } elseif (!empty($formattedMsgs)) {
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
        if (isset($data['status']) && in_array($data['status'], ['open', 'in_progress', 'pending_client'])) {
            $data['closed_at'] = null;
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

    private function formatProviderMessage(array $pm, Ticket $ticket, $localMessages = null): array
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

        // If from me, check if we can identify the specific agent who sent this message from CRM
        if ($isFromMe && $localMessages && !empty($content)) {
            $matchingLocal = collect($localMessages)->first(function ($lm) use ($content) {
                $c = is_array($lm) ? ($lm['content'] ?? '') : ($lm->content ?? '');
                return trim((string)$c) === trim((string)$content);
            });
            if ($matchingLocal) {
                $lmUserId = is_array($matchingLocal) ? ($matchingLocal['user_id'] ?? null) : ($matchingLocal->user_id ?? null);
                $lmUserName = is_array($matchingLocal) ? ($matchingLocal['user']['name'] ?? $matchingLocal['sender_name'] ?? null) : ($matchingLocal->user?->name ?? $matchingLocal->sender_name ?? null);
                $lmUserEmail = is_array($matchingLocal) ? ($matchingLocal['user']['email'] ?? $matchingLocal['sender_email'] ?? null) : ($matchingLocal->user?->email ?? $matchingLocal->sender_email ?? null);

                if ($lmUserId) $userId = $lmUserId;
                if ($lmUserName) $senderName = $lmUserName;
                if ($lmUserEmail) $senderEmail = $lmUserEmail;
            }
        }

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
            : 'Asia/Riyadh';

        $rawCreatedAt = !empty($pm['createdAt']) ? $pm['createdAt'] : 'now';
        $rawUpdatedAt = !empty($pm['updatedAt']) ? $pm['updatedAt'] : $rawCreatedAt;

        $parseDate = function ($raw) use ($tz) {
            if (is_numeric($raw)) {
                $c = \Carbon\Carbon::createFromTimestamp((int) $raw, 'UTC');
            } elseif (is_string($raw) && !empty($raw)) {
                $c = \Carbon\Carbon::parse($raw, 'UTC');
            } else {
                $c = \Carbon\Carbon::now('UTC');
            }
            return $c->setTimezone($tz)->toIso8601String();
        };

        $msgCreatedAt = $parseDate($rawCreatedAt);
        $msgUpdatedAt = $parseDate($rawUpdatedAt);

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
