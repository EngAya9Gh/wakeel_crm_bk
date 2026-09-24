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
        Schema::create('evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete()->comment('User who submitted');
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete()->comment('User being evaluated');
            $table->foreignId('ticket_id')->nullable()->constrained('tickets')->nullOnDelete();
            
            $table->tinyInteger('rating')->unsigned();
            $table->text('notes')->nullable();
            
            $table->string('channel')->default('link')->comment('manual, link, whatsapp, chat_widget, api');
            $table->json('metadata')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluations');
    }
};
