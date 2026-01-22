<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Dynamic Settings Tables
        Schema::create('client_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('color')->default('#000000');
            $table->integer('order')->default(0);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('sources', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('behaviors', function (Blueprint $table) {
             $table->id();
             $table->string('name');
             $table->string('color')->nullable();
             $table->timestamps();
        });

        Schema::create('invalid_reasons', function (Blueprint $table) {
             $table->id();
             $table->string('name');
             $table->timestamps();
        });

        Schema::create('regions', function (Blueprint $table) {
             $table->id();
             $table->string('name');
             $table->timestamps();
        });

        Schema::create('cities', function (Blueprint $table) {
             $table->id();
             $table->foreignId('region_id')->constrained()->onDelete('cascade');
             $table->string('name');
             $table->timestamps();
        });

        Schema::create('tags', function (Blueprint $table) {
             $table->id();
             $table->string('name');
             $table->string('color')->nullable();
             $table->timestamps();
        });

        // 2. Clients Table
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->unique();
            $table->string('company')->nullable();
            
            $table->foreignId('region_id')->nullable()->constrained();
            $table->foreignId('city_id')->nullable()->constrained();
            $table->text('address')->nullable();
            
            $table->foreignId('status_id')->constrained('client_statuses');
            $table->enum('priority', ['high', 'medium', 'low'])->default('medium');
            $table->enum('lead_rating', ['hot', 'warm', 'cold'])->nullable();
            $table->foreignId('behavior_id')->nullable()->constrained('behaviors');
            
            $table->foreignId('source_id')->nullable()->constrained('sources');
            $table->enum('source_status', ['valid', 'invalid'])->default('valid');
            $table->foreignId('invalid_reason_id')->nullable()->constrained('invalid_reasons');
            
            $table->foreignId('assigned_to')->nullable()->constrained('users');
            $table->text('exclusion_reason')->nullable();
            
            $table->timestamp('first_contact_at')->nullable();
            $table->timestamp('converted_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });

        // 3. Client Tags Pivot
        Schema::create('client_tag', function (Blueprint $table) {
             $table->foreignId('client_id')->constrained()->onDelete('cascade');
             $table->foreignId('tag_id')->constrained()->onDelete('cascade');
             $table->primary(['client_id', 'tag_id']);
        });

        // 4. Comments
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('content');
            $table->enum('outcome', ['positive', 'neutral', 'negative'])->default('neutral');
            $table->timestamps();
        });

        // 5. Client Files
        Schema::create('client_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('path');
            $table->enum('type', ['contract', 'identity', 'document', 'image'])->default('document');
            $table->unsignedBigInteger('size')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_files');
        Schema::dropIfExists('comments');
        Schema::dropIfExists('client_tag');
        Schema::dropIfExists('clients');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('cities');
        Schema::dropIfExists('regions');
        Schema::dropIfExists('invalid_reasons');
        Schema::dropIfExists('behaviors');
        Schema::dropIfExists('sources');
        Schema::dropIfExists('client_statuses');
    }
};
