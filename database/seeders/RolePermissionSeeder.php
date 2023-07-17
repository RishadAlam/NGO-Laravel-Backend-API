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
        $roleDeveloper  = Role::create(['name' => 'Developer']);
        $roleSuperAdmin = Role::create(['name' => 'Super Admin']);
        $roleAdmin      = Role::create(['name' => 'Admin']);
        $roleManager    = Role::create(['name' => 'Manager']);
        $roleUser       = Role::create(['name' => 'Field Officer']);

        // Permission
        $permissions = [
            // Dashboard
            [
                'groupName'     => 'dashboard',
                'permissions'   => [
                    'dashboardAsAdmin'
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
