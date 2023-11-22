<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('loan_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('field_id')->constrained()->cascadeOnUpdate('cascade')->cascadeOnDelete('cascade');
            $table->foreignId('center_id')->constrained()->cascadeOnUpdate('cascade')->cascadeOnDelete('cascade');
            $table->foreignId('category_id')->constrained()->cascadeOnUpdate('cascade')->cascadeOnDelete('cascade');
            $table->foreignId('client_registration_id')->constrained()->cascadeOnUpdate('cascade')->cascadeOnDelete('cascade');
            $table->string('acc_no', 50);
            $table->date('start_date')->default(DB::raw('NOW()'));
            $table->date('duration_date');
            $table->integer('loan_given')->default(0);
            $table->integer('payable_deposit')->default(0);
            $table->tinyInteger('payable_installment')->default(0);
            $table->tinyInteger('payable_interest')->default(0)->comment('interest in "%" percentage');
            $table->tinyInteger('total_payable_interest')->default(0)->comment('interest in "$" currency');
            $table->integer('total_payable_loan_with_interest')->default(0);
            $table->integer('loan_installment')->default(0)->comment('The loan must be repaid in each installment');
            $table->integer('interest_installment')->default(0)->comment('The interest must be repaid in each installment');
            $table->integer('total_rec_installment')->default(0)->comment('Total recovered installment');
            $table->integer('total_deposited')->default(0);
            $table->integer('total_withdrawn')->default(0);
            $table->integer('balance')->storedAs('total_deposited - total_withdrawn')->comment('balance = total_deposited - total_withdrawn');
            $table->integer('total_loan_rec')->default(0);
            $table->integer('total_loan_remaining')->storedAs('loan_given - total_loan_rec')->comment('balance = loan_given - total_loan_rec');
            $table->integer('total_interest_rec')->default(0);
            $table->integer('total_interest_remaining')->storedAs('total_payable_interest - total_interest_rec')->comment('balance = total_payable_interest - total_interest_rec');
            $table->integer('closing_balance')->nullable();
            $table->string('description')->nullable();
            $table->enum('status', [0, 1, 2])->default(1)->comment('deactivate = 0, activate = 1, hold = 2');
            $table->boolean('is_approved')->default(false);
            $table->foreignId('creator_id')->nullable()->constrained('users', 'id')->cascadeOnUpdate('cascade')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_accounts');
    }
};
