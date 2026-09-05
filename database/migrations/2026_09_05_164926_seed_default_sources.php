<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $sources = ['فيسبوك', 'إنستغرام', 'تيك توك', 'واتساب'];
        
        foreach ($sources as $source) {
            DB::table('sources')->insertOrIgnore([
                'name' => $source,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('sources')->whereIn('name', ['فيسبوك', 'إنستغرام', 'تيك توك', 'واتساب'])->delete();
    }
};
