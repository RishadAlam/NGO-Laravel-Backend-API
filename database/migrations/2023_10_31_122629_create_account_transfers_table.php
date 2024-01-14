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
        Schema::create('account_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tx_acc_id')->constrained('accounts', 'id')->cascadeOnUpdate('cascade')->cascadeOnDelete('cascade')->comment('Transaction Sended Account');
            $table->foreignId('rx_acc_id')->constrained('accounts', 'id')->cascadeOnUpdate('cascade')->cascadeOnDelete('cascade')->comment('Transaction Received Account');
            $table->integer('amount');
            $table->integer('tx_prev_balance');
            $table->integer('tx_balance')->storedAs('tx_prev_balance - amount');
            $table->integer('rx_prev_balance');
            $table->integer('rx_balance')->storedAs('rx_prev_balance + amount');
            $table->mediumText('description')->nullable();
            $table->timestamp('date')->useCurrent();
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
        Schema::dropIfExists('account_transfers');
    }
};
