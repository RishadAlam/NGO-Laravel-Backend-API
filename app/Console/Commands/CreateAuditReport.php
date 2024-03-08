<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CreateAuditReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:audit-report';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create audit report into the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Log::info("Command run success fully");
    }
}
