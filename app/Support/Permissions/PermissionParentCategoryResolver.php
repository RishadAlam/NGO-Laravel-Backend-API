<?php

namespace App\Support\Permissions;

use Illuminate\Support\Str;

class PermissionParentCategoryResolver
{
    public const DEFAULT_PARENT_CATEGORY = 'others';

    /**
     * Child group -> parent category.
     */
    public static function map(): array
    {
        return [
            'dashboard' => 'dashboard',

            'field' => 'field',
            'center' => 'center',
            'category' => 'category',

            'registration' => 'client_registration_accounts',
            'client_register_account' => 'client_register_account',
            'registered_account_view' => 'registered_account_view',

            'pending_client_registration_form' => 'pending_registration_accounts_form',
            'pending_saving_acc_registration_form' => 'pending_registration_accounts_form',
            'pending_loan_acc_registration_form' => 'pending_registration_accounts_form',
            'pending_loan' => 'pending_registration_accounts_form',

            'client_saving_account' => 'savings_accounts',
            'client_saving_account_collection' => 'savings_accounts',
            'client_saving_account_actions' => 'savings_accounts',

            'client_loan_account' => 'loan_accounts',
            'client_loan_account_collection' => 'loan_accounts',
            'client_loan_account_actions' => 'loan_accounts',
            
            'pending_req_to_delete_saving_acc' => 'pending_closing_accounts_requests',
            'pending_req_to_delete_loan_acc' => 'pending_closing_accounts_requests',

            'regular_saving_collection' => 'collections',
            'regular_loan_collection' => 'collections',
            'pending_saving_collection' => 'pending_collections',
            'pending_loan_collection' => 'pending_collections',

            'pending_saving_withdrawal' => 'withdrawals',
            'pending_loan_saving_withdrawal' => 'withdrawals',

            'pending_client_account_transactions' => 'pending_transactions',

            'account_management' => 'accounts',
            'account_withdrawal' => 'accounts',
            'account_transfer' => 'accounts',
            'income' => 'accounts',
            'income_category' => 'accounts',
            'expense' => 'accounts',
            'expense_category' => 'accounts',

            'staff' => 'staff',
            'staff_role' => 'staff',
            'staff_permission' => 'staff',

            'settings_and_privacy' => 'settings',

            'audit_report_meta' => 'audit',
            'cooperative_audit_report' => 'audit',
            'internal_audit_report' => 'audit',

            'recycle_bin' => 'recycle_bin',
        ];
    }

    /**
     * Resolve parent category key.
     */
    public static function resolve(?string $groupName, ?string $requestedParentCategory = null): string
    {
        if (is_string($requestedParentCategory) && trim($requestedParentCategory) !== '') {
            return (string) Str::of($requestedParentCategory)->trim()->snake();
        }

        if (!is_string($groupName) || trim($groupName) === '') {
            return self::DEFAULT_PARENT_CATEGORY;
        }

        return self::map()[$groupName] ?? self::DEFAULT_PARENT_CATEGORY;
    }
}
