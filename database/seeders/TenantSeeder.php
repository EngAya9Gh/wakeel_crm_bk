<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Role;
use App\Models\Team;
use App\Models\Permission;
use App\Models\ClientStatus;
use App\Models\Source;
use App\Services\Tenants\TenantSetupService;
use Illuminate\Database\Seeder;

/**
 * TenantSeeder
 *
 * Creates the default tenant with a complete initial setup:
 * - The default tenant
 * - Default team & admin role with all permissions
 * - The super-admin user
 * - Initial client statuses and sources
 *
 * Run: php artisan db:seed --class=TenantSeeder
 */
class TenantSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create the default tenant
        $tenant = Tenant::firstOrCreate(
            ['slug' => 'default'],
            [
                'name'      => 'الحساب الافتراضي',
                'slug'      => 'default',
                'email'     => 'admin@wakeel.crm',
                'is_active' => true,
                'plan'      => 'pro',
            ]
        );

        // 2. Delegate to TenantSetupService
        $setupService = app(TenantSetupService::class);
        
        $adminUser = clone \App\Models\User::withoutGlobalScope('tenant')
            ->where('email', 'admin@wakeel.crm')
            ->first(); // Avoid creating user if it already exists

        if (!$adminUser) {
            $setupService->setupNewTenant(
                $tenant,
                'مدير النظام',
                'admin@wakeel.crm',
                'Password@123'
            );
        } else {
            // Setup the tenant without creating the user again
            $setupService->setupNewTenant($tenant);
        }

        $this->command->info("✅ Default Tenant created/verified: {$tenant->name}");
        $this->command->info("✅ Admin user: admin@wakeel.crm / Password@123");
        $this->command->info("⚠️  Change the admin password immediately after first login!");
    }
}
