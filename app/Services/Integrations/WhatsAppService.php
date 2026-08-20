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
}
