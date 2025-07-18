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

            // Shorter constraint name
            $table->unsignedBigInteger('loan_saving_withdrawal_id');
            $table->foreign('loan_saving_withdrawal_id', 'lswah_lsw_id_fk')
                ->references('id')
                ->on('loan_saving_withdrawals')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('author_id')
                ->nullable()
                ->constrained('users', 'id')
                ->cascadeOnUpdate()
                ->nullOnDelete();

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
