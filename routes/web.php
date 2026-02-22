<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::post('/__deploy/run', function () {

    // 1️⃣ Environment check
    if (app()->environment() !== 'production') {
        abort(403, 'Not production');
    }

    // 2️⃣ Secret key check
    if (request('key') !== config('app.deploy_key')) {
        abort(403, 'Invalid key');
    }

    // 3️⃣ Optional IP restriction (comment if needed)
    /*
    $allowedIps = ['YOUR_GITHUB_RUNNER_IP'];
    if (!in_array(Request::ip(), $allowedIps)) {
        abort(403, 'IP not allowed');
    }
    */

    $output = [];

    if (config('app.key')) {
        $output['key'] ='APP_KEY already exists';
    }else{
        // ✅ Generate application key
        Artisan::call('key:generate', ['--force' => true]);
        $output['key'] = Artisan::output();
    }

    // ✅ Safe migrate
    Artisan::call('migrate', [
        '--force' => true,
    ]);
    $output['migrate'] = Artisan::output();

    // ✅ Run seeders
    Artisan::call('db:seed', [
        '--class' => 'Database\\Seeders\\RolePermissionSeeder',
        '--force' => true,
    ]);
    $output['seeder'] = 'RolePermissionSeeder executed';

    // ✅ Clear & cache
    Artisan::call('config:clear');
    Artisan::call('config:cache');

    Artisan::call('route:clear');
    Artisan::call('route:cache');

    Artisan::call('view:clear');

    // ✅ Storage link (won’t fail if exists)
    try {
        Artisan::call('storage:link');
        $output['storage'] = 'ok';
    } catch (\Exception $e) {
        $output['storage'] = 'already exists';
    }

    // ✅ Optimize
    Artisan::call('optimize');

    // 🏁 Deployment marker
    File::put(
        storage_path('framework/last_deploy.txt'),
        now()->toDateTimeString()
    );

    return response()->json([
        'status' => 'DEPLOY_SUCCESS',
        'time' => now()->toDateTimeString(),
        'output' => $output,
    ]);
});