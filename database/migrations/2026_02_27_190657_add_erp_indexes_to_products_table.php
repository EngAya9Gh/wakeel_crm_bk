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
            // Assuming sku wasn't indexed. If it is, this might fail, but it's safe to try.
            // A better way is to check first:
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexesFound = $sm->listTableIndexes('products');

            if (!array_key_exists('products_sku_index', $indexesFound) && !array_key_exists('products_sku_unique', $indexesFound)) {
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
            
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexesFound = $sm->listTableIndexes('products');
            if (array_key_exists('products_sku_index', $indexesFound)) {
                $table->dropIndex(['sku']);
            }
        });
    }
};
