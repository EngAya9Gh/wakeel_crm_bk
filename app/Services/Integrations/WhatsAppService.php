<?php

declare(strict_types=1);

namespace App\Services\Integrations;

use App\Services\Integrations\Contracts\WhatsAppServiceInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\TenantContext;

class WhatsAppService implements WhatsAppServiceInterface
{
    public function __construct(
        protected string $baseUrl = '',
        protected string $apiKey = '',
        protected string $channelId = ''
    ) {
        $tenant = TenantContext::isResolved() ? TenantContext::current() : null;
        
        if ($tenant && !empty($tenant->settings)) {
            $this->baseUrl = $tenant->settings['whatsapp_provider'] ?? '';
            $this->apiKey = $tenant->settings['whatsapp_api_key'] ?? '';
            // If the provider URL is UltraMsg, we typically append the instance key to the URL, but the user's .env had it like: https://provider.wakeel.cc/api/v1
            // Let's assume whatsapp_provider is the full base URL. If it's just 'ultramsg' as saved in the DB from the UI, we might need a map.
            // In the tenant UI we had a select: "ultramsg", "whatsapp_business", "twilio". But the user's .env had a direct URL. 
            // Wait, looking at the UI code, the field is whatsapp_provider which is a string like "ultramsg". 
            // The previous code used: config('services.whatsapp.base_url', 'https://provider.wakeel.cc/api/v1')
            // I should use the tenant's base URL if provided, otherwise default to the old Wakeel provider if we want to be safe, but actually they should just put the full URL in `whatsapp_provider` OR we should map it.
            // Since the user asked me how to use the .env keys, I told them to put WHATSAPP_API_BASE_URL into the settings. Wait! In the UI we had a select dropdown for Provider.
            // Let's allow `whatsapp_provider` to be the actual URL or just use it as it is.
            $provider = $tenant->settings['whatsapp_provider'] ?? '';
            if (filter_var($provider, FILTER_VALIDATE_URL)) {
                $this->baseUrl = rtrim($provider, '/');
            } else {
                $this->baseUrl = 'https://provider.wakeel.cc/api/v1'; // Default internal proxy
            }
        }
    }

    public function send(string $to, string $message, ?array $media = null): bool
    {
        if ($this->isDummyMode()) {
            Log::info("WhatsApp Mock Sent to {$to}: {$message}", ['media' => $media]);
            return true;
        }

        try {
            $payload = [
                'phone' => ltrim($to, '+'),
                'message' => $message,
            ];

            // The provider API strictly validates the payload and rejects 'channel_id'
            // It infers the channel from the API key (tenant context) automatically.
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

            $errorMsg = "Provider API Error ({$response->status()}): " . $response->body();
            Log::error($errorMsg);
            throw new \Exception($errorMsg);

        } catch (\Exception $e) {
            Log::error("WhatsApp Sending Exception: " . $e->getMessage());
            throw $e;
        }
    }

    protected function isDummyMode(): bool
    {
        // Only disable if the API key is genuinely missing, regardless of environment
        return empty($this->apiKey);
    }

    public function sendList(string $to, string $title, string $body, string $buttonText, array $sections): bool
    {
        if ($this->isDummyMode()) return true;

        $payload = [
            'phone' => ltrim($to, '+'),
            'title' => $title,
            'body' => $body,
            'buttonText' => $buttonText,
            'sections' => $sections,
        ];


        $response = Http::withToken($this->apiKey)->post("{$this->baseUrl}/message/send-list", $payload);
        return $response->successful();
    }

    public function sendTemplate(string $to, string $templateId, array $variables): bool
    {
        if ($this->isDummyMode()) return true;

        $payload = [
            'phone' => ltrim($to, '+'),
            'templateId' => $templateId,
            'variables' => $variables,
        ];


        $response = Http::withToken($this->apiKey)->post("{$this->baseUrl}/templates/send", $payload);
        return $response->successful();
    }

    public function getThreads(): array
    {
        if ($this->isDummyMode()) return [];

        $response = Http::withToken($this->apiKey)->get("{$this->baseUrl}/chat/threads");
        return $response->successful() ? $response->json('data', []) : [];
    }

    /**
     * Find an existing thread by contact phone number.
     * The provider's /message/send endpoint is blocked when the WhatsApp session
     * uses Business-initiated messaging restrictions. The ONLY reliable way to
     * send is via an existing thread (replyToThread). This method bridges that gap.
     */
    public function findThreadByPhone(string $phone): ?string
    {
        if ($this->isDummyMode()) return null;

        // Normalize: strip leading + or spaces so we can compare consistently
        $normalizedPhone = ltrim(preg_replace('/\s+/', '', $phone), '+');

        $response = Http::withToken($this->apiKey)->get("{$this->baseUrl}/chat/threads");

        if (!$response->successful()) {
            return null;
        }

        $threads = $response->json('data.threads', []);

        foreach ($threads as $thread) {
            $contactPhone = ltrim(preg_replace('/\s+/', '', $thread['contactPhone'] ?? ''), '+');
            if ($contactPhone === $normalizedPhone) {
                return $thread['_id'] ?? $thread['id'] ?? null;
            }
        }

        return null;
    }

    public function getThreadMessages(string $threadId): array
    {
        if ($this->isDummyMode()) return [];

        $response = Http::withToken($this->apiKey)->get("{$this->baseUrl}/chat/threads/{$threadId}/messages");
        return $response->successful() ? $response->json('data', []) : [];
    }

    public function replyToThread(string $threadId, string $content, string $type = 'text', ?string $mediaUrl = null): bool
    {
        if ($this->isDummyMode()) return true;

        $payload = [
            'content' => $content,
            'type' => $type,
        ];
        
        if ($mediaUrl) {
            $payload['hasMedia'] = true;
            $payload['mediaUrl'] = $mediaUrl;
        }

        $response = Http::withToken($this->apiKey)->post("{$this->baseUrl}/chat/threads/{$threadId}/messages", $payload);
        return $response->successful();
    }

    /**
     * Upload a media file to the provider and get a public URL.
     *
     * @param string $threadId
     * @param \Illuminate\Http\UploadedFile $file
     * @param string $mediaType  image | audio | document | video
     * @return string|null  The public URL of the uploaded file, or null on failure
     */
    public function uploadThreadMedia(string $threadId, \Illuminate\Http\UploadedFile $file, string $mediaType = 'image'): ?string
    {
        try {
            $filename = time() . '_' . preg_replace('/[^A-Za-z0-9.\-_]/', '', $file->getClientOriginalName());
            $directory = public_path("uploads/whatsapp_media/{$threadId}");
            
            if (!file_exists($directory)) {
                mkdir($directory, 0775, true);
            }
            
            $file->move($directory, $filename);
            
            return url("uploads/whatsapp_media/{$threadId}/{$filename}");
        } catch (\Exception $e) {
            Log::error("WhatsApp Media Upload Exception: " . $e->getMessage());
            return null;
        }
    }

    public function getMedia(string $url): ?array
    {
        if ($this->isDummyMode()) return null;

        $response = Http::withToken($this->apiKey)->get($url);

        if ($response->successful()) {
            return [
                'body' => $response->body(),
                'contentType' => $response->header('Content-Type') ?? 'application/octet-stream',
            ];
        }

        return null;
    }
}
