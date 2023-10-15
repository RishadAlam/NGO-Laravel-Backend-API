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
        Schema::create('category_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnUpdate('cascade')->cascadeOnDelete('cascade');
            $table->smallInteger('saving_acc_reg_fee')->default(0);
            $table->smallInteger('saving_acc_closing_fee')->default(0);
            $table->smallInteger('loan_acc_reg_fee')->default(0);
            $table->smallInteger('loan_acc_closing_fee')->default(0);
            $table->smallInteger('saving_withdrawal_fee')->default(0);
            $table->smallInteger('loan_saving_withdrawal_fee')->default(0);
            $table->smallInteger('min_saving_withdrawal')->default(0);
            $table->smallInteger('max_saving_withdrawal')->default(0);
            $table->smallInteger('min_loan_saving_withdrawal')->default(0);
            $table->smallInteger('max_loan_saving_withdrawal')->default(0);
            $table->smallInteger('saving_acc_check_time_period')->default(0);
            $table->smallInteger('loan_acc_check_time_period')->default(0);
            $table->boolean('disable_unchecked_saving_acc')->default(false);
            $table->boolean('disable_unchecked_loan_acc')->default(false);
            $table->smallInteger('inactive_saving_acc_disable_time_period')->default(0);
            $table->smallInteger('inactive_loan_acc_disable_time_period')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_configs');
    }
};
