<?php

declare(strict_types=1);

namespace App\Services\Integrations;

use App\Services\Integrations\Contracts\SmsServiceInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService implements SmsServiceInterface
{
    public function __construct(
        protected string $apiKey = '',
        protected string $senderId = '',
        protected string $baseUrl = ''
    ) {
        $this->apiKey = config('services.sms.api_key', 'dummy_key');
        $this->senderId = config('services.sms.sender_id', 'CRM_SYSTEM');
        $this->baseUrl = config('services.sms.base_url', 'https://api.sms-provider.com');
    }

    public function send(string $to, string $message): bool
    {
        // For development/testing or if dummy credentials are present
        if ($this->isDummyMode()) {
            Log::info("SMS Mock Sent to {$to}: {$message}");
            return true;
        }

        try {
            // Example generic HTTP request
            // $response = Http::withToken($this->apiKey)->post("{$this->baseUrl}/send", [
            //     'to' => $to,
            //     'from' => $this->senderId,
            //     'body' => $message,
            // ]);
            
            // return $response->successful();

            Log::info("SMS Service invoked for {$to}. (Provider implementation pending)");
            return true;
        } catch (\Exception $e) {
            Log::error("SMS Sending Failed: " . $e->getMessage());
            return false;
        }
    }

    protected function isDummyMode(): bool
    {
        return config('app.env') === 'local' || $this->apiKey === 'dummy_key';
    }
}
