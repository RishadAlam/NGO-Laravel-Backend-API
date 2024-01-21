<?php

namespace App\Http\Controllers\Withdrawal;

use Carbon\Carbon;
use App\Helpers\Helper;
use App\Models\AppConfig;
use Illuminate\Http\Request;
use App\Models\accounts\Account;
use App\Models\accounts\Expense;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\client\SavingAccount;
use App\Models\category\CategoryConfig;
use App\Models\accounts\ExpenseCategory;
use App\Models\Withdrawal\SavingWithdrawal;
use App\Http\Requests\Withdrawal\SavingWithdrawalApprovalRequest;
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
        $account        = SavingAccount::find($data->account_id);
        $categoryConf   = CategoryConfig::categoryID($account->category_id)
            ->first(['min_saving_withdrawal', 'max_saving_withdrawal']);

        if ($data->amount > $account->balance) {
            return create_validation_error_response(__('customValidations.accounts.insufficient_balance'));
        }
        if ($categoryConf->max_saving_withdrawal > 0 && ($data->amount < $categoryConf->min_saving_withdrawal || $data->amount > $categoryConf->max_saving_withdrawal)) {
            return create_validation_error_response(__('customValidations.common.amount') . ' ' . __('customValidations.common_validation.crossed_the_limitations'));
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
        $account = SavingAccount::active()
            ->approve()
            ->clientRegistration('id', 'name')
            ->find($id, ['id', 'client_registration_id', 'category_id', 'balance']);

        if (empty($account)) {
            return create_validation_error_response(__('customValidations.client.saving.not_found'));
        }

        $categoryConf = CategoryConfig::categoryID($account->category_id)
            ->first(['min_saving_withdrawal', 'max_saving_withdrawal']);

        return response([
            'success'   => true,
            'data'      => [
                'id'        => $account->id,
                'name'      => $account->ClientRegistration->name,
                'balance'   => $account->balance,
                'min'       => $categoryConf->min_saving_withdrawal,
                'max'       => $categoryConf->max_saving_withdrawal
            ],
        ], 200);
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
        SavingWithdrawal::find($id)->delete();
        return create_response(__('customValidations.client.collection.delete'));
    }

    /**
     * Display the pending resource. 
     */
    public function pending_withdrawal()
    {
        $withdrawals = SavingWithdrawal::pendingWithdrawals()->get();

        return response([
            'success'   => true,
            'data'      => $withdrawals,
        ], 200);
    }


    /**
     * Approved the specified Withdrawal
     */
    public function approved(SavingWithdrawalApprovalRequest $request, string $id)
    {
        $account        = null;
        $data           = (object) $request->validated();
        $withdrawal     = SavingWithdrawal::with(['SavingAccount:id,balance', 'Category:id,name,is_default'])->find($id);
        $fee            = CategoryConfig::categoryID($withdrawal->category_id)->pluck('saving_withdrawal_fee');

        if (!$withdrawal) {
            return create_validation_error_response(__('customValidations.client.withdrawal.not_found'));
        }
        if ($withdrawal->amount > $withdrawal->SavingAccount->balance) {
            return create_validation_error_response(__('customValidations.accounts.insufficient_balance'), 'balance');
        }
        if (!empty($fee) && ($withdrawal->amount + $fee) > $withdrawal->SavingAccount->balance) {
            return create_validation_error_response(__('customValidations.accounts.insufficient_balance'), 'fee');
        }
        if (isset($data->account) && $account = Account::find($data->account)) {
            if ($account->balance < $withdrawal->amount) {
                return create_validation_error_response(__('customValidations.accounts.insufficient_balance'), 'account');
            }
        }


        DB::transaction(function () use ($withdrawal, $data, $account) {
            if (isset($data->account) && !empty($account)) {
                $expenseCatId   = ExpenseCategory::where('name', 'saving_withdrawal')->value('id');
                $categoryName   = !$withdrawal->category->is_default ? $withdrawal->category->name :  __("customValidations.category.default.{$withdrawal->category->name}");
                $acc_no         = Helper::tsNumbers($withdrawal->acc_no);
                $amount         = Helper::tsNumbers("৳{$withdrawal->amount}/-");
                $description    = __('customValidations.common.acc_no') . ' = ' . $acc_no . ', ' . __('customValidations.common.category') . ' = ' . $categoryName . ', ' . __('customValidations.common.saving') . ' ' . __('customValidations.common.withdrawal') . ' = ' . $amount;

                Expense::store(
                    $data->account,
                    $expenseCatId,
                    $withdrawal->amount,
                    $account->balance,
                    $description
                );
                $account->increment('total_withdrawal', $withdrawal->amount);
            }

            SavingAccount::find($withdrawal->saving_account_id)->increment('total_withdrawn', $withdrawal->amount);
            $withdrawal->update(['account_id' => $data->account, 'approved_by' => auth()->id()]);
        });

        return create_response(__('customValidations.client.withdrawal.approved'));
    }
}
