<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();

            // Descriptive name for the key (e.g. "Main Website", "Marketing Landing Page")
            $table->string('name');

            // The actual API key — unique and indexed for fast lookup on every request
            $table->string('key')->unique();

            // Revoke without deletion — keeps audit trail intact
            $table->boolean('is_active')->default(true);

            // Helps tenant verify integrations are working correctly
            $table->timestamp('last_used_at')->nullable();

            $table->timestamps();

            // Composite index for the most common query in ValidateApiKey middleware
            $table->index(['key', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_keys');
    }
};
