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
        Schema::create('account_withdrawal_action_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('withdrawal_id')->constrained('account_withdrawals', 'id')->cascadeOnUpdate('cascade')->cascadeOnDelete('cascade');
            $table->foreignId('author_id')->nullable()->constrained('users', 'id')->cascadeOnUpdate('cascade')->nullOnDelete();
            $table->string('name');
            $table->string('image_uri');
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
        Schema::dropIfExists('account_withdrawal_action_histories');
    }
};
