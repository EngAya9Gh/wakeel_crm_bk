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
            // Tickets
            ['name' => 'tickets.view', 'display_name' => 'عرض التذاكر', 'group' => 'tickets'],
            ['name' => 'tickets.create', 'display_name' => 'إنشاء تذكرة', 'group' => 'tickets'],
            ['name' => 'tickets.edit', 'display_name' => 'تعديل تذكرة', 'group' => 'tickets'],
            ['name' => 'tickets.delete', 'display_name' => 'حذف تذكرة', 'group' => 'tickets'],
            ['name' => 'tickets.assign', 'display_name' => 'إسناد التذاكر', 'group' => 'tickets'],
            ['name' => 'tickets.manage_categories', 'display_name' => 'إدارة تصنيفات التذاكر', 'group' => 'tickets'],
            
            // Evaluations
            ['name' => 'evaluations.view', 'display_name' => 'عرض التقييمات', 'group' => 'evaluations'],
            ['name' => 'evaluations.create', 'display_name' => 'إضافة تقييم', 'group' => 'evaluations'],
            ['name' => 'evaluations.delete', 'display_name' => 'حذف تقييم', 'group' => 'evaluations'],
            ['name' => 'evaluations.manage_types', 'display_name' => 'إدارة أنواع التقييمات', 'group' => 'evaluations'],
        ];

        foreach ($permissions as $permission) {
            DB::table('permissions')->insert(array_merge($permission, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('permissions')->whereIn('group', ['tickets', 'evaluations'])->delete();
    }
};
