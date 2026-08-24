<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * SuperAdminSeeder — Creates the global Super Admin user.
 * 
 * This user:
 * - Has is_super_admin = true
 * - Has tenant_id = null (crosses all tenants)
 * - Accesses the system via /super/v1 routes
 *
 * Run: php artisan db:seed --class=SuperAdminSeeder
 */
class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        User::withoutGlobalScope('tenant')->firstOrCreate(
            ['email' => 'superadmin@wakeel.system'],
            [
                'tenant_id'      => null,
                'team_id'        => null,
                'role_id'        => null,
                'name'           => 'Super Admin',
                'email'          => 'superadmin@wakeel.system',
                'password'       => Hash::make('SuperAdmin@Wakeel2025'),
                'is_active'      => true,
                'is_super_admin' => true,
            ]
        );

        $this->command->info('✅ Super Admin created: superadmin@wakeel.system');
        $this->command->warn('⚠️  Password: SuperAdmin@Wakeel2025 — Change immediately after first login!');
    }
}
