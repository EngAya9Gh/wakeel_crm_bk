<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    /**
     * Handle incoming webhook requests from WhatsApp Provider.
     */
    public function handle(Request $request): JsonResponse
    {
        $secretKey = config('services.whatsapp.webhook_secret');
        
        // Verify Webhook Key
        $providedKey = $request->header('X-Webhook-Key');
        
        if (!$providedKey || $providedKey !== $secretKey) {
            Log::warning('Unauthorized WhatsApp Webhook attempt', [
                'ip' => $request->ip(),
                'provided_key' => $providedKey
            ]);
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // Process Event
        $event = $request->input('event');
        $data = $request->input('data');
        
        Log::info("WhatsApp Webhook Received: {$event}", ['data' => $data]);
        
        // Dispatch event if it's a message
        if ($event === 'message.incoming') {
            $payload = $data;
            $threadId = $payload['thread_id'] ?? $payload['from'] ?? null;
            
            if ($threadId) {
                \App\Events\NewWhatsAppMessageReceived::dispatch($payload, (string) $threadId);
            }
        }
        
        // Depending on event type (e.g., 'message.incoming' or 'message.status') 
        // we can dispatch jobs or process it directly.
        // TODO: Add further event processing logic here when needed.

        return response()->json(['success' => true]);
    }
}
