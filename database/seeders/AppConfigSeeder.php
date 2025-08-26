<?php

namespace Database\Seeders;

use App\Models\AppConfig;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class AppConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $configurations = [
            [
                "meta_key"      => "company_details",
                "meta_value"    => json_encode([
                    "company_name"          => "আমার সমিতি",
                    "company_short_name"    => "আমার সমিতি",
                    "company_address"       => "",
                    "company_logo"          => null,
                    "company_logo_uri"      => null,
                ])
            ],
            [
                "meta_key"      => "saving_collection_approval",
                "meta_value"    => json_encode(false)
            ],
            [
                "meta_key"      => "loan_collection_approval",
                "meta_value"    => json_encode(false)
            ],
            [
                "meta_key"      => "account_transactions_approval",
                "meta_value"    => json_encode(false)
            ],
            [
                "meta_key"      => "money_withdrawal_approval",
                "meta_value"    => json_encode(false)
            ],
            [
                "meta_key"      => "client_registration_approval",
                "meta_value"    => json_encode(false)
            ],
            [
                "meta_key"      => "saving_account_registration_approval",
                "meta_value"    => json_encode(false)
            ],
            [
                "meta_key"      => "saving_account_closing_approval",
                "meta_value"    => json_encode(false)
            ],
            [
                "meta_key"      => "loan_account_registration_approval",
                "meta_value"    => json_encode(false)
            ],
            [
                "meta_key"      => "loan_approval",
                "meta_value"    => json_encode(false)
            ],
            [
                "meta_key"      => "loan_account_closing_approval",
                "meta_value"    => json_encode(false)
            ],
            [
                "meta_key"      => "client_reg_sign_is_required",
                "meta_value"    => json_encode(true)
            ],
            [
                "meta_key"      => "nominee_reg_sign_is_required",
                "meta_value"    => json_encode(true)
            ],
            [
                "meta_key"      => "guarantor_reg_sign_is_required",
                "meta_value"    => json_encode(true)
            ],
            [
                "meta_key"      => "client_reg_fee",
                "meta_value"    => 0
            ],
            [
                "meta_key"      => "money_transfer_transaction",
                "meta_value"    => json_encode([
                    "saving_to_saving"              => [
                        "fee"                       => 0,
                        "fee_store_acc_id"          => 1,
                        "min"                       => 0,
                        "max"                       => 0,
                    ],
                    "saving_to_loan_saving"         => [
                        "fee"                       => 0,
                        "fee_store_acc_id"          => 1,
                        "min"                       => 0,
                        "max"                       => 0,
                    ],
                    "loan_saving_to_loan_saving"    => [
                        "fee"                       => 0,
                        "fee_store_acc_id"          => 1,
                        "min"                       => 0,
                        "max"                       => 0,
                    ],
                    "loan_saving_to_saving"         => [
                        "fee"                       => 0,
                        "fee_store_acc_id"          => 1,
                        "min"                       => 0,
                        "max"                       => 0,
                    ],
                ])
            ]
        ];

        AppConfig::insert($configurations);
    }
}
