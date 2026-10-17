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
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('timezone', 50)->default('Asia/Riyadh')->after('plan');
        });

        // Set default timezone for existing tenants (check settings if specified)
        try {
            $tenants = DB::table('tenants')->select(['id', 'settings'])->get();
            foreach ($tenants as $t) {
                $tz = 'Asia/Riyadh';
                if (!empty($t->settings)) {
                    $settings = json_decode((string)$t->settings, true);
                    if (!empty($settings['timezone'])) {
                        $tz = (string) $settings['timezone'];
                    }
                }
                DB::table('tenants')->where('id', $t->id)->update(['timezone' => $tz]);
            }
        } catch (\Throwable $e) {
            // Ignore during rollback/fresh setups
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('timezone');
        });
    }
};
