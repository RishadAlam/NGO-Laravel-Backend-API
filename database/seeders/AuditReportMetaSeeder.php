<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Audit\AuditReportMeta;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class AuditReportMetaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $meta_keys = [
            [
                'creator_id'    => 1,
                'meta_key'      => 'collection_of_shares',
                'page_no'       => 1,
                'column_no'     => 1,
                'is_default'    => true,
                'created_at'    => now(),
                'updated_at'    => now()
            ], [
                'creator_id'    => 1,
                'meta_key'      => 'collection_of_savings_deposits',
                'page_no'       => 1,
                'column_no'     => 1,
                'is_default'    => true,
                'created_at'    => now(),
                'updated_at'    => now()
            ], [
                'creator_id'    => 1,
                'meta_key'      => 'collection_of_loans',
                'page_no'       => 1,
                'column_no'     => 1,
                'is_default'    => true,
                'created_at'    => now(),
                'updated_at'    => now()
            ], [
                'creator_id'    => 1,
                'meta_key'      => 'collection_of_fixed_deposits',
                'page_no'       => 1,
                'column_no'     => 1,
                'is_default'    => true,
                'created_at'    => now(),
                'updated_at'    => now()
            ], [
                'creator_id'    => 1,
                'meta_key'      => 'collection_of_loan_interests',
                'page_no'       => 1,
                'column_no'     => 1,
                'is_default'    => true,
                'created_at'    => now(),
                'updated_at'    => now()
            ], [
                'creator_id'    => 1,
                'meta_key'      => 'registration_fee',
                'page_no'       => 1,
                'column_no'     => 1,
                'is_default'    => true,
                'created_at'    => now(),
                'updated_at'    => now()
            ], [
                'creator_id'    => 1,
                'meta_key'      => 'loan_form',
                'page_no'       => 1,
                'column_no'     => 1,
                'is_default'    => true,
                'created_at'    => now(),
                'updated_at'    => now()
            ], [
                'creator_id'    => 1,
                'meta_key'      => 'closing_fee',
                'page_no'       => 1,
                'column_no'     => 1,
                'is_default'    => true,
                'created_at'    => now(),
                'updated_at'    => now()
            ],
        ];

        AuditReportMeta::insert($meta_keys);
    }
}
