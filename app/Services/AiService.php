<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiService
{
    private string $apiKey;
    private string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models';

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key', '');
    }

    /**
     * Send a prompt to Gemini API and get the response text.
     */
    public function ask(string $prompt, array $systemInstruction = []): ?string
    {
        if (empty($this->apiKey)) {
            Log::warning('Gemini API key is not configured.');
            return null;
        }

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.7,
            ]
        ];

        if (!empty($systemInstruction)) {
            $payload['systemInstruction'] = [
                'parts' => [
                    ['text' => implode("\n", $systemInstruction)]
                ]
            ];
        }

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/gemini-2.5-flash:generateContent?key={$this->apiKey}", $payload);

            if ($response->successful()) {
                $data = $response->json();
                return $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
            }

            Log::error('Gemini API Error', ['response' => $response->body()]);
            return null;
        } catch (\Exception $e) {
            Log::error('Gemini API Exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Build context for a specific client to send to the AI.
     */
    public function buildClientContext(\App\Models\Client $client): string
    {
        $client->loadMissing(['status', 'source', 'assignedTo', 'comments.user', 'invoices', 'timeline.user']);

        $context = [
            'client_info' => [
                'name' => $client->name,
                'phone' => $client->phone,
                'status' => $client->status?->name,
                'source' => $client->source?->name,
                'assigned_to' => $client->assignedTo?->name,
                'created_at' => $client->created_at?->format('Y-m-d'),
            ],
            'recent_comments' => $client->comments->take(10)->map(function ($comment) {
                return [
                    'date' => $comment->created_at?->format('Y-m-d H:i'),
                    'user' => $comment->user?->name,
                    'content' => $comment->content,
                ];
            }),
            'recent_timeline_events' => $client->timeline->take(10)->map(function ($event) {
                return [
                    'date' => $event->created_at?->format('Y-m-d H:i'),
                    'user' => $event->user?->name,
                    'action' => $event->action, // The automated action (e.g., status changed, whatsapp sent)
                ];
            }),
            'invoices' => $client->invoices->map(function ($invoice) {
                return [
                    'number' => $invoice->invoice_number,
                    'total' => $invoice->total,
                    'status' => $invoice->status,
                    'date' => $invoice->created_at?->format('Y-m-d'),
                ];
            })
        ];

        return json_encode($context, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
}
