<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique()->comment('Unique identifier used for subdomain or reference');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('logo')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('settings')->nullable()->comment('Flexible key-value store for tenant-specific configurations');
            $table->string('plan')->default('basic')->comment('Subscription plan: basic, pro, enterprise');
            $table->timestamps();
        });

        // Insert default tenant immediately so that existing data can safely point to tenant_id = 1
        // in the next migration without violating foreign key constraints.
        DB::table('tenants')->insert([
            'id' => 1,
            'name' => 'الحساب الافتراضي',
            'slug' => 'default',
            'email' => 'admin@wakeel.crm',
            'is_active' => true,
            'plan' => 'pro',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
