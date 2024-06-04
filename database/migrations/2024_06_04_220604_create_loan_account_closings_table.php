<?php

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
        Schema::create('loan_account_closings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_account_id')->constrained()->cascadeOnUpdate('cascade')->cascadeOnDelete('cascade');
            $table->foreignId('creator_id')->constrained('users')->cascadeOnUpdate('cascade')->cascadeOnDelete('cascade');
            $table->foreignId('approved_by')->nullable()->constrained('users')->cascadeOnUpdate('cascade')->nullOnDelete();
            $table->foreignId('account_id')->nullable()->constrained()->cascadeOnUpdate('cascade')->nullOnDelete();
            $table->integer('payable_installment');
            $table->integer('total_rec_installment')->comment('Total recovered installment');
            $table->integer('balance');
            $table->integer('loan_given');
            $table->integer('total_loan_rec');
            $table->integer('total_loan_remaining');
            $table->integer('total_payable_interest')->comment('interest in "$" currency');
            $table->integer('total_interest_rec');
            $table->integer('total_interest_remaining');
            $table->mediumText('description')->nullable();
            $table->boolean('is_approved')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_account_closings');
    }
};
