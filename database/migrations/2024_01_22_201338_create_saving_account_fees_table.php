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
        Schema::create('saving_account_fees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('saving_account_id')->constrained()->cascadeOnUpdate('cascade')->cascadeOnDelete('cascade');
            $table->foreignId('account_fees_category_id')->constrained()->cascadeOnUpdate('cascade')->cascadeOnDelete('cascade');
            $table->foreignId('creator_id')->constrained('users')->cascadeOnUpdate('cascade')->cascadeOnDelete('cascade');
            $table->integer('amount');
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saving_account_fees');
    }
};
