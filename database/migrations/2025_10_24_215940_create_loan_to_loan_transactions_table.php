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
        Schema::create('loan_to_loan_transactions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('creator_id')->constrained('users')->cascadeOnUpdate('cascade')->cascadeOnDelete('cascade');
            $table->foreignId('approved_by')->nullable()->constrained('users')->cascadeOnUpdate('cascade')->nullOnDelete();

            $table->foreignId('tx_acc_id')->constrained('loan_accounts', 'id')->cascadeOnUpdate('cascade')->cascadeOnDelete('cascade')->comment('Transaction Sended Account');
            $table->foreignId('rx_acc_id')->constrained('saving_accounts', 'id')->cascadeOnUpdate('cascade')->cascadeOnDelete('cascade')->comment('Transaction Received Account');

            $table->integer('amount');

            $table->integer('tx_prev_balance');
            $table->integer('tx_balance')->storedAs('tx_prev_balance - amount');

            $table->integer('rx_prev_balance');
            $table->integer('rx_balance')->storedAs('rx_prev_balance + amount');

            $table->mediumText('description')->nullable();

            $table->boolean('is_approved')->default(false);

            $table->timestamp('approved_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_to_loan_transactions');
    }
};
