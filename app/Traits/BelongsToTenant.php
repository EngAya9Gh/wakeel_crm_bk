<?php

declare(strict_types=1);

namespace App\Traits;

use App\Services\TenantContext;
use Illuminate\Database\Eloquent\Builder;

/**
 * BelongsToTenant — Multi-Tenancy enforcement via Eloquent Global Scope.
 *
 * Add this trait to any Model that has a `tenant_id` column to get:
 *  1. Automatic query filtering: every query is scoped to the current tenant.
 *  2. Automatic tenant assignment: every create() fills `tenant_id` automatically.
 *
 * Usage:
 *   class Client extends Model {
 *       use BelongsToTenant;
 *   }
 *
 * To temporarily bypass the scope (e.g. for admin commands):
 *   Client::withoutGlobalScope('tenant')->get();
 */
trait BelongsToTenant
{
    /**
     * Boot the trait — registers the Global Scope and the creating listener.
     */
    public static function bootBelongsToTenant(): void
    {
        // 1. Global Scope: restrict all queries to the current tenant
        static::addGlobalScope('tenant', function (Builder $query) {
            // Only apply the scope if a tenant context has been set.
            // This prevents errors during migrations, seeders, console commands,
            // AND when the authenticated user is a Super Admin (no tenant_id).
            if (TenantContext::isResolved()) {
                $query->where(
                    static::qualifyColumn('tenant_id'),
                    TenantContext::id()
                );
            }
        });

        // 2. Creating listener: auto-fill tenant_id on every new record
        static::creating(function ($model) {
            if (empty($model->tenant_id) && TenantContext::isResolved()) {
                $model->tenant_id = TenantContext::id();
            }
        });
    }

    /**
     * Define the belongsTo relationship to the Tenant model.
     */
    public function tenant()
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }
}
