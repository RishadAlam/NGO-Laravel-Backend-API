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
        Schema::create('client_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('field_id')->constrained()->cascadeOnUpdate('cascade')->cascadeOnDelete('cascade');
            $table->foreignId('center_id')->constrained()->cascadeOnUpdate('cascade')->cascadeOnDelete('cascade');
            $table->foreignId('creator_id')->nullable()->constrained('users', 'id')->cascadeOnUpdate('cascade')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->cascadeOnUpdate('cascade')->nullOnDelete();
            $table->string('acc_no', 50)->unique();
            $table->string('name');
            $table->string('father_name')->nullable();
            $table->string('husband_name')->nullable();
            $table->string('mother_name');
            $table->string('nid', 50)->unique();
            $table->date('dob');
            $table->string('occupation');
            $table->enum('religion', ['islam', 'hindu', 'christian', 'Buddhist', 'others']);
            $table->enum('gender', ['male', 'female', 'others']);
            $table->string('primary_phone', 20);
            $table->string('secondary_phone', 20)->nullable();
            $table->string('image');
            $table->string('image_uri');
            $table->string('signature')->nullable();
            $table->string('signature_uri')->nullable();
            $table->string('annual_income', 50)->nullable();
            $table->string('bank_acc_no', 50)->nullable();
            $table->string('bank_check_no', 50)->nullable();
            $table->integer('share')->default(0);
            $table->json('present_address');
            $table->json('permanent_address');
            $table->boolean('is_approved')->default(false);
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_registrations');
    }
};
