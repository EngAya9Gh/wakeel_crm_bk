<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * SetTenantContext Middleware
 *
 * Runs after Sanctum authentication on all protected routes.
 * Loads the Tenant from the authenticated user and populates
 * the TenantContext — which the BelongsToTenant trait reads
 * to transparently scope all Eloquent queries.
 */
class SetTenantContext
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        if (!$user || !$user->tenant_id) {
            return response()->json([
                'success' => false,
                'message' => 'هذا المستخدم غير مرتبط بأي مستأجر (Tenant). يرجى التواصل مع المشرف.',
                'error'   => ['code' => 'TENANT_NOT_FOUND'],
            ], 403);
        }

        $tenant = $user->tenant;

        if (!$tenant || !$tenant->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'حساب المستأجر غير نشط. يرجى التواصل مع الدعم الفني.',
                'error'   => ['code' => 'TENANT_INACTIVE'],
            ], 403);
        }

        TenantContext::set($tenant);

        return $next($request);
    }
}
