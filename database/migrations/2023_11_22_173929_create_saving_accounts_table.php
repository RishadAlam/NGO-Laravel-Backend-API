<?php

use Illuminate\Support\Facades\DB;
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
        Schema::create('saving_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('field_id')->constrained()->cascadeOnUpdate('cascade')->cascadeOnDelete('cascade');
            $table->foreignId('center_id')->constrained()->cascadeOnUpdate('cascade')->cascadeOnDelete('cascade');
            $table->foreignId('category_id')->constrained()->cascadeOnUpdate('cascade')->cascadeOnDelete('cascade');
            $table->foreignId('client_registration_id')->constrained()->cascadeOnUpdate('cascade')->cascadeOnDelete('cascade');
            $table->string('acc_no', 50);
            $table->date('start_date')->default(DB::raw('NOW()'));
            $table->date('duration_date');
            $table->integer('payable_installment')->default(0);
            $table->integer('payable_deposit')->default(0);
            $table->integer('payable_interest')->default(0)->comment('interest in "%" percentage');
            $table->integer('total_deposit_without_interest')->default(0);
            $table->integer('total_deposit_with_interest')->default(0);
            $table->integer('total_installment')->default(0);
            $table->integer('total_deposited')->default(0);
            $table->integer('total_withdrawn')->default(0);
            $table->integer('balance')->storedAs('total_deposited - total_withdrawn')->comment('balance = total_deposited - total_withdrawn');
            $table->integer('closing_balance')->nullable();
            $table->integer('closing_interest')->nullable();
            $table->integer('closing_balance_with_interest')->nullable();
            $table->string('description')->nullable();
            $table->enum('status', [0, 1, 2])->default(1)->comment('deactivate = 0, activate = 1, hold = 2');
            $table->boolean('is_approved')->default(false);
            $table->foreignId('creator_id')->constrained('users')->cascadeOnUpdate('cascade')->cascadeOnDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saving_accounts');
    }
};
