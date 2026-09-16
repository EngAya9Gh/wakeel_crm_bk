<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            // Drop the old global unique index on phone
            $table->dropUnique('clients_phone_unique');
            // Add the new tenant-scoped unique index
            $table->unique(['tenant_id', 'phone'], 'clients_tenant_id_phone_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropUnique('clients_tenant_id_phone_unique');
            $table->unique('phone', 'clients_phone_unique');
        });
    }
};
