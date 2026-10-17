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
        Schema::table('tickets', function (Blueprint $table) {
            $table->string('provider_ticket_id', 100)->nullable()->index()->after('source');
            $table->string('provider_thread_id', 100)->nullable()->index()->after('provider_ticket_id');
            $table->unsignedTinyInteger('rating')->nullable()->index()->after('provider_thread_id');
            $table->text('ai_summary')->nullable()->after('rating');
        });

        // Migrate existing metadata into the dedicated columns
        try {
            $tickets = \Illuminate\Support\Facades\DB::table('tickets')
                ->whereNotNull('metadata')
                ->select(['id', 'metadata'])
                ->get();

            foreach ($tickets as $ticket) {
                $meta = json_decode((string)$ticket->metadata, true);
                if (is_array($meta) && !empty($meta)) {
                    $update = [];
                    if (!empty($meta['provider_ticket_id'])) {
                        $update['provider_ticket_id'] = (string) $meta['provider_ticket_id'];
                    }
                    if (!empty($meta['provider_thread_id'])) {
                        $update['provider_thread_id'] = (string) $meta['provider_thread_id'];
                    }
                    if (!empty($meta['rating'])) {
                        $update['rating'] = (int) $meta['rating'];
                    }
                    if (!empty($meta['ai_summary'])) {
                        $update['ai_summary'] = (string) $meta['ai_summary'];
                    }
                    if (!empty($update)) {
                        \Illuminate\Support\Facades\DB::table('tickets')
                            ->where('id', $ticket->id)
                            ->update($update);
                    }
                }
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Data migration for tickets provider columns: ' . $e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex(['provider_ticket_id']);
            $table->dropIndex(['provider_thread_id']);
            $table->dropIndex(['rating']);
            $table->dropColumn([
                'provider_ticket_id',
                'provider_thread_id',
                'rating',
                'ai_summary',
            ]);
        });
    }
};
