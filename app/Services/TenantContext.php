<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tenant;
use RuntimeException;

/**
 * TenantContext — Thread-local storage for the current tenant.
 *
 * Acts as a request-scoped singleton that holds the resolved Tenant
 * for the current HTTP request. Both the SetTenantContext middleware
 * (for authenticated routes) and the ValidateApiKey middleware
 * (for public routes) use this to set the context before the
 * BelongsToTenant Global Scope reads it.
 *
 * Usage:
 *   TenantContext::set($tenant);      // In middleware
 *   TenantContext::current();         // In Global Scope / anywhere else
 *   TenantContext::isResolved();      // Guard check
 *   TenantContext::clear();           // After request (auto via DI lifecycle)
 */
class TenantContext
{
    private static ?Tenant $tenant = null;

    /**
     * Set the current tenant for this request.
     */
    public static function set(Tenant $tenant): void
    {
        static::$tenant = $tenant;
    }

    /**
     * Get the current tenant.
     *
     * @throws RuntimeException if no tenant is set.
     */
    public static function current(): Tenant
    {
        if (static::$tenant === null) {
            throw new RuntimeException('Tenant context has not been set for this request.');
        }

        return static::$tenant;
    }

    /**
     * Get the current tenant's ID safely.
     *
     * @throws RuntimeException if no tenant is set.
     */
    public static function id(): int
    {
        return static::current()->id;
    }

    /**
     * Check if the tenant context has been resolved.
     */
    public static function isResolved(): bool
    {
        return static::$tenant !== null;
    }

    /**
     * Clear the tenant context (useful for tests between requests).
     */
    public static function clear(): void
    {
        static::$tenant = null;
    }
}
