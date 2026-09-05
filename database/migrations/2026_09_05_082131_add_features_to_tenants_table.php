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
        Schema::table('tenants', function (Blueprint $table) {
            $table->json('features')->nullable()->after('settings');
        });

        // Migrate existing features from settings to the new features column
        DB::table('tenants')->orderBy('id')->chunk(100, function ($tenants) {
            foreach ($tenants as $tenant) {
                $settings = json_decode($tenant->settings ?? '{}', true) ?: [];
                $features = [];

                if (isset($settings['features']) && is_array($settings['features'])) {
                    $features = $settings['features'];
                }
                
                // Keep whatsapp backwards compatibility
                if (!empty($settings['whatsapp_api_key']) && !in_array('whatsapp', $features)) {
                    $features[] = 'whatsapp';
                }

                if (!empty($settings['invoices_enabled']) && !in_array('invoices', $features)) {
                    $features[] = 'invoices';
                }

                if (!empty($features)) {
                    DB::table('tenants')->where('id', $tenant->id)->update([
                        'features' => json_encode(array_values(array_unique($features)))
                    ]);
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('features');
        });
    }
};
