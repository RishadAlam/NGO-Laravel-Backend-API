<?php

namespace App\Http\Controllers\closing;

use App\Helpers\Helper;
use App\Models\AppConfig;
use Illuminate\Http\Request;
use App\Models\accounts\Income;
use App\Models\accounts\Account;
use App\Models\client\LoanAccount;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\client\SavingAccount;
use App\Models\accounts\IncomeCategory;
use App\Models\category\CategoryConfig;
use App\Models\client\SavingAccountFee;
use App\Models\client\AccountFeesCategory;
use App\Models\client\SavingAccountClosing;
use App\Models\client\SavingAccountActionHistory;
use App\Http\Requests\ClientAccClosing\StoreSavingAccountClosingRequest;

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
    public function store(StoreSavingAccountClosingRequest $request)
    {
        $data = (object) $request->validated();

        if ($data->total_balance < 0) {
            return create_validation_error_response(__('customValidations.accounts.insufficient_balance'), 'balance');
        }

        $isApproved = AppConfig::get_config('saving_account_closing_approval');

        return DB::transaction(function () use ($data, $isApproved) {
            SavingAccountClosing::create(SavingAccountClosing::setFieldMap($data, true, $isApproved));

            if ($isApproved) {
                SavingAccountClosing::handleApprovedAccountClosing($data);
                return create_response(__('customValidations.client.saving.delete'));
            } else {
                return create_response(__('customValidations.client.saving.delete_request'));
            }
        });
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
