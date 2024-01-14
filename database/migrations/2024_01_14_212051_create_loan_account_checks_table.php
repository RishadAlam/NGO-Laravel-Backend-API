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
        Schema::create('loan_account_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_account_id')->constrained()->cascadeOnUpdate('cascade')->cascadeOnDelete('cascade');
            $table->foreignId('checked_by')->nullable()->constrained('users')->cascadeOnUpdate('cascade')->nullOnDelete();
            $table->integer('installment_recovered');
            $table->integer('installment_remaining');
            $table->integer('balance');
            $table->integer('loan_recovered');
            $table->integer('loan_remaining');
            $table->integer('interest_recovered');
            $table->integer('interest_remaining');
            $table->mediumText('description')->nullable();
            $table->timestamp('next_check_in_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_account_checks');
    }
};
