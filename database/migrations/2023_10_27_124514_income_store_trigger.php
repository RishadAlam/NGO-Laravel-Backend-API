<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::unprepared('
            CREATE TRIGGER Income_Store_Trigger BEFORE INSERT ON `incomes` FOR EACH ROW
            BEGIN
                UPDATE accounts SET total_deposit = total_deposit + NEW.amount WHERE id = NEW.account_id;
            END
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared('DROP TRIGGER `Income_Store_Trigger`');
    }
};
