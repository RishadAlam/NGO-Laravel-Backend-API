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
        Schema::create('saving_account_closings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('saving_account_id')->constrained()->cascadeOnUpdate('cascade')->cascadeOnDelete('cascade');
            $table->foreignId('creator_id')->constrained('users')->cascadeOnUpdate('cascade')->cascadeOnDelete('cascade');
            $table->foreignId('approved_by')->nullable()->constrained('users')->cascadeOnUpdate('cascade')->nullOnDelete();
            $table->foreignId('account_id')->nullable()->constrained()->cascadeOnUpdate('cascade')->nullOnDelete();
            $table->integer('balance');
            $table->integer('interest');
            $table->integer('total_balance');
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
        Schema::dropIfExists('saving_account_closings');
    }
};
