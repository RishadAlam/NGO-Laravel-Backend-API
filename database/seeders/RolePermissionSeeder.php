<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //Create Role
        $roleDeveloper  = Role::create(['name' => 'developer', 'is_default' => true]);
        $roleSuperAdmin = Role::create(['name' => 'super_admin', 'is_default' => true]);
        $roleAdmin      = Role::create(['name' => 'admin', 'is_default' => true]);
        $roleManager    = Role::create(['name' => 'manager', 'is_default' => true]);
        $roleUser       = Role::create(['name' => 'field_officer', 'is_default' => true]);

        // Permission
        $permissions = [
            // Dashboard
            [
                'groupName'     => 'dashboard',
                'permissions'   => [
                    'dashboardAsAdmin'
                ]
            ],
            // Staffs
            [
                'groupName'     => 'staff',
                'permissions'   => [
                    'staff_list_view',
                    'staff_permissions_view',
                    'staff_registration',
                    'staff_data_update',
                    'staff_soft_delete',
                    'staff_permanently_delete',
                ]
            ],
            // Staffs
            [
                'groupName'     => 'role',
                'permissions'   => [
                    'role_list_view',
                    'role_registration',
                    'role_update',
                    'role_delete',
                ]
            ]
        ];

        /**
         * Find user
         */
        $user = User::where('email', 'sazzadullalamrishad@yahoo.com')->first();
        $user->assignRole($roleDeveloper);

        for ($j = 2; $j < 12; $j++) {
            $role = Arr::random([$roleSuperAdmin, $roleAdmin, $roleManager, $roleUser]);
            User::find($j)->assignRole($role);
        }

        foreach ($permissions as $row) {
            $groupName = $row['groupName'];
            foreach ($row['permissions'] as $permission) {
                $permission = Permission::create(
                    [
                        'name'          => $permission,
                        'group_name'    => $groupName
                    ]
                );
                $user->givePermissionTo($permission);
            }
        }
    }
}
