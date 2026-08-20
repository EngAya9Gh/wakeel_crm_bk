<?php

declare(strict_types=1);

namespace App\Services\Integrations;

use App\Services\Integrations\Contracts\WhatsAppServiceInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService implements WhatsAppServiceInterface
{
    public function __construct(
        protected string $baseUrl = '',
        protected string $apiKey = '',
        protected string $channelId = ''
    ) {
        $this->baseUrl = config('services.whatsapp.base_url', 'https://provider.wakeel.cc/api/v1');
        $this->apiKey = config('services.whatsapp.api_key', '');
        $this->channelId = config('services.whatsapp.channel_id', '');
    }

    public function send(string $to, string $message, ?array $media = null): bool
    {
        if ($this->isDummyMode()) {
            Log::info("WhatsApp Mock Sent to {$to}: {$message}", ['media' => $media]);
            return true;
        }

        try {
            $payload = [
                'phone' => $to,
            ];

            if (!empty($this->channelId)) {
                $payload['channel_id'] = $this->channelId;
            }

            if ($media && isset($media['url']) && isset($media['type'])) {
                // Media message
                $endpoint = "{$this->baseUrl}/message/send-media";
                $payload['url'] = $media['url'];
                $payload['type'] = $media['type'];
                $payload['caption'] = $message;
            } else {
                // Text message
                $endpoint = "{$this->baseUrl}/message/send";
                $payload['message'] = $message;
            }

            $response = Http::withToken($this->apiKey)
                ->post($endpoint, $payload);

            if ($response->successful()) {
                Log::info("WhatsApp Message Sent successfully to {$to}");
                return true;
            }

            Log::error("WhatsApp Sending Failed (HTTP Status {$response->status()}): " . $response->body());
            return false;

        } catch (\Exception $e) {
            Log::error("WhatsApp Sending Exception: " . $e->getMessage());
            return false;
        }
    }

    protected function isDummyMode(): bool
    {
        return config('app.env') === 'local' && empty($this->apiKey);
    }

    public function sendList(string $to, string $title, string $body, string $buttonText, array $sections): bool
    {
        if ($this->isDummyMode()) return true;

        $payload = [
            'phone' => $to,
            'title' => $title,
            'body' => $body,
            'buttonText' => $buttonText,
            'sections' => $sections,
        ];
        if (!empty($this->channelId)) $payload['channel_id'] = $this->channelId;

        $response = Http::withToken($this->apiKey)->post("{$this->baseUrl}/message/send-list", $payload);
        return $response->successful();
    }

    public function sendTemplate(string $to, string $templateId, array $variables): bool
    {
        if ($this->isDummyMode()) return true;

        $payload = [
            'phone' => $to,
            'templateId' => $templateId,
            'variables' => $variables,
        ];
        if (!empty($this->channelId)) $payload['channel_id'] = $this->channelId;

        $response = Http::withToken($this->apiKey)->post("{$this->baseUrl}/templates/send", $payload);
        return $response->successful();
    }

    public function getThreads(): array
    {
        if ($this->isDummyMode()) return [];

        $response = Http::withToken($this->apiKey)->get("{$this->baseUrl}/chat/threads");
        return $response->successful() ? $response->json('data', []) : [];
    }

    public function getThreadMessages(string $threadId): array
    {
        if ($this->isDummyMode()) return [];

        $response = Http::withToken($this->apiKey)->get("{$this->baseUrl}/chat/threads/{$threadId}/messages");
        return $response->successful() ? $response->json('data', []) : [];
    }

    public function replyToThread(string $threadId, string $content, string $type = 'text'): bool
    {
        if ($this->isDummyMode()) return true;

        $payload = [
            'content' => $content,
            'type' => $type,
        ];

        $response = Http::withToken($this->apiKey)->post("{$this->baseUrl}/chat/threads/{$threadId}/messages", $payload);
        return $response->successful();
    }
}
