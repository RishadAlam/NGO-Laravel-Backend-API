<?php

namespace App\Http\Controllers\Withdrawal;

use App\Models\AppConfig;
use Illuminate\Http\Request;
use App\Models\client\LoanAccount;
use App\Http\Controllers\Controller;
use App\Models\Withdrawal\LoanSavingWithdrawal;
use App\Http\Requests\Withdrawal\LoanWithdrawalControllerStoreRequest;
use App\Http\Requests\Withdrawal\SavingWithdrawalControllerStoreRequest;

class LoanSavingWithdrawalController extends Controller
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
    public function store(LoanWithdrawalControllerStoreRequest $request)
    {
        $data           = (object) $request->validated();
        $is_approved    = AppConfig::get_config('money_withdrawal_approval');
        $account        = LoanAccount::find($data->id);

        if ($data->amount > $account->balance) {
            return create_validation_error_response(__('customValidations.accounts.insufficient_balance'));
        }

        $field_map = [
            'field_id'           => $account->field_id,
            'center_id'          => $account->center_id,
            'category_id'        => $account->category_id,
            'loan_account_id'    => $account->id,
            'acc_no'             => $account->acc_no,
            'balance'            => $account->balance,
            'amount'             => $data->amount,
            'description'        => $data->description,
            'creator_id'         => auth()->id(),
        ];


        if ($is_approved) {
            $field_map += [
                'is_approved'   => $is_approved,
                'approved_by'   => auth()->id(),
                'account_id'    => auth()->id(),
                'approved_at'   => Carbon::now('Asia/Dhaka')
            ];

            DB::transaction(function () use ($field_map, $data, $account) {
                LoanSavingWithdrawal::create($field_map);
                $account->increment('total_withdrawn', $data->amount);
                // Account::find($data->account_id)
                //     ->increment('total_deposit', $data->total);
            });
        } else {
            LoanSavingWithdrawal::create($field_map);
        }

        return create_response(__('customValidations.client.withdrawal.successful'));
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
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
