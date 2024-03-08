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
            [
                'creator_id'            => 1,
                'meta_key'              => 'collection_of_shares',
                'audit_report_page_id'  => $deposit_expenditure,
                'column_no'             => 1,
                'is_default'            => true,
                'created_at'            => now(),
                'updated_at'            => now()
            ], [
                'creator_id'            => 1,
                'meta_key'              => 'collection_of_savings_deposits',
                'audit_report_page_id'  => $deposit_expenditure,
                'column_no'             => 1,
                'is_default'            => true,
                'created_at'            => now(),
                'updated_at'            => now()
            ], [
                'creator_id'            => 1,
                'meta_key'              => 'collection_of_loans',
                'audit_report_page_id'  => $deposit_expenditure,
                'column_no'             => 1,
                'is_default'            => true,
                'created_at'            => now(),
                'updated_at'            => now()
            ], [
                'creator_id'            => 1,
                'meta_key'              => 'collection_of_fixed_deposits',
                'audit_report_page_id'  => $deposit_expenditure,
                'column_no'             => 1,
                'is_default'            => true,
                'created_at'            => now(),
                'updated_at'            => now()
            ], [
                'creator_id'            => 1,
                'meta_key'              => 'collection_of_loan_interests',
                'audit_report_page_id'  => $deposit_expenditure,
                'column_no'             => 1,
                'is_default'            => true,
                'created_at'            => now(),
                'updated_at'            => now()
            ], [
                'creator_id'            => 1,
                'meta_key'              => 'registration_fee',
                'audit_report_page_id'  => $deposit_expenditure,
                'column_no'             => 1,
                'is_default'            => true,
                'created_at'            => now(),
                'updated_at'            => now()
            ], [
                'creator_id'            => 1,
                'meta_key'              => 'loan_form',
                'audit_report_page_id'  => $deposit_expenditure,
                'column_no'             => 1,
                'is_default'            => true,
                'created_at'            => now(),
                'updated_at'            => now()
            ], [
                'creator_id'            => 1,
                'meta_key'              => 'closing_fee',
                'audit_report_page_id'  => $deposit_expenditure,
                'column_no'             => 1,
                'is_default'            => true,
                'created_at'            => now(),
                'updated_at'            => now()
            ], [
                'creator_id'            => 1,
                'meta_key'              => 'savings_return',
                'audit_report_page_id'  => $deposit_expenditure,
                'column_no'             => 2,
                'is_default'            => true,
                'created_at'            => now(),
                'updated_at'            => now()
            ], [
                'creator_id'            => 1,
                'meta_key'              => 'loan_given',
                'audit_report_page_id'  => $deposit_expenditure,
                'column_no'             => 2,
                'is_default'            => true,
                'created_at'            => now(),
                'updated_at'            => now()
            ], [
                'creator_id'            => 1,
                'meta_key'              => 'printing_stationery',
                'audit_report_page_id'  => $deposit_expenditure,
                'column_no'             => 2,
                'is_default'            => true,
                'created_at'            => now(),
                'updated_at'            => now()
            ], [
                'creator_id'            => 1,
                'meta_key'              => 'office_rent',
                'audit_report_page_id'  => $deposit_expenditure,
                'column_no'             => 2,
                'is_default'            => true,
                'created_at'            => now(),
                'updated_at'            => now()
            ], [
                'creator_id'            => 1,
                'meta_key'              => 'electricity_bill',
                'audit_report_page_id'  => $deposit_expenditure,
                'column_no'             => 2,
                'is_default'            => true,
                'created_at'            => now(),
                'updated_at'            => now()
            ], [
                'creator_id'            => 1,
                'meta_key'              => 'employee_salary',
                'audit_report_page_id'  => $deposit_expenditure,
                'column_no'             => 2,
                'is_default'            => true,
                'created_at'            => now(),
                'updated_at'            => now()
            ], [
                'creator_id'            => 1,
                'meta_key'              => 'travel_cost',
                'audit_report_page_id'  => $deposit_expenditure,
                'column_no'             => 2,
                'is_default'            => true,
                'created_at'            => now(),
                'updated_at'            => now()
            ], [
                'creator_id'            => 1,
                'meta_key'              => 'consumption',
                'audit_report_page_id'  => $deposit_expenditure,
                'column_no'             => 2,
                'is_default'            => true,
                'created_at'            => now(),
                'updated_at'            => now()
            ], [
                'creator_id'            => 1,
                'meta_key'              => 'others_expense',
                'audit_report_page_id'  => $deposit_expenditure,
                'column_no'             => 2,
                'is_default'            => true,
                'created_at'            => now(),
                'updated_at'            => now()
            ], [
                'creator_id'            => 1,
                'meta_key'              => 'printing_stationery',
                'audit_report_page_id'  => $profit_loss,
                'column_no'             => 1,
                'is_default'            => true,
                'created_at'            => now(),
                'updated_at'            => now()
            ], [
                'creator_id'            => 1,
                'meta_key'              => 'office_rent',
                'audit_report_page_id'  => $profit_loss,
                'column_no'             => 1,
                'is_default'            => true,
                'created_at'            => now(),
                'updated_at'            => now()
            ], [
                'creator_id'            => 1,
                'meta_key'              => 'electricity_bill',
                'audit_report_page_id'  => $profit_loss,
                'column_no'             => 1,
                'is_default'            => true,
                'created_at'            => now(),
                'updated_at'            => now()
            ], [
                'creator_id'            => 1,
                'meta_key'              => 'employee_salary',
                'audit_report_page_id'  => $profit_loss,
                'column_no'             => 1,
                'is_default'            => true,
                'created_at'            => now(),
                'updated_at'            => now()
            ], [
                'creator_id'            => 1,
                'meta_key'              => 'travel_cost',
                'audit_report_page_id'  => $profit_loss,
                'column_no'             => 1,
                'is_default'            => true,
                'created_at'            => now(),
                'updated_at'            => now()
            ], [
                'creator_id'            => 1,
                'meta_key'              => 'consumption',
                'audit_report_page_id'  => $profit_loss,
                'column_no'             => 1,
                'is_default'            => true,
                'created_at'            => now(),
                'updated_at'            => now()
            ], [
                'creator_id'            => 1,
                'meta_key'              => 'others_expense',
                'audit_report_page_id'  => $profit_loss,
                'column_no'             => 1,
                'is_default'            => true,
                'created_at'            => now(),
                'updated_at'            => now()
            ], [
                'creator_id'            => 1,
                'meta_key'              => 'collection_of_loan_interests',
                'audit_report_page_id'  => $profit_loss,
                'column_no'             => 2,
                'is_default'            => true,
                'created_at'            => now(),
                'updated_at'            => now()
            ], [
                'creator_id'            => 1,
                'meta_key'              => 'registration_fee',
                'audit_report_page_id'  => $profit_loss,
                'column_no'             => 2,
                'is_default'            => true,
                'created_at'            => now(),
                'updated_at'            => now()
            ], [
                'creator_id'            => 1,
                'meta_key'              => 'loan_form',
                'audit_report_page_id'  => $profit_loss,
                'column_no'             => 2,
                'is_default'            => true,
                'created_at'            => now(),
                'updated_at'            => now()
            ], [
                'creator_id'            => 1,
                'meta_key'              => 'closing_fee',
                'audit_report_page_id'  => $profit_loss,
                'column_no'             => 2,
                'is_default'            => true,
                'created_at'            => now(),
                'updated_at'            => now()
            ], [
                'creator_id'            => 1,
                'meta_key'              => 'reserve_fund',
                'audit_report_page_id'  => $net_profit,
                'column_no'             => 1,
                'is_default'            => true,
                'created_at'            => now(),
                'updated_at'            => now()
            ], [
                'creator_id'            => 1,
                'meta_key'              => 'cooperative_dev_fund',
                'audit_report_page_id'  => $net_profit,
                'column_no'             => 1,
                'is_default'            => true,
                'created_at'            => now(),
                'updated_at'            => now()
            ], [
                'creator_id'            => 1,
                'meta_key'              => 'undistributed_profits',
                'audit_report_page_id'  => $net_profit,
                'column_no'             => 1,
                'is_default'            => true,
                'created_at'            => now(),
                'updated_at'            => now()
            ], [
                'creator_id'            => 1,
                'meta_key'              => 'current_year_net_profits',
                'audit_report_page_id'  => $net_profit,
                'column_no'             => 2,
                'is_default'            => true,
                'created_at'            => now(),
                'updated_at'            => now()
            ],
        ];

        AuditReportMeta::insert($meta_keys);
    }
}
