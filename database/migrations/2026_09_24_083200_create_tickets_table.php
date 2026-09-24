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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('ticket_number')->index();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete()->comment('Created by user');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('ticket_categories')->nullOnDelete();
            
            $table->string('title');
            $table->text('description')->nullable();
            
            $table->string('status')->default('open')->comment('open, in_progress, pending_client, resolved, closed');
            $table->string('priority')->default('medium')->comment('low, medium, high, critical');
            $table->string('source')->default('manual')->comment('manual, whatsapp, email, chat_widget, api, phone');
            
            $table->dateTime('sla_due_at')->nullable();
            $table->dateTime('resolved_at')->nullable();
            $table->dateTime('closed_at')->nullable();
            
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
