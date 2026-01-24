<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiLog extends Model
{
    protected $fillable = [
        'api_key',
        'endpoint',
        'method',
        'ip_address',
        'request_data',
        'request_headers',
        'status_code',
        'response_data',
        'success',
        'error_type',
        'error_message',
        'validation_errors',
        'response_time_ms',
        'user_agent',
        'source',
    ];

    protected $casts = [
        'request_data' => 'array',
        'response_data' => 'array',
        'validation_errors' => 'array',
        'success' => 'boolean',
        'response_time_ms' => 'integer',
        'status_code' => 'integer',
    ];

    /**
     * Scope: Failed requests only
     */
    public function scopeFailed($query)
    {
        return $query->where('success', false);
    }

    /**
     * Scope: Successful requests only
     */
    public function scopeSuccessful($query)
    {
        return $query->where('success', true);
    }

    /**
     * Scope: By API Key
     */
    public function scopeByApiKey($query, string $apiKey)
    {
        return $query->where('api_key', $apiKey);
    }

    /**
     * Scope: By date range
     */
    public function scopeDateRange($query, $from, $to)
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }

    /**
     * Scope: Rate limit errors
     */
    public function scopeRateLimitErrors($query)
    {
        return $query->where('status_code', 429);
    }

    /**
     * Scope: Validation errors
     */
    public function scopeValidationErrors($query)
    {
        return $query->where('status_code', 422);
    }
}
