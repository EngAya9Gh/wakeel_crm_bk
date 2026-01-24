<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\ApiLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * API Request Logger Middleware
 * 
 * Logs all Public API requests for monitoring and debugging
 */
class LogApiRequests
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        
        // Process the request
        $response = $next($request);
        
        // Calculate response time
        $responseTime = (microtime(true) - $startTime) * 1000; // in milliseconds
        
        // Log the request asynchronously (non-blocking)
        $this->logRequest($request, $response, $responseTime);
        
        return $response;
    }

    /**
     * Log the API request
     */
    protected function logRequest(Request $request, Response $response, float $responseTime): void
    {
        try {
            $responseData = json_decode($response->getContent(), true);
            $requestData = $request->all();
            
            // Mask sensitive data
            if (isset($requestData['phone'])) {
                $requestData['phone_masked'] = $this->maskPhone($requestData['phone']);
            }
            
            ApiLog::create([
                'api_key' => $this->maskApiKey($request->header('X-API-Key')),
                'endpoint' => $request->path(),
                'method' => $request->method(),
                'ip_address' => $request->ip(),
                'request_data' => $requestData,
                'request_headers' => $this->getRelevantHeaders($request),
                'status_code' => $response->getStatusCode(),
                'response_data' => $responseData,
                'success' => $response->isSuccessful(),
                'error_type' => $this->getErrorType($response, $responseData),
                'error_message' => $this->getErrorMessage($responseData),
                'validation_errors' => $this->getValidationErrors($responseData),
                'response_time_ms' => (int) round($responseTime),
                'user_agent' => $request->userAgent(),
                'source' => $requestData['source'] ?? null,
            ]);
        } catch (\Exception $e) {
            // Don't let logging errors break the API
            \Log::error('Failed to log API request', [
                'error' => $e->getMessage(),
                'endpoint' => $request->path(),
            ]);
        }
    }

    /**
     * Mask API Key for security (show only last 8 characters)
     */
    protected function maskApiKey(?string $apiKey): ?string
    {
        if (!$apiKey || strlen($apiKey) < 8) {
            return $apiKey;
        }
        
        return '***' . substr($apiKey, -8);
    }

    /**
     * Mask phone number for privacy
     */
    protected function maskPhone(string $phone): string
    {
        if (strlen($phone) < 4) {
            return $phone;
        }
        
        return substr($phone, 0, 4) . '****' . substr($phone, -2);
    }

    /**
     * Get relevant headers (exclude sensitive ones)
     */
    protected function getRelevantHeaders(Request $request): string
    {
        $headers = $request->headers->all();
        
        // Remove sensitive headers
        unset($headers['x-api-key'], $headers['authorization']);
        
        return json_encode([
            'content-type' => $headers['content-type'] ?? null,
            'accept' => $headers['accept'] ?? null,
            'origin' => $headers['origin'] ?? null,
            'referer' => $headers['referer'] ?? null,
        ]);
    }

    /**
     * Determine error type from response
     */
    protected function getErrorType(Response $response, ?array $responseData): ?string
    {
        if ($response->isSuccessful()) {
            return null;
        }
        
        return match ($response->getStatusCode()) {
            401 => 'MISSING_API_KEY',
            403 => 'INVALID_API_KEY',
            422 => 'VALIDATION_ERROR',
            429 => 'RATE_LIMIT_EXCEEDED',
            500 => 'SERVER_ERROR',
            default => 'UNKNOWN_ERROR',
        };
    }

    /**
     * Extract error message from response
     */
    protected function getErrorMessage(?array $responseData): ?string
    {
        if (!$responseData) {
            return null;
        }
        
        return $responseData['message'] 
            ?? $responseData['error']['message'] 
            ?? null;
    }

    /**
     * Extract validation errors from response
     */
    protected function getValidationErrors(?array $responseData): ?array
    {
        if (!$responseData || !isset($responseData['errors'])) {
            return null;
        }
        
        return $responseData['errors'];
    }
}
