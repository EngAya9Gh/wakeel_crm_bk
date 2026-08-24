<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add tenant_id to all core tables for full Multi-Tenancy isolation.
 * 
 * Strategy: All data (including settings like statuses, sources, etc.)
 * is fully isolated per tenant (Option B - Full Isolation).
 * 
 * Note: We insert a default tenant (id=1) via seeder first, then
 * existing rows are updated to belong to tenant_id = 1.
 */
return new class extends Migration
{
    /**
     * Tables and whether they get tenant_id with a FK constraint.
     * Cities is dependent on regions (same tenant), so both get it.
     */
    private array $tables = [
        // Core auth
        'users',
        'roles',
        'teams',
        // CRM data
        'clients',
        'invoices',
        'appointments',
        'products',
        // Settings — fully isolated per tenant
        'client_statuses',
        'sources',
        'behaviors',
        'invalid_reasons',
        'regions',
        'cities',
        'tags',
        'invoice_tags',
        'comment_types',
    ];

    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $table->foreignId('tenant_id')
                    ->after('id')
                    ->default(1) // Existing rows belong to the first/default tenant
                    ->constrained('tenants')
                    ->cascadeOnDelete();

                // Add index for performance (queries always filter by tenant_id)
                $table->index('tenant_id');
            });
        }
    }

    public function down(): void
    {
        foreach (array_reverse($this->tables) as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $table->dropForeign(["{$tableName}_tenant_id_foreign"]);
                $table->dropIndex(["{$tableName}_tenant_id_index"]);
                $table->dropColumn('tenant_id');
            });
        }
    }
};
