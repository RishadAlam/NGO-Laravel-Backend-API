<?php

namespace App\Console\Commands;

use App\Models\UserImpersonationSession;
use Illuminate\Console\Command;

class ExpireImpersonationSessions extends Command
{
    protected $signature = 'impersonation:expire';

    protected $description = 'Revoke expired temporary sessions and finalize their audit records';

    public function handle(): int
    {
        UserImpersonationSession::whereNull('ended_at')->where('expires_at', '<=', now())
            ->chunkById(100, function ($sessions) {
                foreach ($sessions as $session) {
                    $session->finish('expired');
                }
            });

        return self::SUCCESS;
    }
}
