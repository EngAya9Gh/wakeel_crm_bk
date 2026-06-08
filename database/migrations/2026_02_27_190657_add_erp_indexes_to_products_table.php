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
        Schema::table('products', function (Blueprint $table) {
            $table->index('erp_id');
            
            // Check if index already exists using Laravel 11/12 native Schema builder
            $indexes = Schema::getIndexes('products');
            $hasSkuIndex = false;
            foreach ($indexes as $index) {
                if ($index['name'] === 'products_sku_index' || $index['name'] === 'products_sku_unique' || in_array('sku', $index['columns'])) {
                    $hasSkuIndex = true;
                    break;
                }
            }

            if (!$hasSkuIndex) {
                $table->index('sku');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['erp_id']);
            
            $indexes = Schema::getIndexes('products');
            $hasSkuIndex = false;
            foreach ($indexes as $index) {
                if ($index['name'] === 'products_sku_index') {
                    $hasSkuIndex = true;
                    break;
                }
            }
            
            if ($hasSkuIndex) {
                $table->dropIndex(['sku']);
            }
        });
    }
};
