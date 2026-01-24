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
        Schema::create('api_logs', function (Blueprint $table) {
            $table->id();
            
            // Request Information
            $table->string('api_key', 50)->nullable()->index();
            $table->string('endpoint', 255)->index();
            $table->string('method', 10)->default('POST');
            $table->ipAddress('ip_address')->nullable();
            
            // Request Data
            $table->json('request_data')->nullable();
            $table->text('request_headers')->nullable();
            
            // Response Information
            $table->integer('status_code')->index();
            $table->json('response_data')->nullable();
            $table->boolean('success')->default(false)->index();
            
            // Error Information
            $table->string('error_type', 100)->nullable()->index();
            $table->text('error_message')->nullable();
            $table->json('validation_errors')->nullable();
            
            // Performance
            $table->integer('response_time_ms')->nullable(); // in milliseconds
            
            // Additional Info
            $table->string('user_agent', 500)->nullable();
            $table->string('source', 50)->nullable()->index(); // contact_form, landing_page, etc.
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index('created_at');
            $table->index(['api_key', 'created_at']);
            $table->index(['success', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_logs');
    }
};
