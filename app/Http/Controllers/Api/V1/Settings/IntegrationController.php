<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Settings;

use App\Http\Controllers\Controller;
use App\Models\TenantIntegration;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class IntegrationController extends Controller
{
    public function index(Request $request)
    {
        $tenant = $request->user()->tenant;

        // Ensure tenant has a webhook_token
        if (!$tenant->webhook_token) {
            $tenant->webhook_token = Str::uuid()->toString();
            $tenant->save();
        }

        $integrations = $tenant->integrations()->get()->keyBy('platform');

        $baseUrl = config('app.url');

        return response()->json([
            'webhook_token' => $tenant->webhook_token,
            'webhook_urls' => [
                'meta' => "{$baseUrl}/api/webhooks/meta/{$tenant->webhook_token}",
                'tiktok' => "{$baseUrl}/api/webhooks/tiktok/{$tenant->webhook_token}",
            ],
            'integrations' => [
                'meta' => [
                    'is_active' => $integrations->has('meta') ? $integrations['meta']->is_active : false,
                    'has_credentials' => $integrations->has('meta') && !empty($integrations['meta']->credentials),
                ],
                'tiktok' => [
                    'is_active' => $integrations->has('tiktok') ? $integrations['tiktok']->is_active : false,
                    'has_credentials' => $integrations->has('tiktok') && !empty($integrations['tiktok']->credentials),
                ],
            ]
        ]);
    }

    public function update(Request $request, string $platform)
    {
        $tenant = $request->user()->tenant;
        
        $validated = $request->validate([
            'is_active' => 'required|boolean',
            'credentials' => 'nullable|array',
        ]);

        $integration = TenantIntegration::updateOrCreate(
            ['tenant_id' => $tenant->id, 'platform' => $platform],
            [
                'is_active' => $validated['is_active'],
            ]
        );

        if (isset($validated['credentials'])) {
            $integration->credentials = $validated['credentials'];
            $integration->save();
        }

        return response()->json([
            'message' => 'Integration updated successfully',
            'data' => [
                'platform' => $platform,
                'is_active' => $integration->is_active,
            ]
        ]);
    }
}
