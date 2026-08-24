<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add is_super_admin flag to users table.
 * 
 * Super Admin users:
 * - Have is_super_admin = true
 * - Have tenant_id = null (not tied to any tenant)
 * - Can manage ALL tenants in the system
 * - Access system via /super/v1 routes with IsSuperAdmin middleware
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Make tenant_id nullable for super admin users
            $table->dropForeign(['tenant_id']);
            $table->foreignId('tenant_id')->nullable()->change()->constrained('tenants')->cascadeOnDelete();

            // Super admin flag — placed after tenant_id
            $table->boolean('is_super_admin')->default(false)->after('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_super_admin');

            // Restore non-nullable tenant_id
            $table->dropForeign(['tenant_id']);
            $table->foreignId('tenant_id')->nullable(false)->change()->constrained('tenants')->cascadeOnDelete();
        });
    }
};
