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
        $permissions = [
            ['name' => 'inventory.view',   'display_name' => 'عرض المخزون',    'group' => 'inventory'],
            ['name' => 'inventory.create', 'display_name' => 'إضافة للمخزون',  'group' => 'inventory'],
            ['name' => 'inventory.edit',   'display_name' => 'تعديل المخزون',  'group' => 'inventory'],
            ['name' => 'inventory.delete', 'display_name' => 'حذف من المخزون', 'group' => 'inventory'],
        ];

        foreach ($permissions as $perm) {
            $permission = \App\Models\Permission::firstOrCreate(['name' => $perm['name']], $perm);
            
            // Assign this new permission to all Admin roles (roles with ID 1, or name "مدير النظام")
            // Wait, an easier way is to just attach it to any role that is an admin role. 
            // In Wakeel CRM, the Super Admin role for a tenant is typically named 'مدير النظام' or 'Super Admin'.
            $adminRoles = \App\Models\Role::withoutGlobalScopes()
                ->whereIn('name', ['مدير النظام', 'Super Admin'])
                ->get();
                
            foreach ($adminRoles as $role) {
                // Attach permission if not already attached
                if (!$role->permissions()->where('permission_id', $permission->id)->exists()) {
                    $role->permissions()->attach($permission->id);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        \App\Models\Permission::where('group', 'inventory')->delete();
    }
};
