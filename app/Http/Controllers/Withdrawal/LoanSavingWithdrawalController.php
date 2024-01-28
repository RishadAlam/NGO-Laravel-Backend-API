<?php

namespace App\Http\Controllers\Withdrawal;

use Carbon\Carbon;
use App\Helpers\Helper;
use App\Models\AppConfig;
use Illuminate\Http\Request;
use App\Models\accounts\Income;
use App\Models\accounts\Account;
use App\Models\accounts\Expense;
use App\Models\client\LoanAccount;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\client\LoanAccountFee;
use App\Models\accounts\IncomeCategory;
use App\Models\category\CategoryConfig;
use App\Models\accounts\ExpenseCategory;
use App\Models\client\AccountFeesCategory;
use App\Models\Withdrawal\LoanSavingWithdrawal;
use App\Http\Requests\Withdrawal\LoanSavingWithdrawalApprovalRequest;
use App\Http\Requests\Withdrawal\LoanWithdrawalControllerStoreRequest;
use App\Http\Requests\Withdrawal\LoanWithdrawalControllerUpdateRequest;
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
        $account        = LoanAccount::find($data->account_id);
        $categoryConf   = CategoryConfig::categoryID($account->category_id)
            ->first(['min_loan_saving_withdrawal', 'max_loan_saving_withdrawal']);

        if ($data->amount > $account->balance) {
            return create_validation_error_response(__('customValidations.accounts.insufficient_balance'));
        }
        if ($categoryConf->max_loan_saving_withdrawal > 0 && ($data->amount < $categoryConf->min_loan_saving_withdrawal || $data->amount > $categoryConf->max_loan_saving_withdrawal)) {
            return create_validation_error_response(__('customValidations.common.amount') . ' ' . __('customValidations.common_validation.crossed_the_limitations'));
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
        $account = LoanAccount::active()
            ->approve()
            ->clientRegistration('id', 'name')
            ->find($id, ['id', 'client_registration_id', 'category_id', 'balance']);

        if (empty($account)) {
            return create_validation_error_response(__('customValidations.client.loan.not_found'));
        }

        $categoryConf = CategoryConfig::categoryID($account->category_id)
            ->first(['min_loan_saving_withdrawal', 'max_loan_saving_withdrawal']);

        return response([
            'success'   => true,
            'data'      => [
                'id'        => $account->id,
                'name'      => $account->ClientRegistration->name,
                'balance'   => $account->balance,
                'min'       => $categoryConf->min_loan_saving_withdrawal,
                'max'       => $categoryConf->max_loan_saving_withdrawal
            ],
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(LoanWithdrawalControllerUpdateRequest $request, string $id)
    {
        $data           = (object) $request->validated();
        $withdrawal     = LoanSavingWithdrawal::find($id);
        $account        = LoanAccount::find($withdrawal->loan_account_id);
        $categoryConf   = CategoryConfig::categoryID($withdrawal->category_id)
            ->first(['min_loan_saving_withdrawal', 'max_loan_saving_withdrawal']);

        // Validation
        $validationErrors = self::validateAmount($data->amount, $account->balance, $categoryConf->min_loan_saving_withdrawal, $categoryConf->max_loan_saving_withdrawal);
        if (!empty($validationErrors)) {
            return $validationErrors;
        }

        $withdrawal->update(self::fieldMapping($account, $data));
        return create_response(__('customValidations.client.withdrawal.update'));
    }

    /**
     * Validate withdrawal Amount
     *
     * @param int $amount
     * @param int $balance
     * @param int $min
     * @param int $max
     * @return response
     */
    private static function validateAmount(int $amount, int $balance, int $min, int $max)
    {
        if ($amount > $balance) {
            return create_validation_error_response(__('customValidations.accounts.insufficient_balance'));
        }
        if ($amount < $min || ($max > 0 && $amount > $max)) {
            return create_validation_error_response(__('customValidations.common.withdrawal') . ' ' . __('customValidations.common_validation.crossed_the_limitations'));
        }

        return false;
    }

    /**
     * Field Mapping Withdrawal Data
     *
     * @param LoanAccount $account
     * @param object $requestData
     * @param boolean $is_store
     * @param boolean $is_approved
     * @return array
     */
    private static function fieldMapping(LoanAccount $account, object $requestData, $is_store = false)
    {
        $field_map = [
            'balance'       => $account->balance,
            'amount'        => $requestData->amount,
            'description'   => $requestData->description,
        ];
        if ($is_store) {
            $field_map += [
                'field_id'           => $account->field_id,
                'center_id'          => $account->center_id,
                'category_id'        => $account->category_id,
                'loan_account_id'    => $account->id,
                'acc_no'             => $account->acc_no,
                'creator_id'         => auth()->id(),
            ];
        }

        return $field_map;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        LoanSavingWithdrawal::find($id)->delete();
        return create_response(__('customValidations.client.withdrawal.delete'));
    }

    /**
     * Display the pending resource. 
     */
    public function pending_withdrawal()
    {
        $withdrawals = LoanSavingWithdrawal::pendingWithdrawals()->get();

        return response([
            'success'   => true,
            'data'      => $withdrawals,
        ], 200);
    }

    /**
     * Approved the specified Withdrawal
     */
    public function approved(LoanSavingWithdrawalApprovalRequest $request, string $id)
    {
        try {
            return DB::transaction(function () use ($request, $id) {
                $account        = null;
                $requestData    = $request->validated();
                $withdrawal     = LoanSavingWithdrawal::with(['LoanAccount:id,balance', 'Category:id,name,is_default'])->find($id);
                $categoryConf   = CategoryConfig::categoryID($withdrawal->category_id)->first(['loan_saving_withdrawal_fee', 'ls_with_fee_acc_id']);
                $fee            = $categoryConf->loan_saving_withdrawal_fee;
                $feeAccId       = $categoryConf->ls_with_fee_acc_id;

                if (isset($requestData['account'])) {
                    $account = Account::find($requestData['account']);
                }

                // Validation
                $validationErrors = self::validateWithdrawal($withdrawal, $account, $fee);
                if (!empty($validationErrors)) {
                    return $validationErrors;
                }

                // Process Withdrawal
                self::processWithdrawal($withdrawal, $account, $fee, $feeAccId, $requestData);
                return create_response(__('customValidations.client.withdrawal.approved'));
            });
        } catch (\Exception $e) {
            return create_response($e->getMessage(), null, 400, false);
        }
    }

    /**
     * Validate withdrawal request
     *
     * @param LoanSavingWithdrawal $withdrawal
     * @param Account $account
     * @param int $fee
     * @return response
     */
    private static function validateWithdrawal(LoanSavingWithdrawal $withdrawal, Account $account = null, int $fee)
    {
        if (!$withdrawal) {
            return create_validation_error_response(__('customValidations.client.withdrawal.not_found'));
        }
        if ($withdrawal->amount > $withdrawal->LoanAccount->balance) {
            return create_validation_error_response(__('customValidations.accounts.insufficient_balance'), 'balance');
        }
        if (!empty($fee) && ($withdrawal->amount + $fee) > $withdrawal->LoanAccount->balance) {
            return create_validation_error_response(__('customValidations.accounts.insufficient_balance'), 'fee');
        }
        if (!empty($account) && $account->balance < $withdrawal->amount) {
            return create_validation_error_response(__('customValidations.accounts.insufficient_balance'), 'account');
        }
        return false;
    }

    /**
     * Process withdrawal
     *
     * @param LoanSavingWithdrawal $withdrawal
     * @param Account $account
     * @param int $fee
     * @param int $feeAccId
     * @param array $requestData
     */
    private static function processWithdrawal(LoanSavingWithdrawal $withdrawal, Account $account = null, int $fee, int $feeAccId, array $requestData): void
    {
        $data           = (object) $requestData;
        $loanAccount    = $withdrawal->LoanAccount;

        $expenseCatId   = ExpenseCategory::where('name', 'loan_saving_withdrawal')->value('id');
        $categoryName   = !$withdrawal->category->is_default ? $withdrawal->category->name :  __("customValidations.category.default.{$withdrawal->category->name}");
        $acc_no         = Helper::tsNumbers($withdrawal->acc_no);
        $amount         = Helper::tsNumbers("৳{$withdrawal->amount}/-");
        $description    = __('customValidations.common.acc_no') . ' = ' . $acc_no . ', ' . __('customValidations.common.category') . ' = ' . $categoryName . ', ' . __('customValidations.common.loan_saving') . ' ' . __('customValidations.common.withdrawal') . ' = ' . $amount;

        if (isset($data->account) && !empty($account)) {
            Expense::store(
                $data->account,
                $expenseCatId,
                $withdrawal->amount,
                $account->balance,
                $description
            );
            $account->increment('total_withdrawal', $withdrawal->amount);
        }
        if (!empty($fee) && $fee > 0) {
            $categoryId     = AccountFeesCategory::where('name', 'withdrawal_fee')->value('id');
            $feeAccount     = Account::find($feeAccId);
            $incomeCatId    = IncomeCategory::where('name', 'withdrawal_fee')->value('id');

            LoanAccountFee::create([
                'loan_account_id'           => $loanAccount->id,
                'account_fees_category_id'  => $categoryId,
                'creator_id'                => auth()->id(),
                'amount'                    => $fee,
                'description'               => $description
            ]);
            Income::store(
                $feeAccId,
                $incomeCatId,
                $fee,
                $feeAccount->balance,
                $description
            );
            $feeAccount->increment('total_deposit', $fee);
            $loanAccount->increment('total_withdrawn', $fee);
        }

        $loanAccount->increment('total_withdrawn', $withdrawal->amount);
        $withdrawal->update(
            [
                'is_approved' => true,
                'approved_by' => auth()->id(),
                'approved_at' => Carbon::now()
            ]
        );
    }
}
