<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('app_configs', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->string('company_short_name');
            $table->string('company_address');
            $table->string('company_logo');
            $table->string('company_logo_uri');
            $table->boolean('has_collection_time_is_enabled');
            $table->string('collection_start_time')->nullable();
            $table->string('collection_end_time')->nullable();
            $table->boolean('saving_collection_approval');
            $table->boolean('loan_collection_approval');
            $table->boolean('money_exchange_approval');
            $table->boolean('money_withdrawal_approval');
            $table->boolean('saving_account_registration_approval');
            $table->boolean('saving_account_closing_approval');
            $table->boolean('loan_account_registration_approval');
            $table->boolean('loan_account_closing_approval');
            $table->json('saving_account_registration_fee');
            $table->json('saving_account_closing_fee');
            $table->json('loan_account_registration_fee');
            $table->json('loan_account_closing_fee');
            $table->json('withdrawal_fee');
            $table->integer('money_exchange_transaction_fee');
            $table->json('saving_account_check_time_period');
            $table->json('loan_account_check_time_period');
            $table->json('saving_account_disable_time_period');
            $table->json('loan_account_disable_time_period');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_configs');
    }
};
