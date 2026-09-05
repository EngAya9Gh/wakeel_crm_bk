<?php

declare(strict_types=1);

namespace App\Services\Integrations;

use App\Models\Client;
use App\Models\ClientStatus;
use App\Models\Source;
use App\Models\Tenant;
use App\Models\WebhookLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LeadIntegrationService
{
    public function logWebhook(Tenant $tenant, string $platform, array $payload, string $status = 'success', ?string $errorMessage = null): WebhookLog
    {
        return WebhookLog::create([
            'tenant_id' => $tenant->id,
            'platform' => $platform,
            'payload' => $payload,
            'status' => $status,
            'error_message' => $errorMessage,
        ]);
    }

    public function handleMetaWebhook(Tenant $tenant, array $payload): void
    {
        try {
            // Check if this is a leadgen event
            if (!isset($payload['entry'][0]['changes'][0]['value']['leadgen_id'])) {
                $this->logWebhook($tenant, 'meta', $payload, 'failed', 'Missing leadgen_id in payload');
                return;
            }

            $leadgenId = $payload['entry'][0]['changes'][0]['value']['leadgen_id'];
            
            // Get tenant's Meta integration credentials
            $integration = $tenant->integrations()->where('platform', 'meta')->where('is_active', true)->first();
            
            if (!$integration || empty($integration->credentials['access_token'])) {
                $this->logWebhook($tenant, 'meta', $payload, 'failed', 'Tenant missing active Meta integration or access token');
                return;
            }

            $accessToken = $integration->credentials['access_token'];

            // Fetch lead details from Graph API
            $response = Http::get("https://graph.facebook.com/v20.0/{$leadgenId}", [
                'access_token' => $accessToken,
            ]);

            if ($response->failed()) {
                $this->logWebhook($tenant, 'meta', $payload, 'failed', 'Graph API error: ' . $response->body());
                return;
            }

            $leadData = $response->json();
            
            $this->processLeadData($tenant, 'meta', $payload, $leadData);

        } catch (\Exception $e) {
            Log::error('Meta Webhook Error: ' . $e->getMessage());
            $this->logWebhook($tenant, 'meta', $payload, 'failed', $e->getMessage());
        }
    }

    public function handleTikTokWebhook(Tenant $tenant, array $payload): void
    {
        try {
            // TikTok sends data in the payload directly (standard webhook format)
            // Assuming TikTok payload structure provides lead data in 'data' array
            $this->processLeadData($tenant, 'tiktok', $payload, $payload);
        } catch (\Exception $e) {
            Log::error('TikTok Webhook Error: ' . $e->getMessage());
            $this->logWebhook($tenant, 'tiktok', $payload, 'failed', $e->getMessage());
        }
    }

    private function processLeadData(Tenant $tenant, string $platform, array $rawPayload, array $leadData): void
    {
        // 1. Extract Fields
        $name = 'Unknown Lead';
        $email = null;
        $phone = null;
        $sourceName = $platform === 'meta' ? 'فيسبوك' : 'تيك توك'; // Default

        if ($platform === 'meta') {
            // Meta Graph API returns field_data as an array of objects
            if (isset($leadData['field_data'])) {
                foreach ($leadData['field_data'] as $field) {
                    if ($field['name'] === 'full_name' || $field['name'] === 'name') {
                        $name = $field['values'][0] ?? $name;
                    }
                    if ($field['name'] === 'email') {
                        $email = $field['values'][0] ?? null;
                    }
                    if ($field['name'] === 'phone_number') {
                        $phone = $field['values'][0] ?? null;
                    }
                }
            }
            
            // Determine Facebook vs Instagram
            if (isset($leadData['platform']) && strtolower($leadData['platform']) === 'ig') {
                $sourceName = 'إنستغرام';
            }
        } else if ($platform === 'tiktok') {
            // TikTok format extraction (simplified)
            $name = $leadData['full_name'] ?? $leadData['name'] ?? 'TikTok Lead';
            $email = $leadData['email'] ?? null;
            $phone = $leadData['phone_number'] ?? $leadData['phone'] ?? null;
        }

        // Clean phone number (remove spaces, plus, etc.)
        if ($phone) {
            $phone = preg_replace('/[^0-9]/', '', $phone);
        }

        if (!$phone) {
            $this->logWebhook($tenant, $platform, $rawPayload, 'failed', 'Missing phone number in lead data');
            return;
        }

        // 2. Duplicate Check
        // In a multi-tenant system, checking the tenant's clients
        $existingClient = Client::where('tenant_id', $tenant->id)
            ->where('phone', $phone)
            ->first();

        if ($existingClient) {
            $this->logWebhook($tenant, $platform, $rawPayload, 'duplicate', "Duplicate phone number: {$phone}");
            return;
        }

        // 3. Find Source & Status
        $source = Source::where('name', $sourceName)->first();
        $sourceId = $source ? $source->id : null;

        $defaultStatus = ClientStatus::where('is_default', true)->first();
        
        // 4. Create Client
        Client::create([
            'tenant_id' => $tenant->id,
            'name' => $name,
            'phone' => $phone,
            'email' => $email,
            'status_id' => $defaultStatus ? $defaultStatus->id : 1, // Fallback to 1
            'source_id' => $sourceId,
            'priority' => 'medium',
        ]);

        $this->logWebhook($tenant, $platform, $rawPayload, 'success');
    }
}
