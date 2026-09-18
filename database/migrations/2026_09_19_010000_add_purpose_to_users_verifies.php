<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users_verifies', function (Blueprint $table) {
            $table->string('purpose', 20)->default('verification');
        });
    }

    public function down(): void
    {
        Schema::table('users_verifies', fn (Blueprint $table) => $table->dropColumn('purpose'));
    }
};
