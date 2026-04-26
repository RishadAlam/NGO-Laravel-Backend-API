<?php

use App\Support\Permissions\PermissionParentCategoryResolver;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tableNames = config('permission.table_names');
        $permissionsTable = $tableNames['permissions'] ?? 'permissions';
        $parentGroupName = PermissionParentCategoryResolver::resolve('staff_role');

        foreach (['role_permission_view', 'role_permission_update'] as $permissionName) {
            DB::table($permissionsTable)->updateOrInsert(
                ['name' => $permissionName],
                [
                    'group_name' => 'staff_role',
                    'parent_group_name' => $parentGroupName,
                    'guard_name' => 'web',
                ]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableNames = config('permission.table_names');
        $permissionsTable = $tableNames['permissions'] ?? 'permissions';

        DB::table($permissionsTable)
            ->whereIn('name', ['role_permission_view', 'role_permission_update'])
            ->delete();
    }
};
