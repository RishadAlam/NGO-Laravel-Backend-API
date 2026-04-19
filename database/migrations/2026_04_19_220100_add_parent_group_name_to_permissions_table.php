<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Support\Permissions\PermissionParentCategoryResolver;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tableNames = config('permission.table_names');
        $permissionsTable = $tableNames['permissions'] ?? 'permissions';

        if (!Schema::hasColumn($permissionsTable, 'parent_group_name')) {
            Schema::table($permissionsTable, function (Blueprint $table) {
                $table->string('parent_group_name')
                    ->nullable()
                    ->default(PermissionParentCategoryResolver::DEFAULT_PARENT_CATEGORY)
                    ->after('group_name');
                $table->index('parent_group_name');
            });
        }

        foreach (PermissionParentCategoryResolver::map() as $groupName => $parentGroupName) {
            DB::table($permissionsTable)
                ->where('group_name', $groupName)
                ->update(['parent_group_name' => $parentGroupName]);
        }

        DB::table($permissionsTable)
            ->where(function ($query) {
                $query->whereNull('parent_group_name')
                    ->orWhere('parent_group_name', '');
            })
            ->update(['parent_group_name' => PermissionParentCategoryResolver::DEFAULT_PARENT_CATEGORY]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableNames = config('permission.table_names');
        $permissionsTable = $tableNames['permissions'] ?? 'permissions';

        if (Schema::hasColumn($permissionsTable, 'parent_group_name')) {
            Schema::table($permissionsTable, function (Blueprint $table) {
                $table->dropIndex(['parent_group_name']);
                $table->dropColumn('parent_group_name');
            });
        }
    }
};
