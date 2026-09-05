<?php

declare(strict_types=1);

namespace App\Services\Tenants;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Role;
use App\Models\Team;
use App\Models\Permission;
use App\Models\ClientStatus;
use App\Models\Source;
use App\Services\TenantContext;
use Illuminate\Support\Facades\Hash;

class TenantSetupService
{
    /**
     * Initialize a newly created tenant with default data.
     * 
     * @param Tenant $tenant The newly created tenant
     * @param string|null $adminName Admin name
     * @param string|null $adminEmail Admin email
     * @param string|null $adminPassword Admin password (plain text)
     * @return User|null The created admin user, if requested
     */
    public function setupNewTenant(Tenant $tenant, ?string $adminName = null, ?string $adminEmail = null, ?string $adminPassword = null): ?User
    {
        // 1. Set context so subsequent models are created for this tenant
        TenantContext::set($tenant);

        // 2. Create the default Management team
        $team = Team::firstOrCreate(
            ['name' => 'الإدارة', 'tenant_id' => $tenant->id],
            [
                'tenant_id'   => $tenant->id,
                'name'        => 'الإدارة',
                'category'    => 'management',
                'description' => 'فريق الإدارة العليا',
                'is_active'   => true,
            ]
        );

        // 3. Ensure global permissions exist (this is safe to run multiple times)
        $this->ensurePermissionsExist();

        // 4. Create the Admin role for this tenant
        $adminRole = Role::firstOrCreate(
            ['name' => 'مدير النظام', 'tenant_id' => $tenant->id],
            [
                'tenant_id'  => $tenant->id,
                'team_id'    => $team->id,
                'name'       => 'مدير النظام',
                'is_default' => false,
            ]
        );

        // Assign all existing permissions to this Admin role
        $adminRole->permissions()->sync(Permission::pluck('id'));

        // 5. Seed default client statuses
        $this->seedDefaultStatuses($tenant);

        // 6. Seed default sources
        $this->seedDefaultSources($tenant);

        // 7. Create admin user if email is provided
        $adminUser = null;
        if (!empty($adminEmail)) {
            $adminUser = User::withoutGlobalScope('tenant')->create([
                'tenant_id' => $tenant->id,
                'team_id'   => $team->id,
                'role_id'   => $adminRole->id,
                'name'      => $adminName ?? 'مدير النظام',
                'email'     => $adminEmail,
                'password'  => Hash::make($adminPassword ?? 'Password@123'),
                'is_active' => true,
            ]);
        }

        // Clear context to prevent bleed
        TenantContext::clear();

        return $adminUser;
    }

    private function ensurePermissionsExist(): void
    {
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
            // Inventory
            ['name' => 'inventory.view',         'display_name' => 'عرض المخزون',           'group' => 'inventory'],
            ['name' => 'inventory.create',       'display_name' => 'إضافة للمخزون',         'group' => 'inventory'],
            ['name' => 'inventory.edit',         'display_name' => 'تعديل المخزون',         'group' => 'inventory'],
            ['name' => 'inventory.delete',       'display_name' => 'حذف من المخزون',        'group' => 'inventory'],
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
    }

    private function seedDefaultStatuses(Tenant $tenant): void
    {
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
    }

    private function seedDefaultSources(Tenant $tenant): void
    {
        $sources = ['نموذج الموقع', 'صفحة الهبوط', 'نموذج اتصل بنا', 'واتساب', 'إحالة', 'إعلان'];

        foreach ($sources as $sourceName) {
            Source::firstOrCreate(
                ['name' => $sourceName, 'tenant_id' => $tenant->id],
                ['name' => $sourceName, 'tenant_id' => $tenant->id, 'is_active' => true]
            );
        }
    }
}
