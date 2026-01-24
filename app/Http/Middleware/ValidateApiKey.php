<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * API Key Authentication Middleware
 * 
 * This middleware validates API keys for public endpoints.
 * It checks for the API key in the Authorization header or X-API-Key header.
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
        // Get API key from headers
        $apiKey = $request->header('X-API-Key') 
                  ?? $request->bearerToken() 
                  ?? $request->input('api_key');
        
        // Check if API key is provided
        if (empty($apiKey)) {
            return response()->json([
                'success' => false,
                'message' => 'API Key مطلوب',
                'error' => [
                    'code' => 'MISSING_API_KEY',
                    'message' => 'يجب تضمين API Key في الطلب'
                ]
            ], 401);
        }
        
        // Validate API key
        $validApiKeys = config('services.api_keys.public', []);
        
        if (!in_array($apiKey, $validApiKeys, true)) {
            return response()->json([
                'success' => false,
                'message' => 'API Key غير صحيح',
                'error' => [
                    'code' => 'INVALID_API_KEY',
                    'message' => 'المفتاح المقدم غير صالح'
                ]
            ], 403);
        }
        
        // API key is valid, proceed with the request
        return $next($request);
    }
}
