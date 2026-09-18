<?php

namespace App\Console\Commands;

use App\Models\UserImpersonationSession;
use Illuminate\Console\Command;

class ExpireImpersonationSessions extends Command
{
    protected $signature = 'impersonation:expire';

    protected $description = 'Finalize expired or revoked temporary sessions and remove their tokens';

    public function handle(): int
    {
        UserImpersonationSession::whereNull('ended_at')
            ->where(function ($query) {
                $query->where('expires_at', '<=', now());
                foreach (['token_id', 'admin_token_id'] as $column) {
                    $query->orWhereNotExists(fn ($tokens) => $tokens->selectRaw('1')
                        ->from('personal_access_tokens')
                        ->whereColumn('personal_access_tokens.id', 'user_impersonation_sessions.'.$column));
                }
            })
            ->chunkById(100, function ($sessions) {
                foreach ($sessions as $session) {
                    $session->finish($session->expires_at->lte(now()) ? 'expired' : 'revoked');
                }
            });

        return self::SUCCESS;
    }
}
