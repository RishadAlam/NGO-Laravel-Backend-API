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
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('acc_no')->unique()->nullable();
            $table->string('acc_details')->nullable();
            $table->integer('total_deposit')->default(0);
            $table->integer('total_withdrawal')->default(0);
            $table->integer('balance')->storedAs('total_deposit - total_withdrawal');
            $table->boolean('is_default')->default(false);
            $table->boolean('status')->default(true);
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
        Schema::dropIfExists('accounts');
    }
};
