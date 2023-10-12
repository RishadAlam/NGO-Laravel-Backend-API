<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('group');
            $table->mediumText('description')->nullable();
            $table->boolean('saving')->default(false);
            $table->boolean('loan')->default(false);
            $table->boolean('status')->default(true);
            $table->boolean('is_default')->default(false);
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
        Schema::dropIfExists('categories');
    }
};
