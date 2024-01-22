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
        Schema::create('account_fees_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('creator_id')->nullable()->constrained('users')->cascadeOnUpdate('cascade')->nullOnDelete();
            $table->string('name')->unique();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_fees_categories');
    }
};
