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

    /**
     * Get automatically generated insights for the client (Lead Score, Summary).
     */
    public function insights(Client $client)
    {
        // First check if we have a recent insight session (last 24 hours)
        $insightSession = ClientAiSession::where('client_id', $client->id)
            ->where('type', 'insight')
            ->where('created_at', '>=', now()->subDay())
            ->latest()
            ->first();

        if ($insightSession) {
            $messages = $insightSession->messages;
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
        $session = ClientAiSession::where('client_id', $client->id)->findOrFail($sessionId);

        return $this->successResponse([
            'session' => $session
        ]);
    }
}
