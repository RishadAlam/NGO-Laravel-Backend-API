<?php

use App\Support\Permissions\PermissionParentCategoryResolver;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_impersonation_sessions', function (Blueprint $table) {
            $table->id();
            // Keep audit identifiers even after a user or token is deleted.
            $table->unsignedBigInteger('admin_id')->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('admin_token_id')->index();
            $table->unsignedBigInteger('token_id')->unique();
            $table->timestamp('expires_at')->index();
            $table->timestamp('ended_at')->nullable();
            $table->string('end_reason')->nullable();
            $table->timestamps();
        });

        DB::table(config('permission.table_names.permissions', 'permissions'))->updateOrInsert(
            ['name' => 'staff_impersonate', 'guard_name' => 'web'],
            ['group_name' => 'staff', 'parent_group_name' => PermissionParentCategoryResolver::resolve('staff')]
        );
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        DB::table('personal_access_tokens')->where('name', 'impersonation')->delete();
        Schema::dropIfExists('user_impersonation_sessions');
        DB::table(config('permission.table_names.permissions', 'permissions'))
            ->where('name', 'staff_impersonate')->where('guard_name', 'web')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
