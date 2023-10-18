<?php

namespace App\Http\Controllers\config;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\category\CategoryConfig;
use App\Http\Requests\appConfig\CategoriesConfigUpdateRequest;

class CategoryConfigController extends Controller
{
    /**
     * Get all categories configuration
     */
    public function get_all_categories_config()
    {
        $configs = CategoryConfig::with("Category:id,name")->get();
        return $configs;
    }

    /**
     * App Setting Update Update
     *
     * @param App\Http\Requests\CategoriesConfigUpdateRequest $request
     * @return Illuminate\Http\Response
     */
    public function config_update(CategoriesConfigUpdateRequest $request)
    {
        $data = (object) $request->validated();
        foreach ($data->categoriesConfig as $config) {
            $config = (object) $config;
            CategoryConfig::find($config->id)
                ->update(
                    [
                        'saving_acc_reg_fee'                        => $config->saving_acc_reg_fee,
                        'saving_acc_closing_fee'                    => $config->saving_acc_closing_fee,
                        'loan_acc_reg_fee'                          => $config->loan_acc_reg_fee,
                        'loan_acc_closing_fee'                      => $config->loan_acc_closing_fee,
                        'saving_withdrawal_fee'                     => $config->saving_withdrawal_fee,
                        'loan_saving_withdrawal_fee'                => $config->loan_saving_withdrawal_fee,
                        'min_saving_withdrawal'                     => $config->min_saving_withdrawal,
                        'max_saving_withdrawal'                     => $config->max_saving_withdrawal,
                        'min_loan_saving_withdrawal'                => $config->min_loan_saving_withdrawal,
                        'max_loan_saving_withdrawal'                => $config->max_loan_saving_withdrawal,
                        'saving_acc_check_time_period'              => $config->saving_acc_check_time_period,
                        'loan_acc_check_time_period'                => $config->loan_acc_check_time_period,
                        'disable_unchecked_saving_acc'              => $config->disable_unchecked_saving_acc,
                        'disable_unchecked_loan_acc'                => $config->disable_unchecked_loan_acc,
                        'inactive_saving_acc_disable_time_period'   => $config->inactive_saving_acc_disable_time_period,
                        'inactive_loan_acc_disable_time_period'     => $config->inactive_loan_acc_disable_time_period,
                    ]
                );
        }

        return response(
            [
                'success'   => true,
                'message'   => __('customValidations.app_config.categories_configuration_update')
            ],
            200
        );
    }
}
