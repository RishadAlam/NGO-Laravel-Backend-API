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
        Schema::create('saving_account_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('saving_account_id')->constrained()->cascadeOnUpdate('cascade')->cascadeOnDelete('cascade');
            $table->foreignId('checked_by')->nullable()->constrained('users')->cascadeOnUpdate('cascade')->nullOnDelete();
            $table->integer('installment_recovered');
            $table->integer('balance');
            $table->mediumText('description')->nullable();
            $table->boolean('status')->default(false);
            $table->timestamp('next_check_in_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saving_account_checks');
    }
};
