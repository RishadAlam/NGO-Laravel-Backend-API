<?php

namespace App\Http\Controllers\Withdrawal;

use Carbon\Carbon;
use App\Models\AppConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\client\SavingAccount;
use App\Models\Withdrawal\SavingWithdrawal;
use App\Http\Requests\Withdrawal\SavingWithdrawalControllerStoreRequest;

class SavingWithdrawalController extends Controller
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
    public function store(SavingWithdrawalControllerStoreRequest $request)
    {
        $data           = (object) $request->validated();
        $is_approved    = AppConfig::get_config('money_withdrawal_approval');
        $account        = SavingAccount::find($data->id);

        if ($data->amount > $account->balance) {
            return create_validation_error_response(__('customValidations.accounts.insufficient_balance'));
        }

        $field_map = [
            'field_id'           => $account->field_id,
            'center_id'          => $account->center_id,
            'category_id'        => $account->category_id,
            'saving_account_id'  => $account->id,
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
                SavingWithdrawal::create($field_map);
                $account->increment('total_withdrawn', $data->amount);
                // Account::find($data->account_id)
                //     ->increment('total_deposit', $data->total);
            });
        } else {
            SavingWithdrawal::create($field_map);
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
