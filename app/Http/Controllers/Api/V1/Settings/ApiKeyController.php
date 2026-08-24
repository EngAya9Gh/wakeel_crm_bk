<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Settings;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API Key Management Controller
 *
 * Allows tenants to manage their own API keys for website integrations.
 * All operations are automatically scoped to the current tenant via
 * the BelongsToTenant global scope on the ApiKey model.
 */
class ApiKeyController extends Controller
{
    use \App\Traits\ApiResponse;

    /**
     * GET /api/v1/settings/api-keys
     * List all API keys for the current tenant.
     */
    public function index(): JsonResponse
    {
        $keys = ApiKey::orderBy('created_at', 'desc')->get([
            'id', 'name', 'key', 'is_active', 'last_used_at', 'created_at',
        ]);

        return $this->successResponse($keys, 'تم جلب مفاتيح الـ API بنجاح');
    }

    /**
     * POST /api/v1/settings/api-keys
     * Generate a new API key for the current tenant.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $apiKey = ApiKey::create([
            'tenant_id' => TenantContext::id(),
            'name'      => $validated['name'],
            'key'       => ApiKey::generateKey(),
            'is_active' => true,
        ]);

        return $this->createdResponse([
            'id'         => $apiKey->id,
            'name'       => $apiKey->name,
            'key'        => $apiKey->key, // Only shown once at creation
            'is_active'  => $apiKey->is_active,
            'created_at' => $apiKey->created_at,
        ], 'تم إنشاء مفتاح الـ API بنجاح. احفظ المفتاح الآن، لن يُعرض مرة أخرى بالكامل.');
    }

    /**
     * PATCH /api/v1/settings/api-keys/{id}/toggle
     * Toggle the active state of an API key (revoke or re-enable).
     */
    public function toggle(int $id): JsonResponse
    {
        $apiKey = ApiKey::findOrFail($id);
        $apiKey->update(['is_active' => !$apiKey->is_active]);

        $status = $apiKey->is_active ? 'تفعيل' : 'تعطيل';

        return $this->successResponse([
            'id'        => $apiKey->id,
            'name'      => $apiKey->name,
            'is_active' => $apiKey->is_active,
        ], "تم {$status} مفتاح الـ API بنجاح");
    }

    /**
     * DELETE /api/v1/settings/api-keys/{id}
     * Permanently delete an API key.
     */
    public function destroy(int $id): JsonResponse
    {
        $apiKey = ApiKey::findOrFail($id);
        $apiKey->delete();

        return $this->deletedResponse('تم حذف مفتاح الـ API نهائياً');
    }
}
