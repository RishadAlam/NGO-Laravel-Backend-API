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
        Schema::create('loan_saving_withdrawal_action_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_saving_withdrawal_id')->constrained('loan_saving_withdrawals')->cascadeOnUpdate('cascade')->cascadeOnDelete('cascade');
            $table->foreignId('author_id')->nullable()->constrained('users', 'id')->cascadeOnUpdate('cascade')->nullOnDelete();
            $table->string('name');
            $table->string('image_uri')->nullable();
            $table->enum('action_type', ['update', 'delete', 'restore']);
            $table->json('action_details');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_saving_withdrawal_action_histories');
    }
};
