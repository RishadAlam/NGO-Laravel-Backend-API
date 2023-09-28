<?php

namespace App\Http\Controllers\config;

use App\Http\Controllers\Controller;
use App\Models\AppConfig;
use Illuminate\Http\Request;

class AppConfigController extends Controller
{
    public function index()
    {
        $appSettings = AppConfig::where('meta_key', 'company_details')->value('meta_value');
        return response(
            [
                'success'   => true,
                'data'      => $appSettings,
            ],
            200
        );
    }
}
