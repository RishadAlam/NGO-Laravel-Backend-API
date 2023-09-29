<?php

namespace App\Http\Controllers\config;

use App\Models\AppConfig;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\appConfig\AppSettingsRequest;
use Illuminate\Support\Facades\URL;

class AppConfigController extends Controller
{
    /**
     * App Setting Data
     *
     * @return Illuminate\Http\Response
     */
    public function index()
    {
        $appSettings = AppConfig::where('meta_key', 'company_details')
            ->value('meta_value');

        return response(
            [
                'success'   => true,
                'data'      => $appSettings,
            ],
            200
        );
    }
    /**
     * Approvals Configuration Data
     *
     * @return Illuminate\Http\Response
     */
    public function get_all_approvals()
    {
        $appSettings = AppConfig::whereIn('meta_key', [
            'saving_collection_approval',
            'loan_collection_approval',
            'money_exchange_approval',
            'money_withdrawal_approval',
            'saving_account_registration_approval',
            'saving_account_closing_approval',
            'loan_account_registration_approval',
            'loan_account_closing_approval',
        ])->get(['id', 'meta_key', 'meta_value']);

        return response(
            [
                'success'   => true,
                'data'      => $appSettings,
            ],
            200
        );
    }


    /**
     * App Setting Update Update
     *
     * @param App\Http\Requests\AppSettingsRequest $request
     * @return Illuminate\Http\Response
     */
    public function app_settings_update(AppSettingsRequest $request)
    {
        $data = (object) $request->validated();
        $imgUri = $data->company_logo_uri;
        if (!empty($data->company_logo)) {
            if (!empty($data->company_old_logo)) {
                $path = public_path('storage/config/' . $data->company_old_logo . '');
                unlink($path);
            }

            $extension  = $data->company_logo->extension();
            $imgName    = 'logo_' . time() . '.' . $extension;
            $data->company_logo->move(public_path() . '/storage/config/', $imgName);
            $data->company_logo     = $imgName;
            $data->company_logo_uri = URL::to('/storage/config/', $data->company_logo);
        }

        AppConfig::where('meta_key', 'company_details')
            ->first()
            ->update(
                [
                    'meta_value'  => [
                        "company_name"          => $data->company_name,
                        "company_short_name"    => $data->company_short_name,
                        "company_address"       => $data->company_address,
                        "company_logo"          => $data->company_logo,
                        "company_logo_uri"      => $data->company_logo_uri
                    ],
                ]
            );

        $appSettings = AppConfig::where('meta_key', 'company_details')
            ->value('meta_value');

        return response(
            [
                'success'   => true,
                'message'   => __('customValidations.app_config.app_settings_update'),
                'data'      => $appSettings,
            ],
            200
        );
    }
}
