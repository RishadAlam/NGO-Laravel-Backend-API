<?php

namespace Database\Seeders;

use App\Models\AppConfig;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

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
                    "company_name"          => "Jonokollan kormojibi co-oparetive society ltd",
                    "company_short_name"    => "Jonokollan",
                    "company_address"       => "",
                    "company_logo"          => "logo_1695993860.png",
                    "company_logo_uri"      => "http:\/\/127.0.0.1:8000\/storage\/config\/logo_1699030624.png",
                ])
            ],
            // [
            //     "meta_key"      => "collection_time",
            //     "meta_value"    => json_encode([
            //         "has_collection_time_is_enabled"    => false,
            //         "collection_start_time"             => "",
            //         "collection_end_time"               => "",
            //     ])
            // ],
            [
                "meta_key"      => "saving_collection_approval",
                "meta_value"    => json_encode(true)
            ], [
                "meta_key"      => "loan_collection_approval",
                "meta_value"    => json_encode(true)
            ], [
                "meta_key"      => "money_exchange_approval",
                "meta_value"    => json_encode(true)
            ], [
                "meta_key"      => "money_withdrawal_approval",
                "meta_value"    => json_encode(true)
            ], [
                "meta_key"      => "client_registration_approval",
                "meta_value"    => json_encode(true)
            ], [
                "meta_key"      => "saving_account_registration_approval",
                "meta_value"    => json_encode(true)
            ], [
                "meta_key"      => "saving_account_closing_approval",
                "meta_value"    => json_encode(true)
            ], [
                "meta_key"      => "loan_account_registration_approval",
                "meta_value"    => json_encode(true)
            ], [
                "meta_key"      => "loan_account_closing_approval",
                "meta_value"    => json_encode(true)
            ], [
                "meta_key"      => "client_reg_sign_is_required",
                "meta_value"    => json_encode(true)
            ], [
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
