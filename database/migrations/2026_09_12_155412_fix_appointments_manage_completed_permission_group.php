<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Permission;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Permission::where('name', 'appointments.manage_completed')
            ->update(['group' => 'appointments']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No down logic needed
    }
};
