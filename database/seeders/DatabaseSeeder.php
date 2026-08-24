<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * DatabaseSeeder — Main entry point.
 *
 * ╔══════════════════════════════════════════════════════════════════════╗
 * ║  IMPORTANT — READ BEFORE RUNNING                                    ║
 * ╠══════════════════════════════════════════════════════════════════════╣
 * ║                                                                      ║
 * ║  php artisan db:seed          ← runs ONLY SuperAdminSeeder (SAFE)   ║
 * ║  php artisan db:seed --class=SuperAdminSeeder  ← same, explicit     ║
 * ║                                                                      ║
 * ║  TenantSeeder is a ONE-TIME setup tool for fresh installations.     ║
 * ║  DO NOT run it on a live database — it inserts new statuses,        ║
 * ║  sources, and a default admin user. Use it only on a clean DB.      ║
 * ║                                                                      ║
 * ║  To run TenantSeeder manually (fresh install only):                 ║
 * ║    php artisan db:seed --class=TenantSeeder                         ║
 * ╚══════════════════════════════════════════════════════════════════════╝
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // SAFE to run repeatedly — uses firstOrCreate, never duplicates.
        $this->call([
            SuperAdminSeeder::class,
        ]);
    }
}
