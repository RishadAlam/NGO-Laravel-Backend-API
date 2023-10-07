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
        // $config = [
        //     [
        //         "meta_key"      => "company_name",
        //         "meta_value"    => json_encode("")
        //     ], [
        //         "meta_key"      => "company_short_name",
        //         "meta_value"    => json_encode("")
        //     ], [
        //         "meta_key"      => "company_address",
        //         "meta_value"    => json_encode("")
        //     ], [
        //         "meta_key"      => "company_logo",
        //         "meta_value"    => json_encode("")
        //     ], [
        //         "meta_key"      => "company_logo_uri",
        //         "meta_value"    => json_encode("")
        //     ], [
        //         "meta_key"      => "has_collection_time_is_enabled",
        //         "meta_value"    => json_encode("")
        //     ], [
        //         "meta_key"      => "collection_start_time",
        //         "meta_value"    => json_encode("")
        //     ], [
        //         "meta_key"      => "collection_end_time",
        //         "meta_value"    => json_encode("")
        //     ], [
        //         "meta_key"      => "saving_collection_approval",
        //         "meta_value"    => json_encode("")
        //     ], [
        //         "meta_key"      => "loan_collection_approval",
        //         "meta_value"    => json_encode("")
        //     ], [
        //         "meta_key"      => "money_exchange_approval",
        //         "meta_value"    => json_encode("")
        //     ], [
        //         "meta_key"      => "money_withdrawal_approval",
        //         "meta_value"    => json_encode("")
        //     ], [
        //         "meta_key"      => "saving_account_registration_approval",
        //         "meta_value"    => json_encode("")
        //     ], [
        //         "meta_key"      => "saving_account_closing_approval",
        //         "meta_value"    => json_encode("")
        //     ], [
        //         "meta_key"      => "loan_account_registration_approval",
        //         "meta_value"    => json_encode("")
        //     ], [
        //         "meta_key"      => "loan_account_closing_approval",
        //         "meta_value"    => json_encode("")
        //     ], [
        //         "meta_key"      => "saving_account_registration_fee",
        //         "meta_value"    => json_encode("")
        //     ], [
        //         "meta_key"      => "saving_account_closing_fee",
        //         "meta_value"    => json_encode("")
        //     ], [
        //         "meta_key"      => "loan_account_registration_fee",
        //         "meta_value"    => json_encode("")
        //     ], [
        //         "meta_key"      => "loan_account_closing_fee",
        //         "meta_value"    => json_encode("")
        //     ], [
        //         "meta_key"      => "withdrawal_fee",
        //         "meta_value"    => json_encode("")
        //     ], [
        //         "meta_key"      => "money_exchange_transaction_fee",
        //         "meta_value"    => json_encode("")
        //     ], [
        //         "meta_key"      => "saving_account_check_time_period",
        //         "meta_value"    => json_encode("")
        //     ], [
        //         "meta_key"      => "loan_account_check_time_period",
        //         "meta_value"    => json_encode("")
        //     ], [
        //         "meta_key"      => "saving_account_disable_time_period",
        //         "meta_value"    => json_encode("")
        //     ], [
        //         "meta_key"      => "loan_account_disable_time_period",
        //         "meta_value"    => json_encode("")
        //     ],
        // ];

        $configurations = [
            [
                "meta_key"      => "company_details",
                "meta_value"    => json_encode([
                    "company_name"          => "Jonokollan kormojibi co-oparetive society ltd",
                    "company_short_name"    => "Jonokollan",
                    "company_address"       => "",
                    "company_logo"          => "",
                    "company_logo_uri"      => "",
                ])
            ], [
                "meta_key"      => "collection_time",
                "meta_value"    => json_encode([
                    "has_collection_time_is_enabled"    => false,
                    "collection_start_time"             => "",
                    "collection_end_time"               => "",
                ])
            ], [
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
            ],[
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
                "meta_key"      => "saving_account_registration_fee",
                "meta_value"    => json_encode([])
            ], [
                "meta_key"      => "saving_account_closing_fee",
                "meta_value"    => json_encode([])
            ], [
                "meta_key"      => "loan_account_registration_fee",
                "meta_value"    => json_encode([])
            ], [
                "meta_key"      => "loan_account_closing_fee",
                "meta_value"    => json_encode([])
            ], [
                "meta_key"      => "withdrawal_fee",
                "meta_value"    => json_encode(0)
            ], [
                "meta_key"      => "money_exchange_transaction_fee",
                "meta_value"    => json_encode(0)
            ], [
                "meta_key"      => "saving_account_check_time_period",
                "meta_value"    => json_encode([])
            ], [
                "meta_key"      => "loan_account_check_time_period",
                "meta_value"    => json_encode([])
            ], [
                "meta_key"      => "saving_account_disable_time_period",
                "meta_value"    => json_encode([])
            ], [
                "meta_key"      => "loan_account_disable_time_period",
                "meta_value"    => json_encode([])
            ]
        ];

        AppConfig::insert($configurations);
    }
}
