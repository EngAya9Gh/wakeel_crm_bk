<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\ApiKey;
use App\Services\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * API Key Authentication Middleware (Updated for Database-driven Multi-Tenancy)
 *
 * This middleware validates API keys for public endpoints.
 * It now resolves the key from the database, sets the TenantContext,
 * and updates the last_used_at timestamp on the key.
 */
class ValidateApiKey
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Accept key from X-API-Key header, Authorization Bearer, or query param
        $apiKey = $request->header('X-API-Key')
                  ?? $request->bearerToken()
                  ?? $request->input('api_key');

        if (empty($apiKey)) {
            return response()->json([
                'success' => false,
                'message' => 'API Key مطلوب',
                'error'   => [
                    'code'    => 'MISSING_API_KEY',
                    'message' => 'يجب تضمين API Key في الطلب عبر الـ Header: X-API-Key',
                ],
            ], 401);
        }

        // Look up key in the database — eager-load tenant for TenantContext
        $keyRecord = ApiKey::with('tenant')
            ->where('key', $apiKey)
            ->where('is_active', true)
            ->first();

        if (!$keyRecord) {
            return response()->json([
                'success' => false,
                'message' => 'API Key غير صحيح أو غير نشط',
                'error'   => [
                    'code'    => 'INVALID_API_KEY',
                    'message' => 'المفتاح المقدم غير صالح أو تم إيقافه',
                ],
            ], 403);
        }

        // Set tenant context for this request (enables BelongsToTenant scope)
        TenantContext::set($keyRecord->tenant);

        // Update last_used_at without affecting middleware response time significantly
        $keyRecord->update(['last_used_at' => now()]);

        return $next($request);
    }
}
