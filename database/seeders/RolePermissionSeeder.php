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
                    'view_dashboard_as_admin'
                ]
            ],
            // Field
            [
                'groupName'     => 'field',
                'permissions'   => [
                    'field_list_view',
                    'field_registration',
                    'field_data_update',
                    'field_soft_delete',
                    'field_action_history',
                    'field_permanently_delete',
                ]
            ],
            // Center
            [
                'groupName'     => 'center',
                'permissions'   => [
                    'center_list_view',
                    'center_registration',
                    'center_data_update',
                    'center_soft_delete',
                    'center_action_history',
                    'center_permanently_delete',
                ]
            ],
            // Category
            [
                'groupName'     => 'category',
                'permissions'   => [
                    'category_list_view',
                    'category_registration',
                    'category_data_update',
                    'category_soft_delete',
                    'category_action_history',
                    'category_permanently_delete',
                ]
            ],
            // Account
            [
                'groupName'     => 'account_management',
                'permissions'   => [
                    'account_list_view',
                    'account_registration',
                    'account_data_update',
                    'account_soft_delete',
                    'account_action_history',
                    'account_permanently_delete',
                ]
            ],
            // Income
            [
                'groupName'     => 'income',
                'permissions'   => [
                    'income_list_view',
                    'income_registration',
                    'income_data_update',
                    'income_soft_delete',
                    'income_action_history',
                    'income_permanently_delete',
                ]
            ],
            // Income Categories
            [
                'groupName'     => 'income_category',
                'permissions'   => [
                    'income_category_list_view',
                    'income_category_registration',
                    'income_category_data_update',
                    'income_category_soft_delete',
                ]
            ],
            // Expense
            [
                'groupName'     => 'expense',
                'permissions'   => [
                    'expense_list_view',
                    'expense_registration',
                    'expense_data_update',
                    'expense_soft_delete',
                    'expense_action_history',
                    'expense_permanently_delete',
                ]
            ],
            // Expense Categories
            [
                'groupName'     => 'expense_category',
                'permissions'   => [
                    'expense_category_list_view',
                    'expense_category_registration',
                    'expense_category_data_update',
                    'expense_category_soft_delete',
                ]
            ],
            // Account Withdrawal
            [
                'groupName'     => 'account_withdrawal',
                'permissions'   => [
                    'account_withdrawal_list_view',
                    'account_withdrawal_registration',
                    'account_withdrawal_data_update',
                    'account_withdrawal_soft_delete',
                    'account_withdrawal_action_history',
                    'account_withdrawal_permanently_delete',
                ]
            ],
            // Account Transfer
            [
                'groupName'     => 'account_transfer',
                'permissions'   => [
                    'account_transfer_list_view',
                    'account_transfer_registration'
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
                    'staff_action_history',
                    'staff_reset_password',
                    'staff_permanently_delete',
                ]
            ],
            // Staffs Role
            [
                'groupName'     => 'staff_role',
                'permissions'   => [
                    'role_list_view',
                    'role_registration',
                    'role_update',
                    'role_delete',
                ]
            ],
            // App Config
            [
                'groupName'     => 'settings_and_privacy',
                'permissions'   => [
                    'app_settings',
                    'approvals_config',
                    'categories_config'
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
                        'group_name'    => $groupName,
                        'guard_name'    => 'web'
                    ]
                );
                $user->givePermissionTo($permission);
            }
        }
    }
}
