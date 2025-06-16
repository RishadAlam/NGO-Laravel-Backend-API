<?php

namespace App\Http\Controllers\ClientAccountChecks;

use Carbon\Carbon;
use App\Helpers\Helper;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\client\SavingAccount;
use App\Models\category\CategoryConfig;
use App\Models\client\SavingAccountCheck;
use App\Http\Requests\ClientAccountChecks\SavingAccountCheckStoreRequest;

class SavingAccountCheckController extends Controller
{
    /**
     * Instantiate a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('can:client_saving_account_check')->only('store');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (empty(request('saving_account_id'))) {
            return create_response(__('customValidations.common.somethingWentWrong'), null, 401, false);
        }

        $dateRange = Helper::getDateRange(request('date_range'));
        $checks = SavingAccountCheck::where('saving_account_id', request('saving_account_id'))
            ->whereBetween('created_at', $dateRange)
            ->author('id', 'name')
            ->orderedBy('id', 'DESC')
            ->get();

        return create_response(null, $checks);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(SavingAccountCheckStoreRequest $request)
    {
        $data           = (object) $request->validated();
        $account        = SavingAccount::find($data->account_id);

        if (empty($account)) {
            return create_validation_error_response(__('customValidations.client.saving.not_found'));
        }

        SavingAccountCheck::where('saving_account_id', $account->id)->update(['status' => true]);
        SavingAccountCheck::create(
            [
                'saving_account_id'     => $account->id,
                'balance'               => $account->balance,
                'installment_recovered' => $account->total_installment,
                'description'           => $data->description,
                'next_check_in_at'      => Carbon::parse($data->next_check_in_at)->startOfDay(),
                'checked_by'            => auth()->id(),
            ]
        );

        return create_response(__('customValidations.client.saving.account_check'));
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $account = SavingAccount::active()
            ->approve()
            ->clientRegistration('id', 'name')
            ->find($id, ['id', 'client_registration_id', 'category_id', 'total_installment', 'balance']);

        if (empty($account)) {
            return create_validation_error_response(__('customValidations.client.saving.not_found'));
        }

        $categoryConf = CategoryConfig::categoryID($account->category_id)
            ->first('saving_acc_check_time_period');

        return create_response(
            null,
            [
                'id'                => $account->id,
                'name'              => $account->ClientRegistration->name,
                'balance'           => $account->balance,
                'total_installment' => $account->total_installment,
                'next_check_in_at'  => Carbon::now()->addDays($categoryConf->saving_acc_check_time_period > 0 ? $categoryConf->saving_acc_check_time_period : 1),
            ]
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
