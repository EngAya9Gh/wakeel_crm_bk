<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Tenant;

use App\Http\Controllers\Controller;
use App\Models\TenantAiSession;
use App\Services\TenantContext;
use App\Services\AiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TenantAiController extends Controller
{
    use \App\Traits\ApiResponse;

    private AiService $aiService;

    public function __construct(AiService $aiService)
    {
        $this->aiService = $aiService;
    }

    private function hasAiFeature(): bool
    {
        $tenant = TenantContext::get();
        return $tenant && in_array('ai_agent', $tenant->enabled_features ?? []);
    }

    /**
     * Ask a question to the general System-wide AI.
     */
    public function ask(Request $request)
    {
        if (!$this->hasAiFeature()) {
            return $this->errorResponse('ميزة الذكاء الاصطناعي غير متاحة في باقتك الحالية', 403);
        }

        $request->validate([
            'question' => 'required|string|max:1000',
            'type' => 'nullable|string',
            'session_id' => 'nullable|exists:tenant_ai_sessions,id'
        ]);

        $question = $request->input('question');
        $type = $request->input('type', 'general');
        $sessionId = $request->input('session_id');
        $tenant = TenantContext::get();

        $session = null;
        $messages = [];

        if ($sessionId) {
            $session = TenantAiSession::where('tenant_id', $tenant->id)->findOrFail($sessionId);
            $messages = $session->messages;
        } else {
            // Include context only in the first message of a session
            $context = $this->aiService->buildTenantContext($tenant);
            $messages[] = [
                'role' => 'system',
                'content' => "هذه بيانات وإحصائيات النظام الشاملة للشركة التي سأسألك عنها:\n" . $context
            ];
        }

        // Add user question
        $messages[] = [
            'role' => 'user',
            'content' => $question
        ];

        // Format for Gemini API
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
            "أنت المساعد الذكي العام للشركة ومدير المبيعات، تتحدث العربية.",
            "مهمتك تحليل أداء الشركة، وتقديم نصائح، وتلخيص الأرقام.",
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
            $session = TenantAiSession::create([
                'tenant_id' => $tenant->id,
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
     * Get conversation history for general AI.
     */
    public function history()
    {
        if (!$this->hasAiFeature()) {
            return $this->errorResponse('ميزة الذكاء الاصطناعي غير متاحة في باقتك الحالية', 403);
        }

        $tenant = TenantContext::get();
        $sessions = TenantAiSession::where('tenant_id', $tenant->id)
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
    public function getSession($sessionId)
    {
        if (!$this->hasAiFeature()) {
            return $this->errorResponse('ميزة الذكاء الاصطناعي غير متاحة في باقتك الحالية', 403);
        }

        $tenant = TenantContext::get();
        $session = TenantAiSession::where('tenant_id', $tenant->id)->findOrFail($sessionId);

        return $this->successResponse([
            'session' => $session
        ]);
    }
}
