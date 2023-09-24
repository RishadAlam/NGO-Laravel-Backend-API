<?php

namespace App\Http\Controllers\config;

use App\Http\Controllers\Controller;
use App\Models\AppConfig;
use Illuminate\Http\Request;

class AppConfigController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $configuration = AppConfig::firstOrFail();
        return response(
            [
                "success"   => true,
                "data"      => $configuration
            ],
            200
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }
}
