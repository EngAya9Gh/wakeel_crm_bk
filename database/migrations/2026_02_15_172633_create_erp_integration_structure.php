<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Create ERP Sync Logs Table
        Schema::create('erp_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type'); // Product, Client, Invoice
            $table->unsignedBigInteger('entity_id');
            $table->string('action'); // sync_stock, push_invoice, pull_client
            $table->string('status'); // success, failed, pending
            $table->text('request_payload')->nullable();
            $table->text('response_payload')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
            
            $table->index(['entity_type', 'entity_id']);
        });

        // 2. Add ERP fields to Products
        Schema::table('products', function (Blueprint $table) {
            $table->string('erp_id')->nullable()->unique()->after('id');
            $table->integer('stock_quantity')->default(0)->after('unit_price'); // Cached stock
            $table->timestamp('stock_last_synced_at')->nullable()->after('stock_quantity');
            $table->boolean('is_stockable')->default(true)->after('unit');
        });

        // 3. Add ERP fields to Clients
        Schema::table('clients', function (Blueprint $table) {
            $table->string('erp_id')->nullable()->unique()->after('id');
            $table->timestamp('erp_synced_at')->nullable()->after('updated_at');
        });

        // 4. Add ERP fields to Invoices
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('erp_id')->nullable()->unique()->after('id');
            $table->timestamp('erp_synced_at')->nullable()->after('updated_at');
            $table->enum('erp_sync_status', ['pending', 'synced', 'failed'])->default('pending')->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['erp_id', 'erp_synced_at', 'erp_sync_status']);
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['erp_id', 'erp_synced_at']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['erp_id', 'stock_quantity', 'stock_last_synced_at', 'is_stockable']);
        });

        Schema::dropIfExists('erp_sync_logs');
    }
};
