<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class DeployController extends Controller
{
    public function __invoke(Request $request)
    {
        abort_unless(app()->environment('production'), 403);
        $secret = config('app.deploy_key');
        $provided = $request->header('X-Deploy-Key');
        abort_unless(is_string($secret) && trim($secret) !== '' && is_string($provided) && hash_equals($secret, $provided), 403);
        abort_unless(config('app.key'), 503, 'Configure APP_KEY before deployment.');
        $lock = Cache::lock('ngo:deployment', 300);
        abort_unless($lock->get(), 409, 'A deployment is already running.');

        try {
            // Permissions/schema changes belong in migrations, never demo seeders.
            foreach ([['migrate', ['--force' => true]], ['config:cache', []], ['route:cache', []], ['view:cache', []]] as [$command, $arguments]) {
                if (Artisan::call($command, $arguments) !== 0) {
                    return response()->json(['status' => 'DEPLOY_FAILED', 'command' => $command], 500);
                }
            }
            File::put(storage_path('framework/last_deploy.txt'), now()->toDateTimeString());

            return response()->json(['status' => 'DEPLOY_SUCCESS', 'time' => now()->toDateTimeString()]);
        } finally {
            $lock->release();
        }
    }
}
