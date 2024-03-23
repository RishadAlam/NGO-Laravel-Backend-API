<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use App\Models\Audit\AuditReport;
use Illuminate\Support\Facades\Log;
use App\Models\client\ClientRegistration;
use App\Models\Collections\LoanCollection;

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

        AuditReport::createReport(Carbon::now()->year - 1, Carbon::now()->year);
        Log::info("Command run success fully");
    }
}
