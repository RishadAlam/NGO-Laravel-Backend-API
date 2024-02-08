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
        Schema::create('guarantors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_account_id')->constrained()->cascadeOnUpdate('cascade')->cascadeOnDelete('cascade');
            $table->string('name');
            $table->string('father_name');
            $table->string('husband_name')->nullable();
            $table->string('mother_name');
            $table->string('nid', 50);
            $table->date('dob');
            $table->string('occupation');
            $table->string('relation');
            $table->enum('gender', ['male', 'female', 'others']);
            $table->string('primary_phone', 20);
            $table->string('secondary_phone', 20)->nullable();
            $table->string('image')->nullable();
            $table->string('image_uri')->nullable();
            $table->string('signature')->nullable();
            $table->string('signature_uri')->nullable();
            $table->json('address');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guarantors');
    }
};
