<?php

namespace App\Http\Controllers\closing;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\client\SavingAccount;
use App\Models\category\CategoryConfig;

class SavingAccClosingController extends Controller
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
        $account = SavingAccount::approve()
            ->clientRegistration('id', 'name')
            ->find($id, ['id', 'client_registration_id', 'category_id', 'balance', 'payable_installment', 'payable_interest', 'total_installment']);

        if (empty($account)) {
            return create_validation_error_response(__('customValidations.client.saving.not_found'));
        }

        $categoryConf = CategoryConfig::categoryID($account->category_id)
            ->first(['saving_acc_closing_fee', 's_col_fee_acc_id']);

        return create_response(
            null,
            [
                'id'                        => $account->id,
                'name'                      => $account->ClientRegistration->name,
                'balance'                   => $account->balance,
                'total_installment'         => $account->payable_installment,
                'total_rec_installment'     => $account->total_installment,
                'interest'                  => $account->balance * ($account->payable_interest / 100),
                'total_balance'             => $account->balance + ($account->balance * ($account->payable_interest / 100)),
                'closing_fee'               => $categoryConf->saving_acc_closing_fee ?? 0,
                'closing_fee_acc_id'        => $categoryConf->s_col_fee_acc_id,
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
