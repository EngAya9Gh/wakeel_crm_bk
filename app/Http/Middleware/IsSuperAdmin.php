<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * IsSuperAdmin Middleware
 *
 * Protects the /super/v1/* routes.
 * Only users with is_super_admin = true can access these endpoints.
 * Super admins are NOT subjected to any Tenant scope — they see all data.
 *
 * Note: TenantContext is intentionally NOT set here, which means the
 * BelongsToTenant global scope is bypassed automatically for all queries.
 */
class IsSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصادق. يرجى تسجيل الدخول.',
                'error'   => ['code' => 'UNAUTHENTICATED'],
            ], 401);
        }

        if (!$user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'ممنوع. هذه المنطقة مخصصة لمديري النظام فقط.',
                'error'   => ['code' => 'FORBIDDEN_SUPER_ADMIN_ONLY'],
            ], 403);
        }

        return $next($request);
    }
}
