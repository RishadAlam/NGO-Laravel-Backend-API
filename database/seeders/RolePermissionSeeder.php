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
            // Registration
            [
                'groupName'     => 'registration',
                'permissions'   => [
                    'client_registration',
                    'saving_acc_registration',
                    'loan_acc_registration',
                    'saving_acc_creator_selection',
                    'loan_acc_creator_selection'
                ]
            ],
            // Pending Client Registration Form
            [
                'groupName'     => 'pending_client_registration_form',
                'permissions'   => [
                    'pending_client_registration_list_view',
                    'pending_client_registration_list_view_as_admin',
                    'pending_client_registration_approval',
                    'pending_client_registration_update',
                    'pending_client_registration_permanently_delete',
                ]
            ],
            // Pending Saving Account Registration Form
            [
                'groupName'     => 'pending_saving_acc_registration_form',
                'permissions'   => [
                    'pending_saving_acc_list_view',
                    'pending_saving_acc_list_view_as_admin',
                    'pending_saving_acc_approval',
                    'pending_saving_acc_update',
                    'pending_saving_acc_permanently_delete',
                ]
            ],
            // Pending Loan Account Registration Form
            [
                'groupName'     => 'pending_loan_acc_registration_form',
                'permissions'   => [
                    'pending_loan_acc_list_view',
                    'pending_loan_acc_list_view_as_admin',
                    'pending_loan_acc_approval',
                    'pending_loan_acc_update',
                    'pending_loan_acc_permanently_delete',
                ]
            ],
            // Pending Loan
            [
                'groupName'     => 'pending_loan',
                'permissions'   => [
                    'pending_loan_view',
                    'pending_loan_view_as_admin',
                    'pending_loan_approval',
                    'pending_loan_update',
                    'pending_loan_permanently_delete',
                ]
            ],
            // Client Register Account
            [
                'groupName'     => 'client_register_account',
                'permissions'   => [
                    'client_register_account_action_history',
                    'client_register_account_update',
                    'client_register_account_delete',
                    'client_register_account_permanently_delete',
                    'client_register_account_field_update',
                    'client_register_account_center_update',
                    'client_register_account_acc_no_update',
                ]
            ],
            // Client Saving Account
            [
                'groupName'     => 'client_saving_account',
                'permissions'   => [
                    'client_saving_account_action_history',
                    'client_saving_account_update',
                    'client_saving_account_delete',
                    'client_saving_account_change_status',
                    'client_saving_account_check',
                    'client_saving_account_closing',
                    'client_saving_account_permanently_delete',
                    'client_saving_account_category_update',
                ]
            ],
            // Client Loan Account
            [
                'groupName'     => 'client_loan_account',
                'permissions'   => [
                    'client_loan_account_action_history',
                    'client_loan_account_update',
                    'client_loan_account_delete',
                    'client_loan_account_change_status',
                    'client_loan_account_check',
                    'client_loan_account_closing',
                    'client_loan_account_permanently_delete',
                    'client_loan_account_category_update',
                ]
            ],
            // Regular Saving Collection
            [
                'groupName'     => 'regular_saving_collection',
                'permissions'   => [
                    'permission_to_do_saving_collection',
                    'regular_saving_collection_list_view',
                    'regular_saving_collection_list_view_as_admin',
                    'regular_saving_collection_approval',
                    'regular_saving_collection_update',
                    'regular_saving_collection_permanently_delete',
                ]
            ],
            // Regular Loan Collection
            [
                'groupName'     => 'regular_loan_collection',
                'permissions'   => [
                    'permission_to_do_loan_collection',
                    'permission_to_do_edit_loan_interest',
                    'regular_loan_collection_list_view',
                    'regular_loan_collection_list_view_as_admin',
                    'regular_loan_collection_approval',
                    'regular_loan_collection_update',
                    'regular_loan_collection_permanently_delete',
                ]
            ],
            // Pending Saving Collection
            [
                'groupName'     => 'pending_saving_collection',
                'permissions'   => [
                    'pending_saving_collection_list_view',
                    'pending_saving_collection_list_view_as_admin',
                    'pending_saving_collection_approval',
                    'pending_saving_collection_update',
                    'pending_saving_collection_permanently_delete',
                ]
            ],
            // Pending Loan Collection
            [
                'groupName'     => 'pending_loan_collection',
                'permissions'   => [
                    'pending_loan_collection_list_view',
                    'pending_loan_collection_list_view_as_admin',
                    'pending_loan_collection_approval',
                    'pending_loan_collection_update',
                    'pending_loan_collection_permanently_delete',
                ]
            ],
            // Saving Withdrawal
            [
                'groupName'     => 'saving_withdrawal',
                'permissions'   => [
                    'permission_to_make_saving_withdrawal',
                    'pending_saving_withdrawal_list_view',
                    'pending_saving_withdrawal_list_view_as_admin',
                    'pending_saving_withdrawal_approval',
                    'pending_saving_withdrawal_update',
                    'pending_saving_withdrawal_delete',
                ]
            ],
            // Loan Saving Withdrawal
            [
                'groupName'     => 'loan_saving_withdrawal',
                'permissions'   => [
                    'permission_to_make_loan_saving_withdrawal',
                    'pending_loan_saving_withdrawal_list_view',
                    'pending_loan_saving_withdrawal_list_view_as_admin',
                    'pending_loan_saving_withdrawal_approval',
                    'pending_loan_saving_withdrawal_update',
                    'pending_loan_saving_withdrawal_delete',
                ]
            ],
            // Pending Request to delete Saving Account
            [
                'groupName'     => 'pending_req_to_delete_saving_acc',
                'permissions'   => [
                    'pending_req_to_delete_saving_acc_list_view',
                    'pending_req_to_delete_saving_acc_list_view_as_admin',
                    'pending_req_to_delete_saving_acc_approval',
                    'pending_req_to_delete_saving_acc_update',
                    'pending_req_to_delete_saving_acc_delete',
                ]
            ],
            // Pending Request to delete Loan Account
            [
                'groupName'     => 'pending_req_to_delete_loan_acc',
                'permissions'   => [
                    'pending_req_to_delete_loan_acc_list_view',
                    'pending_req_to_delete_loan_acc_list_view_as_admin',
                    'pending_req_to_delete_loan_acc_approval',
                    'pending_req_to_delete_loan_acc_update',
                    'pending_req_to_delete_loan_acc_delete',
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
                    'account_transaction_list_view',
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
                    'staff_registration',
                    'staff_data_update',
                    'staff_status_update',
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
            // Staffs Permission
            [
                'groupName'     => 'staff_permission',
                'permissions'   => [
                    'staff_permission_view',
                    'staff_permission_update',
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
            ],
            // Audit Report Meta
            [
                'groupName'     => 'audit_report_meta',
                'permissions'   => [
                    'audit_report_meta_list_view',
                    'audit_report_meta_create',
                    'audit_report_meta_update',
                    'audit_report_meta_soft_delete',
                    'audit_report_meta_action_history',
                    'audit_report_meta_permanently_delete',
                ]
            ],
            // Co-Operative Audit Report
            [
                'groupName'     => 'cooperative_audit_report',
                'permissions'   => [
                    'cooperative_audit_report_view',
                    'cooperative_audit_report_update',
                    'cooperative_audit_report_print',
                ]
            ],
        ];

        /**
         * Find user
         */
        $user = User::where('email', 'sazzadullalamrishad@yahoo.com')->first();
        $user->assignRole($roleDeveloper);

        // for ($j = 2; $j < 12; $j++) {
        //     $role = Arr::random([$roleSuperAdmin, $roleAdmin, $roleManager, $roleUser]);
        //     User::find($j)->assignRole($role);
        // }

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
