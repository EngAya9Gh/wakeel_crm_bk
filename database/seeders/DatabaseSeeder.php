<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create Teams
        $salesTeam = DB::table('teams')->insertGetId([
            'name' => 'فريق المبيعات',
            'category' => 'sales',
            'description' => 'فريق مسؤول عن المبيعات والعملاء',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $supportTeam = DB::table('teams')->insertGetId([
            'name' => 'فريق الدعم الفني',
            'category' => 'support',
            'description' => 'فريق مسؤول عن الدعم الفني',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2. Create Permissions
        $permissions = [
            // Clients
            ['name' => 'clients.view', 'display_name' => 'عرض العملاء', 'group' => 'clients'],
            ['name' => 'clients.create', 'display_name' => 'إنشاء عميل', 'group' => 'clients'],
            ['name' => 'clients.update', 'display_name' => 'تعديل عميل', 'group' => 'clients'],
            ['name' => 'clients.delete', 'display_name' => 'حذف عميل', 'group' => 'clients'],
            ['name' => 'clients.assign', 'display_name' => 'إسناد عميل', 'group' => 'clients'],
            ['name' => 'clients.export', 'display_name' => 'تصدير العملاء', 'group' => 'clients'],
            
            // Invoices
            ['name' => 'invoices.view', 'display_name' => 'عرض الفواتير', 'group' => 'invoices'],
            ['name' => 'invoices.create', 'display_name' => 'إنشاء فاتورة', 'group' => 'invoices'],
            ['name' => 'invoices.update', 'display_name' => 'تعديل فاتورة', 'group' => 'invoices'],
            ['name' => 'invoices.delete', 'display_name' => 'حذف فاتورة', 'group' => 'invoices'],
            ['name' => 'invoices.send', 'display_name' => 'إرسال فاتورة', 'group' => 'invoices'],
            
            // Appointments
            ['name' => 'appointments.view', 'display_name' => 'عرض المواعيد', 'group' => 'appointments'],
            ['name' => 'appointments.create', 'display_name' => 'إنشاء موعد', 'group' => 'appointments'],
            ['name' => 'appointments.update', 'display_name' => 'تعديل موعد', 'group' => 'appointments'],
            ['name' => 'appointments.delete', 'display_name' => 'حذف موعد', 'group' => 'appointments'],
            
            // Settings
            ['name' => 'settings.view', 'display_name' => 'عرض الإعدادات', 'group' => 'settings'],
            ['name' => 'settings.manage', 'display_name' => 'إدارة الإعدادات', 'group' => 'settings'],
            
            // Users & Teams
            ['name' => 'users.view', 'display_name' => 'عرض المستخدمين', 'group' => 'users'],
            ['name' => 'users.create', 'display_name' => 'إنشاء مستخدم', 'group' => 'users'],
            ['name' => 'users.update', 'display_name' => 'تعديل مستخدم', 'group' => 'users'],
            ['name' => 'users.delete', 'display_name' => 'حذف مستخدم', 'group' => 'users'],
            ['name' => 'teams.view', 'display_name' => 'عرض الفرق', 'group' => 'teams'],
            ['name' => 'teams.manage', 'display_name' => 'إدارة الفرق', 'group' => 'teams'],
        ];

        foreach ($permissions as $permission) {
            DB::table('permissions')->insert(array_merge($permission, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        // 3. Create Roles
        $adminRole = DB::table('roles')->insertGetId([
            'team_id' => null, // Global role
            'name' => 'Super Admin',
            'is_default' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $salesManagerRole = DB::table('roles')->insertGetId([
            'team_id' => $salesTeam,
            'name' => 'مدير المبيعات',
            'is_default' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $salesRole = DB::table('roles')->insertGetId([
            'team_id' => $salesTeam,
            'name' => 'موظف مبيعات',
            'is_default' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $supportRole = DB::table('roles')->insertGetId([
            'team_id' => $supportTeam,
            'name' => 'موظف دعم',
            'is_default' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 4. Assign Permissions to Roles
        $allPermissions = DB::table('permissions')->pluck('id')->toArray();
        
        // Admin gets all permissions
        foreach ($allPermissions as $permId) {
            DB::table('role_permissions')->insert([
                'role_id' => $adminRole,
                'permission_id' => $permId,
            ]);
        }

        // Sales Manager gets most permissions
        $salesManagerPerms = DB::table('permissions')
            ->whereIn('group', ['clients', 'invoices', 'appointments', 'settings'])
            ->pluck('id')->toArray();
        foreach ($salesManagerPerms as $permId) {
            DB::table('role_permissions')->insert([
                'role_id' => $salesManagerRole,
                'permission_id' => $permId,
            ]);
        }

        // Sales gets client and invoice permissions
        $salesPerms = DB::table('permissions')
            ->whereIn('name', [
                'clients.view', 'clients.create', 'clients.update',
                'invoices.view', 'invoices.create', 'invoices.update',
                'appointments.view', 'appointments.create', 'appointments.update',
            ])
            ->pluck('id')->toArray();
        foreach ($salesPerms as $permId) {
            DB::table('role_permissions')->insert([
                'role_id' => $salesRole,
                'permission_id' => $permId,
            ]);
        }

        // Support gets view permissions only
        $supportPerms = DB::table('permissions')
            ->where('name', 'like', '%.view')
            ->pluck('id')->toArray();
        foreach ($supportPerms as $permId) {
            DB::table('role_permissions')->insert([
                'role_id' => $supportRole,
                'permission_id' => $permId,
            ]);
        }

        // 5. Create Admin User
        DB::table('users')->insert([
            'team_id' => null,
            'role_id' => $adminRole,
            'name' => 'Admin',
            'email' => 'admin@crm.test',
            'password' => Hash::make('password'),
            'phone' => '0500000000',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 6. Client Statuses
        $statuses = [
            ['name' => 'عميل جديد', 'color' => '#3B82F6', 'order' => 1, 'is_default' => true],
            ['name' => 'قيد التفاوض', 'color' => '#F59E0B', 'order' => 2, 'is_default' => false],
            ['name' => 'عرض سعر', 'color' => '#8B5CF6', 'order' => 3, 'is_default' => false],
            ['name' => 'مستبعد', 'color' => '#EF4444', 'order' => 4, 'is_default' => false],
            ['name' => 'مشترك', 'color' => '#10B981', 'order' => 5, 'is_default' => false],
        ];

        foreach ($statuses as $status) {
            DB::table('client_statuses')->insert(array_merge($status, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        // 7. Sources
        $sources = [
            ['name' => 'موقع الويب', 'is_active' => true],
            ['name' => 'فيسبوك', 'is_active' => true],
            ['name' => 'إنستغرام', 'is_active' => true],
            ['name' => 'إحالة', 'is_active' => true],
            ['name' => 'مكالمة مباشرة', 'is_active' => true],
        ];

        foreach ($sources as $source) {
            DB::table('sources')->insert(array_merge($source, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        // 8. Behaviors
        $behaviors = [
            ['name' => 'متعاون', 'color' => '#10B981'],
            ['name' => 'محايد', 'color' => '#6B7280'],
            ['name' => 'صعب', 'color' => '#EF4444'],
        ];

        foreach ($behaviors as $behavior) {
            DB::table('behaviors')->insert(array_merge($behavior, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        // 9. Invalid Reasons
        $invalidReasons = [
            ['name' => 'رقم خاطئ'],
            ['name' => 'لا يرد'],
            ['name' => 'غير مهتم'],
            ['name' => 'خارج المنطقة'],
        ];

        foreach ($invalidReasons as $reason) {
            DB::table('invalid_reasons')->insert(array_merge($reason, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        // 10. Regions & Cities
        $regions = [
            ['name' => 'الرياض', 'cities' => ['الرياض', 'الخرج', 'الدرعية']],
            ['name' => 'مكة المكرمة', 'cities' => ['جدة', 'مكة', 'الطائف']],
            ['name' => 'المنطقة الشرقية', 'cities' => ['الدمام', 'الخبر', 'الأحساء']],
        ];

        foreach ($regions as $regionData) {
            $regionId = DB::table('regions')->insertGetId([
                'name' => $regionData['name'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($regionData['cities'] as $cityName) {
                DB::table('cities')->insert([
                    'region_id' => $regionId,
                    'name' => $cityName,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // 11. Tags
        $tags = [
            ['name' => 'VIP', 'color' => '#F59E0B'],
            ['name' => 'متابعة عاجلة', 'color' => '#EF4444'],
            ['name' => 'عميل دائم', 'color' => '#10B981'],
        ];

        foreach ($tags as $tag) {
            DB::table('tags')->insert(array_merge($tag, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        // 12. Invoice Tags
        $invoiceTags = [
            ['name' => 'عاجل', 'color' => '#EF4444'],
            ['name' => 'متأخر', 'color' => '#F59E0B'],
            ['name' => 'مدفوع', 'color' => '#10B981'],
        ];

        foreach ($invoiceTags as $tag) {
            DB::table('invoice_tags')->insert(array_merge($tag, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        // 13. Products
        $products = [
            ['name' => 'خدمة استشارية', 'description' => 'استشارة قانونية', 'sku' => 'SRV-001', 'unit_price' => 500, 'unit' => 'hour', 'is_active' => true],
            ['name' => 'اشتراك شهري', 'description' => 'اشتراك شهري في الخدمة', 'sku' => 'SUB-001', 'unit_price' => 1000, 'unit' => 'month', 'is_active' => true],
            ['name' => 'منتج رقمي', 'description' => 'منتج رقمي', 'sku' => 'DIG-001', 'unit_price' => 250, 'unit' => 'piece', 'is_active' => true],
        ];

        foreach ($products as $product) {
            DB::table('products')->insert(array_merge($product, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        echo "✅ Database seeded successfully!\n";
        echo "📧 Admin Email: admin@crm.test\n";
        echo "🔑 Password: password\n";
    }
}
