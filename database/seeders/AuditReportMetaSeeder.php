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
                'meta_key'              => 'authorized_shares',
                'meta_value'            => 0,
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
            [
                'creator_id'            => 1,
                'meta_key'              => 'accumulation_of_savings',
                'meta_value'            => 0,
                'audit_report_page_id'  => $surplus_value,
                'column_no'             => 1,
                'is_default'            => true,
                'created_at'            => now(),
                'updated_at'            => now()
            ],
            [
                'creator_id'            => 1,
                'meta_key'              => 'furniture',
                'meta_value'            => 0,
                'audit_report_page_id'  => $surplus_value,
                'column_no'             => 1,
                'is_default'            => true,
                'created_at'            => now(),
                'updated_at'            => now()
            ],
        ];

        AuditReportMeta::insert($meta_keys);
    }
}
