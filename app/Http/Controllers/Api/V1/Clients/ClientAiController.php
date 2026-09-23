<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Clients;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ClientAiSession;
use App\Services\AiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ClientAiController extends Controller
{
    use \App\Traits\ApiResponse;

    private AiService $aiService;

    public function __construct(AiService $aiService)
    {
        $this->aiService = $aiService;
    }

    private function hasAiFeature(Client $client): bool
    {
        return in_array('ai_agent', $client->tenant->enabled_features ?? []);
    }

    /**
     * Get automatically generated insights for the client (Lead Score, Summary).
     */
    public function insights(Client $client)
    {
        if (!$this->hasAiFeature($client)) {
            return $this->errorResponse('ميزة الذكاء الاصطناعي غير متاحة في باقتك الحالية', 403);
        }

        // Fetch the latest insight regardless of date
        $lastInsightSession = ClientAiSession::where('client_id', $client->id)
            ->where('type', 'insight')
            ->latest()
            ->first();

        // If it exists and is less than 24 hours old, return it
        if ($lastInsightSession && $lastInsightSession->created_at >= now()->subDay()) {
            $messages = $lastInsightSession->messages;
            $lastMessage = end($messages);
            return $this->successResponse([
                'insights' => json_decode($lastMessage['content'], true)
            ]);
        }

        // Generate new insights
        $context = $this->aiService->buildClientContext($client);
        
        $prompt = "قم بتحليل هذا العميل وأعطني النتائج حصراً بصيغة JSON تحتوي على:
        1. 'lead_score': رقم من 0 إلى 100
        2. 'reason': سطر واحد يشرح سبب التقييم
        3. 'summary': ملخص ذكي في سطرين عن تاريخ التفاوض
        4. 'warning': تحذير خمول إذا لم يكن هناك تواصل منذ أكثر من أسبوع (خلاف ذلك فارغ)
        بيانات العميل:
        " . $context;

        $response = $this->aiService->ask($prompt, [
            "أنت خبير مبيعات وتتحدث باللغة العربية حصراً.",
            "يجب أن تكون إجابتك بصيغة JSON صحيحة فقط بدون أي نص إضافي وبدون علامات ` ```json `."
        ]);

        if (!$response) {
            // Fallback to old insight if AI fails due to high demand
            if ($lastInsightSession) {
                $messages = $lastInsightSession->messages;
                $lastMessage = end($messages);
                return $this->successResponse([
                    'insights' => json_decode($lastMessage['content'], true),
                    'is_fallback' => true // Optionally let frontend know it's an old cache
                ]);
            }
            return $this->errorResponse('فشل في الاتصال بخدمة الذكاء الاصطناعي', 500);
        }

        // Clean response if it contains markdown formatting
        $response = str_replace(['```json', '```'], '', $response);
        $insightsData = json_decode(trim($response), true);

        if (!$insightsData) {
            Log::error('Invalid JSON from AI insights', ['response' => $response]);
            return $this->errorResponse('خطأ في تحليل استجابة الذكاء الاصطناعي', 500);
        }

        // Save session
        ClientAiSession::create([
            'tenant_id' => $client->tenant_id,
            'client_id' => $client->id,
            'user_id' => auth('sanctum')->id() ?? auth()->id(),
            'title' => 'Insights ' . now()->format('Y-m-d'),
            'type' => 'insight',
            'messages' => [
                ['role' => 'system', 'content' => $prompt],
                ['role' => 'assistant', 'content' => json_encode($insightsData, JSON_UNESCAPED_UNICODE)]
            ]
        ]);

        return $this->successResponse([
            'insights' => $insightsData
        ]);
    }

    /**
     * Ask a question (Free Chat or Quick Action).
     */
    public function ask(Request $request, Client $client)
    {
        if (!$this->hasAiFeature($client)) {
            return $this->errorResponse('ميزة الذكاء الاصطناعي غير متاحة في باقتك الحالية', 403);
        }

        $request->validate([
            'question' => 'required|string|max:1000',
            'type' => 'nullable|in:quick_action,free_chat',
            'session_id' => 'nullable|exists:client_ai_sessions,id'
        ]);

        $question = $request->input('question');
        $type = $request->input('type', 'free_chat');
        $sessionId = $request->input('session_id');

        $session = null;
        $messages = [];

        if ($sessionId) {
            $session = ClientAiSession::where('client_id', $client->id)->findOrFail($sessionId);
            $messages = $session->messages;
        } else {
            // Include context only in the first message of a session
            $context = $this->aiService->buildClientContext($client);
            $messages[] = [
                'role' => 'system',
                'content' => "هذه بيانات العميل التي سأسألك عنها:\n" . $context
            ];
        }

        // Add user question
        $messages[] = [
            'role' => 'user',
            'content' => $question
        ];

        // Format for Gemini API (only user/model roles are allowed, we'll format it as a single prompt for simplicity if it's too complex, or map them).
        // For Gemini 2.5 Flash, we can pass history in contents, but our AiService currently takes a single prompt.
        // Let's combine the history into a single prompt context for now to keep AiService simple.
        
        $fullPrompt = "تاريخ المحادثة السابقة:\n";
        foreach ($messages as $msg) {
            if ($msg['role'] === 'system') {
                $fullPrompt .= "بيانات النظام:\n{$msg['content']}\n\n";
            } else {
                $role = $msg['role'] === 'user' ? 'المستخدم' : 'أنت (المساعد)';
                $fullPrompt .= "{$role}: {$msg['content']}\n\n";
            }
        }
        $fullPrompt .= "أجب على السؤال الأخير بناءً على البيانات. السؤال الأخير هو: {$question}";

        $answer = $this->aiService->ask($fullPrompt, [
            "أنت مساعد ذكي لموظفي المبيعات، تتحدث العربية.",
            "يجب أن تكون إجاباتك مختصرة ومفيدة وعملية، بدون مقدمات طويلة."
        ]);

        if (!$answer) {
            return $this->errorResponse('فشل في الاتصال بخدمة الذكاء الاصطناعي', 500);
        }

        // Add answer to messages
        $messages[] = [
            'role' => 'assistant',
            'content' => $answer
        ];

        // Save or update session
        if ($session) {
            $session->update([
                'messages' => $messages,
                'updated_at' => now(),
            ]);
        } else {
            $session = ClientAiSession::create([
                'tenant_id' => $client->tenant_id,
                'client_id' => $client->id,
                'user_id' => auth('sanctum')->id() ?? auth()->id(),
                'title' => mb_substr($question, 0, 50) . '...',
                'type' => $type,
                'messages' => $messages
            ]);
        }

        return $this->successResponse([
            'session_id' => $session->id,
            'answer' => $answer,
            'messages' => $messages
        ]);
    }

    /**
     * Get conversation history.
     */
    public function history(Client $client)
    {
        if (!$this->hasAiFeature($client)) {
            return $this->errorResponse('ميزة الذكاء الاصطناعي غير متاحة في باقتك الحالية', 403);
        }

        $sessions = ClientAiSession::where('client_id', $client->id)
            ->whereIn('type', ['free_chat', 'quick_action'])
            ->with('user:id,name')
            ->orderBy('created_at', 'desc')
            ->get(['id', 'title', 'type', 'created_at', 'user_id']);

        return $this->successResponse([
            'sessions' => $sessions
        ]);
    }

    /**
     * Get specific session messages.
     */
    public function getSession(Client $client, $sessionId)
    {
        if (!$this->hasAiFeature($client)) {
            return $this->errorResponse('ميزة الذكاء الاصطناعي غير متاحة في باقتك الحالية', 403);
        }

        $session = ClientAiSession::where('client_id', $client->id)->findOrFail($sessionId);

        return $this->successResponse([
            'session' => $session
        ]);
    }

    /**
     * Get AI predefined suggestions.
     */
    public function suggestions(\Illuminate\Http\Request $request)
    {
        $tenant = \App\Services\TenantContext::current();
        if ($tenant && !in_array('ai_agent', $tenant->enabled_features)) {
            return $this->errorResponse('ميزة الذكاء الاصطناعي غير متاحة في باقتك الحالية', 403);
        }

        $suggestions = config('ai_suggestions');

        if ($request->has('client_id')) {
            $client = \App\Models\Client::find($request->client_id);
            if ($client && $client->phone) {
                $whatsAppService = app(\App\Services\Integrations\Contracts\WhatsAppServiceInterface::class);
                $threadId = $whatsAppService->findThreadByPhone($client->phone);
                
                if ($threadId) {
                    $suggestions['client_specific'][] = [
                        'id' => 'summarize_whatsapp',
                        'question' => 'تلخيص أحدث محادثة واتساب',
                        'prompt' => 'قم بتلخيص أحدث محادثة واتساب مع العميل', // This text is standard, frontend calls the special route
                        'icon' => 'whatsapp', // Or chat, or generic icon
                        'action' => 'summarize_whatsapp', // special flag for frontend
                        'thread_id' => $threadId
                    ];
                }
            }
        }

        return $this->successResponse([
            'suggestions' => $suggestions
        ]);
    }

    /**
     * Summarize a WhatsApp conversation from the provider.
     */
    public function summarizeWhatsapp(Request $request, Client $client)
    {
        if (!$this->hasAiFeature($client)) {
            return $this->errorResponse('ميزة الذكاء الاصطناعي غير متاحة في باقتك الحالية', 403);
        }

        $request->validate([
            'thread_id' => 'required|string',
            'session_id' => 'nullable|exists:client_ai_sessions,id'
        ]);

        $threadId = $request->input('thread_id');
        $sessionId = $request->input('session_id');

        $whatsAppService = app(\App\Services\Integrations\Contracts\WhatsAppServiceInterface::class);
        $messages = $whatsAppService->getThreadMessages($threadId);

        if (empty($messages)) {
            return $this->errorResponse('لم يتم العثور على رسائل نصية في هذه المحادثة', 404);
        }

        // Format messages for the AI
        $chatText = "هذه رسائل من محادثة واتساب بين العميل وفريق العمل:\n\n";
        
        // Reverse if they come newest first, usually they do from APIs, we want chronological for AI
        // Or if they come oldest first, don't reverse. We'll just loop. Usually chronological is better.
        // Assuming array structure has 'fromMe' and 'body'.
        $formattedMsgs = [];
        foreach ($messages as $msg) {
            $isFromMe = !empty($msg['fromMe']) || !empty($msg['is_from_me']) || ($msg['direction'] ?? '') === 'outbound' || ($msg['type'] ?? '') === 'sent';
            $sender = $isFromMe ? 'الموظف' : 'العميل';
            
            $text = $msg['body'] ?? $msg['text'] ?? $msg['content'] ?? $msg['message'] ?? '';
            
            if (is_array($text)) {
                $text = $text['body'] ?? $text['text'] ?? json_encode($text, JSON_UNESCAPED_UNICODE);
            }

            if (empty($text) && isset($msg['text']['body'])) {
                $text = $msg['text']['body'];
            }
            
            if (!empty($text) && is_string($text)) {
                $formattedMsgs[] = "{$sender}: {$text}";
            }
        }
        
        // Reverse to ensure oldest to newest if the API returns newest first (common pagination)
        if (empty($formattedMsgs)) {
            // Fallback: If we couldn't parse the specific keys, let the AI read the raw JSON
            $chatText .= json_encode($messages, JSON_UNESCAPED_UNICODE);
        } else {
            $chatText .= implode("\n", array_reverse($formattedMsgs));
        }

        $prompt = $chatText . "\n\nالمطلوب:\nقم بقراءة هذه المحادثة بعناية واستخراج ملخص واضح لأهم النقاط التي تمت مناقشتها، والطلبات أو المشاكل، والقرارات المتخذة (إن وجدت).";

        $answer = $this->aiService->ask($prompt, [
            "أنت خبير في المبيعات وخدمة العملاء تتحدث العربية بطلاقة.",
            "مهمتك تلخيص محادثات الواتساب لتوفير وقت الموظف ومساعدته على فهم حالة العميل بسرعة.",
            "استخدم أسلوب النقاط (Bullet points) للتلخيص، وتجنب السرد الطويل الممل."
        ]);

        if (!$answer) {
            return $this->errorResponse('فشل في الاتصال بخدمة الذكاء الاصطناعي', 500);
        }

        $session = null;
        $sessionMessages = [];

        if ($sessionId) {
            $session = ClientAiSession::where('client_id', $client->id)->findOrFail($sessionId);
            $sessionMessages = $session->messages;
        } else {
            $context = $this->aiService->buildClientContext($client);
            $sessionMessages[] = [
                'role' => 'system',
                'content' => "هذه بيانات العميل التي سأسألك عنها:\n" . $context
            ];
        }

        $sessionMessages[] = [
            'role' => 'user',
            'content' => "قم بتلخيص محادثة الواتساب الحالية."
        ];
        
        $sessionMessages[] = [
            'role' => 'assistant',
            'content' => $answer
        ];

        if ($session) {
            $session->update([
                'messages' => $sessionMessages,
                'updated_at' => now(),
            ]);
        } else {
            $session = ClientAiSession::create([
                'tenant_id' => $client->tenant_id,
                'client_id' => $client->id,
                'user_id' => auth('sanctum')->id() ?? auth()->id(),
                'title' => "تلخيص محادثة واتساب",
                'type' => 'quick_action',
                'messages' => $sessionMessages
            ]);
        }

        return $this->successResponse([
            'session_id' => $session->id,
            'answer' => $answer,
            'messages' => $sessionMessages
        ]);
    }
}
