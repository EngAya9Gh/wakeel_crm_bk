<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Products
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('sku')->unique()->nullable();
            $table->decimal('unit_price', 10, 2);
            $table->string('unit')->default('piece'); // piece, hour, month
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Invoice Tags
        Schema::create('invoice_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('color')->nullable();
            $table->timestamps();
        });

        // 3. Invoices
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Creator
            $table->string('invoice_number')->unique();
            
            $table->decimal('subtotal', 10, 2);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            
            $table->enum('status', ['draft', 'sent', 'paid', 'overdue', 'cancelled'])->default('draft');
            $table->date('due_date')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });

        // 4. Invoice Items
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->nullable()->constrained()->onDelete('set null');
            $table->string('description');
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->timestamps();
        });

        // 5. Invoice Tag Pivot
        Schema::create('invoice_tag', function (Blueprint $table) {
             $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
             $table->foreignId('tag_id')->constrained('invoice_tags')->onDelete('cascade');
             $table->primary(['invoice_id', 'tag_id']);
        });

        // 6. Appointments
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Creator/Assignee
            $table->string('title');
            $table->text('description')->nullable();
            
            $table->dateTime('start_at');
            $table->dateTime('end_at')->nullable();
            
            $table->string('location')->nullable();
            $table->enum('type', ['meeting', 'call', 'visit'])->default('meeting');
            $table->enum('status', ['scheduled', 'completed', 'cancelled', 'no_show'])->default('scheduled');
            
            $table->dateTime('reminder_at')->nullable();
            $table->timestamps();
        });

        // 7. Timeline
        Schema::create('client_timeline', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('event_type'); // status_changed, etc.
            $table->string('description');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_timeline');
        Schema::dropIfExists('appointments');
        Schema::dropIfExists('invoice_tag');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('invoice_tags');
        Schema::dropIfExists('products');
    }
};
