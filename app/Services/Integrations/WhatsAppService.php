<?php

declare(strict_types=1);

namespace App\Services\Integrations;

use App\Services\Integrations\Contracts\WhatsAppServiceInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService implements WhatsAppServiceInterface
{
    public function __construct(
        protected string $apiKey = '',
        protected string $phoneNumberId = ''
    ) {
        $this->apiKey = config('services.whatsapp.api_key', 'dummy_whatsapp_key');
        $this->phoneNumberId = config('services.whatsapp.phone_number_id', 'dummy_phone_id');
    }

    public function send(string $to, string $message, ?array $media = null): bool
    {
        if ($this->isDummyMode()) {
            Log::info("WhatsApp Mock Sent to {$to}: {$message}", ['media' => $media]);
            return true;
        }

        try {
            // Example Meta/WhatsApp Cloud API structure
            // $response = Http::withToken($this->apiKey)->post("https://graph.facebook.com/v17.0/{$this->phoneNumberId}/messages", [
            //     'messaging_product' => 'whatsapp',
            //     'to' => $to,
            //     'type' => 'text',
            //     'text' => ['body' => $message],
            // ]);

            // return $response->successful();

            Log::info("WhatsApp Service invoked for {$to}. (Provider implementation pending)");
            return true;
        } catch (\Exception $e) {
            Log::error("WhatsApp Sending Failed: " . $e->getMessage());
            return false;
        }
    }

    protected function isDummyMode(): bool
    {
        return config('app.env') === 'local' || $this->apiKey === 'dummy_whatsapp_key';
    }
}
