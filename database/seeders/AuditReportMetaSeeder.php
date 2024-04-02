<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Audit\AuditReportMeta;
use App\Models\Audit\AuditReportPage;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class AuditReportMetaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $deposit_expenditure    = AuditReportPage::where('name', 'deposit_expenditure')->value('id');
        $profit_loss            = AuditReportPage::where('name', 'profit_loss')->value('id');
        $net_profit             = AuditReportPage::where('name', 'net_profit')->value('id');
        $surplus_value          = AuditReportPage::where('name', 'surplus_value')->value('id');

        $meta_keys = [
            // [
            //     'creator_id'            => 1,
            //     'meta_key'              => 'collection_of_shares',
            //     'audit_report_page_id'  => $deposit_expenditure,
            //     'column_no'             => 1,
            //     'is_default'            => true,
            //     'created_at'            => now(),
            //     'updated_at'            => now()
            // ], [
            //     'creator_id'            => 1,
            //     'meta_key'              => 'collection_of_savings_deposits',
            //     'audit_report_page_id'  => $deposit_expenditure,
            //     'column_no'             => 1,
            //     'is_default'            => true,
            //     'created_at'            => now(),
            //     'updated_at'            => now()
            // ], [
            //     'creator_id'            => 1,
            //     'meta_key'              => 'collection_of_loans',
            //     'audit_report_page_id'  => $deposit_expenditure,
            //     'column_no'             => 1,
            //     'is_default'            => true,
            //     'created_at'            => now(),
            //     'updated_at'            => now()
            // ], [
            //     'creator_id'            => 1,
            //     'meta_key'              => 'collection_of_fixed_deposits',
            //     'audit_report_page_id'  => $deposit_expenditure,
            //     'column_no'             => 1,
            //     'is_default'            => true,
            //     'created_at'            => now(),
            //     'updated_at'            => now()
            // ], [
            //     'creator_id'            => 1,
            //     'meta_key'              => 'collection_of_loan_interests',
            //     'audit_report_page_id'  => $deposit_expenditure,
            //     'column_no'             => 1,
            //     'is_default'            => true,
            //     'created_at'            => now(),
            //     'updated_at'            => now()
            // ], [
            //     'creator_id'            => 1,
            //     'meta_key'              => 'registration_fee',
            //     'audit_report_page_id'  => $deposit_expenditure,
            //     'column_no'             => 1,
            //     'is_default'            => true,
            //     'created_at'            => now(),
            //     'updated_at'            => now()
            // ], [
            //     'creator_id'            => 1,
            //     'meta_key'              => 'loan_form',
            //     'audit_report_page_id'  => $deposit_expenditure,
            //     'column_no'             => 1,
            //     'is_default'            => true,
            //     'created_at'            => now(),
            //     'updated_at'            => now()
            // ], [
            //     'creator_id'            => 1,
            //     'meta_key'              => 'closing_fee',
            //     'audit_report_page_id'  => $deposit_expenditure,
            //     'column_no'             => 1,
            //     'is_default'            => true,
            //     'created_at'            => now(),
            //     'updated_at'            => now()
            // ], [
            //     'creator_id'            => 1,
            //     'meta_key'              => 'shares_return',
            //     'audit_report_page_id'  => $deposit_expenditure,
            //     'column_no'             => 2,
            //     'is_default'            => true,
            //     'created_at'            => now(),
            //     'updated_at'            => now()
            // ],  [
            //     'creator_id'            => 1,
            //     'meta_key'              => 'savings_return',
            //     'audit_report_page_id'  => $deposit_expenditure,
            //     'column_no'             => 2,
            //     'is_default'            => true,
            //     'created_at'            => now(),
            //     'updated_at'            => now()
            // ], [
            //     'creator_id'            => 1,
            //     'meta_key'              => 'loan_given',
            //     'audit_report_page_id'  => $deposit_expenditure,
            //     'column_no'             => 2,
            //     'is_default'            => true,
            //     'created_at'            => now(),
            //     'updated_at'            => now()
            // ],  [
            //     'creator_id'            => 1,
            //     'meta_key'              => 'collection_of_loan_interests',
            //     'audit_report_page_id'  => $profit_loss,
            //     'column_no'             => 2,
            //     'is_default'            => true,
            //     'created_at'            => now(),
            //     'updated_at'            => now()
            // ], [
            //     'creator_id'            => 1,
            //     'meta_key'              => 'registration_fee',
            //     'audit_report_page_id'  => $profit_loss,
            //     'column_no'             => 2,
            //     'is_default'            => true,
            //     'created_at'            => now(),
            //     'updated_at'            => now()
            // ], [
            //     'creator_id'            => 1,
            //     'meta_key'              => 'loan_form',
            //     'audit_report_page_id'  => $profit_loss,
            //     'column_no'             => 2,
            //     'is_default'            => true,
            //     'created_at'            => now(),
            //     'updated_at'            => now()
            // ], [
            //     'creator_id'            => 1,
            //     'meta_key'              => 'closing_fee',
            //     'audit_report_page_id'  => $profit_loss,
            //     'column_no'             => 2,
            //     'is_default'            => true,
            //     'created_at'            => now(),
            //     'updated_at'            => now()
            // ], [
            //     'creator_id'            => 1,
            //     'meta_key'              => 'reserve_fund',
            //     'audit_report_page_id'  => $net_profit,
            //     'column_no'             => 1,
            //     'is_default'            => true,
            //     'created_at'            => now(),
            //     'updated_at'            => now()
            // ], [
            //     'creator_id'            => 1,
            //     'meta_key'              => 'cooperative_dev_fund',
            //     'audit_report_page_id'  => $net_profit,
            //     'column_no'             => 1,
            //     'is_default'            => true,
            //     'created_at'            => now(),
            //     'updated_at'            => now()
            // ], [
            //     'creator_id'            => 1,
            //     'meta_key'              => 'undistributed_profits',
            //     'audit_report_page_id'  => $net_profit,
            //     'column_no'             => 1,
            //     'is_default'            => true,
            //     'created_at'            => now(),
            //     'updated_at'            => now()
            // ], [
            //     'creator_id'            => 1,
            //     'meta_key'              => 'current_year_net_profits',
            //     'audit_report_page_id'  => $net_profit,
            //     'column_no'             => 2,
            //     'is_default'            => true,
            //     'created_at'            => now(),
            //     'updated_at'            => now()
            // ],
            [
                'creator_id'            => 1,
                'meta_key'              => 'authorized_shares',
                'audit_report_page_id'  => $surplus_value,
                'column_no'             => 1,
                'is_default'            => true,
                'created_at'            => now(),
                'updated_at'            => now()
            ], [
                'creator_id'            => 1,
                'meta_key'              => 'share_per_each',
                'meta_value'            => 100,
                'audit_report_page_id'  => $surplus_value,
                'column_no'             => 1,
                'is_default'            => true,
                'created_at'            => now(),
                'updated_at'            => now()
            ],
            //  [
            //     'creator_id'            => 1,
            //     'meta_key'              => 'paid_up_shares',
            //     'audit_report_page_id'  => $surplus_value,
            //     'column_no'             => 1,
            //     'is_default'            => true,
            //     'created_at'            => now(),
            //     'updated_at'            => now()
            // ], [
            //     'creator_id'            => 1,
            //     'meta_key'              => 'previous_paid_up_shares',
            //     'audit_report_page_id'  => $surplus_value,
            //     'column_no'             => 1,
            //     'is_default'            => true,
            //     'created_at'            => now(),
            //     'updated_at'            => now()
            // ], [
            //     'creator_id'            => 1,
            //     'meta_key'              => 'current_share_collections',
            //     'audit_report_page_id'  => $surplus_value,
            //     'column_no'             => 1,
            //     'is_default'            => true,
            //     'created_at'            => now(),
            //     'updated_at'            => now()
            // ], [
            //     'creator_id'            => 1,
            //     'meta_key'              => 'current_share_return',
            //     'audit_report_page_id'  => $surplus_value,
            //     'column_no'             => 1,
            //     'is_default'            => true,
            //     'created_at'            => now(),
            //     'updated_at'            => now()
            // ], [
            //     'creator_id'            => 1,
            //     'meta_key'              => 'savings_deposit',
            //     'audit_report_page_id'  => $surplus_value,
            //     'column_no'             => 1,
            //     'is_default'            => true,
            //     'created_at'            => now(),
            //     'updated_at'            => now()
            // ], [
            //     'creator_id'            => 1,
            //     'meta_key'              => 'previous_savings_deposit',
            //     'audit_report_page_id'  => $surplus_value,
            //     'column_no'             => 1,
            //     'is_default'            => true,
            //     'created_at'            => now(),
            //     'updated_at'            => now()
            // ], [
            //     'creator_id'            => 1,
            //     'meta_key'              => 'current_saving_collections',
            //     'audit_report_page_id'  => $surplus_value,
            //     'column_no'             => 1,
            //     'is_default'            => true,
            //     'created_at'            => now(),
            //     'updated_at'            => now()
            // ], [
            //     'creator_id'            => 1,
            //     'meta_key'              => 'current_saving_return',
            //     'audit_report_page_id'  => $surplus_value,
            //     'column_no'             => 1,
            //     'is_default'            => true,
            //     'created_at'            => now(),
            //     'updated_at'            => now()
            // ], [
            //     'creator_id'            => 1,
            //     'meta_key'              => 'fixed_deposit',
            //     'audit_report_page_id'  => $surplus_value,
            //     'column_no'             => 1,
            //     'is_default'            => true,
            //     'created_at'            => now(),
            //     'updated_at'            => now()
            // ], [
            //     'creator_id'            => 1,
            //     'meta_key'              => 'previous_fixed_deposit',
            //     'audit_report_page_id'  => $surplus_value,
            //     'column_no'             => 1,
            //     'is_default'            => true,
            //     'created_at'            => now(),
            //     'updated_at'            => now()
            // ], [
            //     'creator_id'            => 1,
            //     'meta_key'              => 'current_fixed_deposit_collections',
            //     'audit_report_page_id'  => $surplus_value,
            //     'column_no'             => 1,
            //     'is_default'            => true,
            //     'created_at'            => now(),
            //     'updated_at'            => now()
            // ],
            [
                'creator_id'            => 1,
                'meta_key'              => 'accumulation_of_savings',
                'audit_report_page_id'  => $surplus_value,
                'column_no'             => 1,
                'is_default'            => true,
                'created_at'            => now(),
                'updated_at'            => now()
            ],
            // [
            //     'creator_id'            => 1,
            //     'meta_key'              => 'reserved_fund',
            //     'audit_report_page_id'  => $surplus_value,
            //     'column_no'             => 1,
            //     'is_default'            => true,
            //     'created_at'            => now(),
            //     'updated_at'            => now()
            // ], [
            //     'creator_id'            => 1,
            //     'meta_key'              => 'previous_reserved_fund',
            //     'audit_report_page_id'  => $surplus_value,
            //     'column_no'             => 1,
            //     'is_default'            => true,
            //     'created_at'            => now(),
            //     'updated_at'            => now()
            // ], [
            //     'creator_id'            => 1,
            //     'meta_key'              => 'current_reserved_fund',
            //     'audit_report_page_id'  => $surplus_value,
            //     'column_no'             => 1,
            //     'is_default'            => true,
            //     'created_at'            => now(),
            //     'updated_at'            => now()
            // ], [
            //     'creator_id'            => 1,
            //     'meta_key'              => 'cooperative_dev_fund',
            //     'audit_report_page_id'  => $surplus_value,
            //     'column_no'             => 1,
            //     'is_default'            => true,
            //     'created_at'            => now(),
            //     'updated_at'            => now()
            // ], [
            //     'creator_id'            => 1,
            //     'meta_key'              => 'previous_cooperative_dev_fund',
            //     'audit_report_page_id'  => $surplus_value,
            //     'column_no'             => 1,
            //     'is_default'            => true,
            //     'created_at'            => now(),
            //     'updated_at'            => now()
            // ], [
            //     'creator_id'            => 1,
            //     'meta_key'              => 'current_cooperative_dev_fund',
            //     'audit_report_page_id'  => $surplus_value,
            //     'column_no'             => 1,
            //     'is_default'            => true,
            //     'created_at'            => now(),
            //     'updated_at'            => now()
            // ], [
            //     'creator_id'            => 1,
            //     'meta_key'              => 'undistributed_profit',
            //     'audit_report_page_id'  => $surplus_value,
            //     'column_no'             => 1,
            //     'is_default'            => true,
            //     'created_at'            => now(),
            //     'updated_at'            => now()
            // ], [
            //     'creator_id'            => 1,
            //     'meta_key'              => 'previous_undistributed_profit',
            //     'audit_report_page_id'  => $surplus_value,
            //     'column_no'             => 1,
            //     'is_default'            => true,
            //     'created_at'            => now(),
            //     'updated_at'            => now()
            // ], [
            //     'creator_id'            => 1,
            //     'meta_key'              => 'current_undistributed_profit',
            //     'audit_report_page_id'  => $surplus_value,
            //     'column_no'             => 1,
            //     'is_default'            => true,
            //     'created_at'            => now(),
            //     'updated_at'            => now()
            // ], [
            //     'creator_id'            => 1,
            //     'meta_key'              => 'current_fund',
            //     'audit_report_page_id'  => $surplus_value,
            //     'column_no'             => 2,
            //     'is_default'            => true,
            //     'created_at'            => now(),
            //     'updated_at'            => now()
            // ], [
            //     'creator_id'            => 1,
            //     'meta_key'              => 'loan_owed',
            //     'audit_report_page_id'  => $surplus_value,
            //     'column_no'             => 2,
            //     'is_default'            => true,
            //     'created_at'            => now(),
            //     'updated_at'            => now()
            // ], [
            //     'creator_id'            => 1,
            //     'meta_key'              => 'previous_loan_owed',
            //     'audit_report_page_id'  => $surplus_value,
            //     'column_no'             => 1,
            //     'is_default'            => true,
            //     'created_at'            => now(),
            //     'updated_at'            => now()
            // ], [
            //     'creator_id'            => 1,
            //     'meta_key'              => 'current_loan_distribution',
            //     'audit_report_page_id'  => $surplus_value,
            //     'column_no'             => 1,
            //     'is_default'            => true,
            //     'created_at'            => now(),
            //     'updated_at'            => now()
            // ], [
            //     'creator_id'            => 1,
            //     'meta_key'              => 'current_loan_collections',
            //     'audit_report_page_id'  => $surplus_value,
            //     'column_no'             => 1,
            //     'is_default'            => true,
            //     'created_at'            => now(),
            //     'updated_at'            => now()
            // ],
            [
                'creator_id'            => 1,
                'meta_key'              => 'furniture',
                'audit_report_page_id'  => $surplus_value,
                'column_no'             => 1,
                'is_default'            => true,
                'created_at'            => now(),
                'updated_at'            => now()
            ],
            // [
            //     'creator_id'            => 1,
            //     'meta_key'              => 'net_loss',
            //     'audit_report_page_id'  => $surplus_value,
            //     'column_no'             => 1,
            //     'is_default'            => true,
            //     'created_at'            => now(),
            //     'updated_at'            => now()
            // ], [
            //     'creator_id'            => 1,
            //     'meta_key'              => 'previous_loss',
            //     'audit_report_page_id'  => $surplus_value,
            //     'column_no'             => 1,
            //     'is_default'            => true,
            //     'created_at'            => now(),
            //     'updated_at'            => now()
            // ], [
            //     'creator_id'            => 1,
            //     'meta_key'              => 'current_net_profit',
            //     'audit_report_page_id'  => $surplus_value,
            //     'column_no'             => 1,
            //     'is_default'            => true,
            //     'created_at'            => now(),
            //     'updated_at'            => now()
            // ]
        ];

        AuditReportMeta::insert($meta_keys);
    }
}
