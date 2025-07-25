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
        Schema::create('account_withdrawals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->cascadeOnUpdate('cascade')->cascadeOnDelete('cascade');
            $table->foreignId('creator_id')->nullable()->constrained('users', 'id')->cascadeOnUpdate('cascade')->nullOnDelete();
            $table->integer('amount');
            $table->integer('previous_balance');
            $table->integer('balance')->storedAs('previous_balance - amount');
            $table->mediumText('description');
            $table->timestamp('date')->useCurrent();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_withdrawals');
    }
};
