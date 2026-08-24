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
use App\Services\TenantContext;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

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

        // Set tenant context so all subsequent creates are scoped correctly
        TenantContext::set($tenant);

        // 2. Create management team
        $team = Team::firstOrCreate(
            ['name' => 'الإدارة'],
            [
                'tenant_id'   => $tenant->id,
                'name'        => 'الإدارة',
                'category'    => 'management',
                'description' => 'فريق الإدارة العليا',
                'is_active'   => true,
            ]
        );

        // 3. Sync Permissions (ensure they exist - permissions are global/shared)
        $permissionNames = [
            // Clients
            ['name' => 'clients.view',           'display_name' => 'عرض العملاء',           'group' => 'clients'],
            ['name' => 'clients.create',         'display_name' => 'إضافة عميل',             'group' => 'clients'],
            ['name' => 'clients.edit',           'display_name' => 'تعديل عميل',             'group' => 'clients'],
            ['name' => 'clients.delete',         'display_name' => 'حذف عميل',              'group' => 'clients'],
            ['name' => 'clients.assign',         'display_name' => 'إسناد عميل',             'group' => 'clients'],
            // Invoices
            ['name' => 'invoices.view',          'display_name' => 'عرض الفواتير',           'group' => 'invoices'],
            ['name' => 'invoices.create',        'display_name' => 'إنشاء فاتورة',           'group' => 'invoices'],
            ['name' => 'invoices.edit',          'display_name' => 'تعديل فاتورة',           'group' => 'invoices'],
            ['name' => 'invoices.delete',        'display_name' => 'حذف فاتورة',             'group' => 'invoices'],
            // Users
            ['name' => 'users.view',             'display_name' => 'عرض المستخدمين',         'group' => 'users'],
            ['name' => 'users.create',           'display_name' => 'إضافة مستخدم',           'group' => 'users'],
            ['name' => 'users.edit',             'display_name' => 'تعديل مستخدم',           'group' => 'users'],
            ['name' => 'users.delete',           'display_name' => 'حذف مستخدم',             'group' => 'users'],
            // Settings
            ['name' => 'settings.view',          'display_name' => 'عرض الإعدادات',          'group' => 'settings'],
            ['name' => 'settings.manage',        'display_name' => 'إدارة الإعدادات',        'group' => 'settings'],
            ['name' => 'api_keys.manage',        'display_name' => 'إدارة مفاتيح الـ API',  'group' => 'settings'],
        ];

        foreach ($permissionNames as $perm) {
            Permission::firstOrCreate(['name' => $perm['name']], $perm);
        }

        // 4. Create admin role for this tenant
        $adminRole = Role::firstOrCreate(
            ['name' => 'مدير النظام', 'tenant_id' => $tenant->id],
            [
                'tenant_id'  => $tenant->id,
                'team_id'    => $team->id,
                'name'       => 'مدير النظام',
                'is_default' => false,
            ]
        );

        // Attach all permissions to admin role
        $adminRole->permissions()->sync(Permission::pluck('id'));

        // 5. Create super-admin user
        $user = User::firstOrCreate(
            ['email' => 'admin@wakeel.crm'],
            [
                'tenant_id' => $tenant->id,
                'team_id'   => $team->id,
                'role_id'   => $adminRole->id,
                'name'      => 'مدير النظام',
                'email'     => 'admin@wakeel.crm',
                'password'  => Hash::make('Password@123'),
                'is_active' => true,
            ]
        );

        // 6. Seed initial client statuses for this tenant
        $statuses = [
            ['name' => 'جديد',        'color' => '#3B82F6', 'order' => 1, 'is_default' => true],
            ['name' => 'قيد المتابعة', 'color' => '#F59E0B', 'order' => 2, 'is_default' => false],
            ['name' => 'مهتم',        'color' => '#8B5CF6', 'order' => 3, 'is_default' => false],
            ['name' => 'تم البيع',    'color' => '#10B981', 'order' => 4, 'is_default' => false],
            ['name' => 'خسارة',       'color' => '#EF4444', 'order' => 5, 'is_default' => false],
        ];

        foreach ($statuses as $status) {
            ClientStatus::firstOrCreate(
                ['name' => $status['name'], 'tenant_id' => $tenant->id],
                array_merge($status, ['tenant_id' => $tenant->id])
            );
        }

        // 7. Seed initial sources for this tenant
        $sources = ['نموذج الموقع', 'صفحة الهبوط', 'نموذج اتصل بنا', 'واتساب', 'إحالة', 'إعلان'];

        foreach ($sources as $sourceName) {
            Source::firstOrCreate(
                ['name' => $sourceName, 'tenant_id' => $tenant->id],
                ['name' => $sourceName, 'tenant_id' => $tenant->id, 'is_active' => true]
            );
        }

        $this->command->info("✅ Default Tenant created: {$tenant->name}");
        $this->command->info("✅ Admin user: admin@wakeel.crm / Password@123");
        $this->command->info("⚠️  Change the admin password immediately after first login!");

        // Clear context after seeding
        TenantContext::clear();
    }
}
