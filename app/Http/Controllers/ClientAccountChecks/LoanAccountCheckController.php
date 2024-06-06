<?php

namespace App\Http\Controllers\ClientAccountChecks;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\client\LoanAccount;
use App\Http\Controllers\Controller;
use App\Models\category\CategoryConfig;
use App\Models\client\LoanAccountCheck;
use App\Http\Requests\ClientAccountChecks\LoanAccountCheckStoreRequest;

class LoanAccountCheckController extends Controller
{
    /**
     * Instantiate a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('can:client_loan_account_check')->only('store');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(LoanAccountCheckStoreRequest $request)
    {
        $data           = (object) $request->validated();
        $account        = LoanAccount::find($data->account_id);

        if (empty($account)) {
            return create_validation_error_response(__('customValidations.client.loan.not_found'));
        }

        LoanAccountCheck::create(
            [
                'loan_account_id'       => $account->id,
                'installment_recovered' => $account->total_rec_installment,
                'installment_remaining' => $account->payable_installment - $account->total_rec_installment,
                'balance'               => $account->balance,
                'loan_recovered'        => $account->total_loan_rec,
                'loan_remaining'        => $account->total_loan_remaining,
                'interest_recovered'    => $account->total_interest_rec,
                'interest_remaining'    => $account->total_interest_remaining,
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
        $account = LoanAccount::active()
            ->approve()
            ->clientRegistration('id', 'name')
            ->find(
                $id,
                [
                    'id',
                    'client_registration_id',
                    'category_id',
                    'balance',
                    'total_rec_installment',
                    'total_loan_rec',
                    'total_loan_remaining',
                    'total_interest_rec',
                    'total_interest_remaining'
                ]
            );

        if (empty($account)) {
            return create_validation_error_response(__('customValidations.client.loan.not_found'));
        }

        $categoryConf = CategoryConfig::categoryID($account->category_id)
            ->first('loan_acc_check_time_period');

        return create_response(
            null,
            [
                'id'                        => $account->id,
                'name'                      => $account->ClientRegistration->name,
                'balance'                   => $account->balance,
                'total_installment'         => $account->total_rec_installment,
                'total_loan_rec'            => $account->total_loan_rec,
                'total_loan_remaining'      => $account->total_loan_remaining,
                'total_interest_rec'        => $account->total_interest_rec,
                'total_interest_remaining'  => $account->total_interest_remaining,
                'next_check_in_at'          => Carbon::now()->addDays($categoryConf->loan_acc_check_time_period > 0 ? $categoryConf->loan_acc_check_time_period : 1),
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
