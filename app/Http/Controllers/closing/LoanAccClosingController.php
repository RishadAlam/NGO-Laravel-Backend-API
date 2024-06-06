<?php

namespace App\Http\Controllers\closing;

use Illuminate\Http\Request;
use App\Models\client\LoanAccount;
use App\Http\Controllers\Controller;
use App\Models\category\CategoryConfig;

class LoanAccClosingController extends Controller
{
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
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $account = LoanAccount::approve()
            ->clientRegistration('id', 'name')
            ->find($id, ['id', 'client_registration_id', 'category_id', 'balance', 'loan_given', 'payable_installment', 'total_payable_interest', 'total_payable_loan_with_interest', 'total_rec_installment', 'total_loan_rec', 'total_interest_rec', 'total_loan_remaining', 'total_interest_remaining']);

        if (empty($account)) {
            return create_validation_error_response(__('customValidations.client.loan.not_found'));
        }

        $categoryConf = CategoryConfig::categoryID($account->category_id)
            ->first(['loan_acc_closing_fee', 'l_col_fee_acc_id']);

        return create_response(
            null,
            [
                'id'                                => $account->id,
                'name'                              => $account->ClientRegistration->name,
                'balance'                           => $account->balance,
                'loan_given'                        => $account->loan_given,
                'total_loan_rec'                    => $account->total_loan_rec,
                'total_loan_remaining'              => $account->total_loan_remaining,
                'total_installment'                 => $account->payable_installment,
                'total_rec_installment'             => $account->total_rec_installment,
                'total_interest'                    => $account->total_payable_interest,
                'total_interest_rec'                => $account->total_interest_rec,
                'total_interest_remaining'          => $account->total_interest_remaining,
                'closing_fee'                       => $categoryConf->loan_acc_closing_fee ?? 0,
                'closing_fee_acc_id'                => $categoryConf->l_col_fee_acc_id,
            ],
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
