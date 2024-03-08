<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Audit\AuditReportPage;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class AuditReportPageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pages = [
            [
                'creator_id'    => 1,
                'name'          => 'deposit_expenditure',
                'is_default'    => true,
                'created_at'    => now(),
                'updated_at'    => now()
            ], [
                'creator_id'    => 1,
                'name'          => 'profit_loss',
                'is_default'    => true,
                'created_at'    => now(),
                'updated_at'    => now()
            ], [
                'creator_id'    => 1,
                'name'          => 'net_profit',
                'is_default'    => true,
                'created_at'    => now(),
                'updated_at'    => now()
            ], [
                'creator_id'    => 1,
                'name'          => 'surplus_value',
                'is_default'    => true,
                'created_at'    => now(),
                'updated_at'    => now()
            ],
        ];

        AuditReportPage::insert($pages);
    }
}
