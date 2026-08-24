<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Super;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Models\ApiKey;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use App\Services\Tenants\TenantSetupService;

/**
 * Tenant CRUD Controller (Super Admin only)
 * Full management of all tenants in the system.
 */
class TenantController extends Controller
{
    use \App\Traits\ApiResponse;

    /**
     * GET /super/v1/tenants
     * List all tenants with summary stats.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Tenant::withCount(['users', 'clients', 'apiKeys']);

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('slug', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%");
            });
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->filled('plan')) {
            $query->where('plan', $request->plan);
        }

        $tenants = $query->orderBy('created_at', 'desc')->paginate(15);

        return $this->successResponse($tenants, 'قائمة المستأجرين');
    }

    /**
     * GET /super/v1/tenants/{id}
     * Full details of a single tenant.
     */
    public function show(int $id): JsonResponse
    {
        $tenant = Tenant::withCount(['users', 'clients', 'invoices', 'appointments', 'apiKeys'])
            ->findOrFail($id);

        return $this->successResponse($tenant);
    }

    /**
     * POST /super/v1/tenants
     * Create a new tenant + optional first admin user.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'                    => 'required|string|max:255',
            'slug'                    => 'required|string|max:100|unique:tenants,slug|regex:/^[a-z0-9\-]+$/',
            'email'                   => 'nullable|email|max:255',
            'phone'                   => 'nullable|string|max:20',
            'plan'                    => ['required', Rule::in(['basic', 'pro', 'enterprise'])],
            'is_active'               => 'boolean',
            // Required: create admin user for this tenant
            'admin_name'              => 'required|string|max:255',
            'admin_email'             => 'required|email|unique:users,email',
            'admin_password'          => 'required|string|min:8',
        ]);

        return DB::transaction(function () use ($validated) {
            $tenant = Tenant::create([
                'name'      => $validated['name'],
                'slug'      => $validated['slug'],
                'email'     => $validated['email'] ?? null,
                'phone'     => $validated['phone'] ?? null,
                'plan'      => $validated['plan'],
                'is_active' => $validated['is_active'] ?? true,
            ]);

            // Delegate initialization to TenantSetupService
            $setupService = app(TenantSetupService::class);
            $adminUser = $setupService->setupNewTenant(
                $tenant,
                $validated['admin_name'],
                $validated['admin_email'],
                $validated['admin_password']
            );

            return $this->createdResponse([
                'tenant'     => $tenant,
                'admin_user' => $adminUser,
            ], 'تم إنشاء المستأجر بنجاح');
        });
    }

    /**
     * PATCH /super/v1/tenants/{id}
     * Update tenant info and settings.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $tenant = Tenant::findOrFail($id);

        $validated = $request->validate([
            'name'      => 'sometimes|string|max:255',
            'slug'      => ['sometimes', 'string', 'max:100', 'regex:/^[a-z0-9\-]+$/', Rule::unique('tenants', 'slug')->ignore($tenant->id)],
            'email'     => 'nullable|email|max:255',
            'phone'     => 'nullable|string|max:20',
            'plan'      => ['sometimes', Rule::in(['basic', 'pro', 'enterprise'])],
            'is_active' => 'boolean',
            'settings'  => 'nullable|array',
            // WhatsApp / messaging provider settings (stored in settings JSON)
            'settings.whatsapp_provider'       => 'nullable|string',
            'settings.whatsapp_api_key'        => 'nullable|string',
            'settings.whatsapp_phone_number'   => 'nullable|string',
            'settings.whatsapp_webhook_secret' => 'nullable|string',
        ]);

        $tenant->update($validated);

        return $this->successResponse($tenant->fresh(), 'تم تحديث بيانات المستأجر');
    }

    /**
     * PATCH /super/v1/tenants/{id}/toggle
     * Toggle active status of a tenant.
     */
    public function toggle(int $id): JsonResponse
    {
        $tenant = Tenant::findOrFail($id);
        $tenant->update(['is_active' => !$tenant->is_active]);

        $status = $tenant->is_active ? 'تفعيل' : 'تعطيل';

        return $this->successResponse([
            'id'        => $tenant->id,
            'is_active' => $tenant->is_active,
        ], "تم {$status} حساب المستأجر");
    }

    /**
     * DELETE /super/v1/tenants/{id}
     * Permanently delete a tenant and ALL its data.
     */
    public function destroy(int $id): JsonResponse
    {
        $tenant = Tenant::findOrFail($id);

        // Safety check — prevent deleting the default tenant
        if ($tenant->slug === 'default') {
            return $this->errorResponse('لا يمكن حذف المستأجر الافتراضي للنظام');
        }

        $tenant->delete();

        return $this->deletedResponse('تم حذف المستأجر وجميع بياناته نهائياً');
    }

    // ===================== API Keys (per tenant) =====================

    /**
     * GET /super/v1/tenants/{id}/api-keys
     */
    public function apiKeys(int $id): JsonResponse
    {
        $tenant = Tenant::findOrFail($id);
        $keys   = ApiKey::where('tenant_id', $tenant->id)->orderBy('created_at', 'desc')->get();

        return $this->successResponse([
            'tenant'   => ['id' => $tenant->id, 'name' => $tenant->name],
            'api_keys' => $keys,
        ]);
    }

    /**
     * POST /super/v1/tenants/{id}/api-keys
     */
    public function storeApiKey(Request $request, int $id): JsonResponse
    {
        $tenant    = Tenant::findOrFail($id);
        $validated = $request->validate(['name' => 'required|string|max:255']);

        $apiKey = ApiKey::create([
            'tenant_id' => $tenant->id,
            'name'      => $validated['name'],
            'key'       => ApiKey::generateKey(),
            'is_active' => true,
        ]);

        return $this->createdResponse([
            'id'         => $apiKey->id,
            'name'       => $apiKey->name,
            'key'        => $apiKey->key,
            'is_active'  => $apiKey->is_active,
            'created_at' => $apiKey->created_at,
        ], 'تم إنشاء مفتاح الربط. احفظه الآن، لن يُعرض مرة أخرى بالكامل.');
    }

    /**
     * PATCH /super/v1/tenants/{id}/api-keys/{keyId}/toggle
     */
    public function toggleApiKey(int $id, int $keyId): JsonResponse
    {
        $apiKey = ApiKey::where('tenant_id', $id)->findOrFail($keyId);
        $apiKey->update(['is_active' => !$apiKey->is_active]);

        return $this->successResponse($apiKey, $apiKey->is_active ? 'تم تفعيل المفتاح' : 'تم تعطيل المفتاح');
    }

    /**
     * DELETE /super/v1/tenants/{id}/api-keys/{keyId}
     */
    public function destroyApiKey(int $id, int $keyId): JsonResponse
    {
        $apiKey = ApiKey::where('tenant_id', $id)->findOrFail($keyId);
        $apiKey->delete();

        return $this->deletedResponse('تم حذف مفتاح الربط نهائياً');
    }

    // ===================== Users (per tenant) =====================

    /**
     * GET /super/v1/tenants/{id}/users
     */
    public function users(int $id): JsonResponse
    {
        $tenant = Tenant::findOrFail($id);
        $users  = User::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenant->id)
            ->with(['role', 'team'])
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->successResponse([
            'tenant' => ['id' => $tenant->id, 'name' => $tenant->name],
            'users'  => $users,
        ]);
    }
}
