<?php

namespace App\Http\Controllers\Withdrawal;

use Carbon\Carbon;
use App\Helpers\Helper;
use App\Models\AppConfig;
use Illuminate\Http\Request;
use Ramsey\Uuid\Type\Integer;
use App\Models\accounts\Income;
use App\Models\accounts\Account;
use App\Models\accounts\Expense;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\client\SavingAccount;
use App\Models\accounts\IncomeCategory;
use App\Models\category\CategoryConfig;
use App\Models\client\SavingAccountFee;
use App\Models\accounts\ExpenseCategory;
use App\Models\client\AccountFeesCategory;
use App\Models\Withdrawal\SavingWithdrawal;
use App\Http\Requests\Withdrawal\SavingWithdrawalApprovalRequest;
use App\Http\Requests\Withdrawal\SavingWithdrawalControllerStoreRequest;
use App\Http\Requests\Withdrawal\SavingWithdrawalControllerUpdateRequest;

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
        try {
            return DB::transaction(function () use ($request) {
                $data           = (object) $request->validated();
                $is_approved    = AppConfig::get_config('money_withdrawal_approval');
                $account        = SavingAccount::find($data->account_id);
                $categoryConf   = CategoryConfig::categoryID($account->category_id)->first(['min_saving_withdrawal', 'max_saving_withdrawal']);

                // Validation
                $validationErrors = self::validateAmount($data->amount, $account->balance, $categoryConf->min_saving_withdrawal, $categoryConf->max_saving_withdrawal);
                if (!empty($validationErrors)) {
                    return $validationErrors;
                }

                $field_map = self::fieldMapping($account, $data, true);
                if ($is_approved) {
                    $categoryConf   = CategoryConfig::categoryID($account->category_id)->first(['saving_withdrawal_fee', 's_with_fee_acc_id']);
                    $fee            = $categoryConf->saving_withdrawal_fee;
                    $feeAccId       = $categoryConf->s_with_fee_acc_id;

                    if (!empty($fee) && ($data->amount + $fee) > $account->balance) {
                        return create_validation_error_response(__('customValidations.accounts.insufficient_balance'), 'fee');
                    }

                    $withdrawal = SavingWithdrawal::create($field_map);
                    self::processWithdrawal($withdrawal, null, $fee, $feeAccId, (array) $data);
                } else {
                    SavingWithdrawal::create($field_map);
                }

                return create_response(__('customValidations.client.withdrawal.successful'));
            });
        } catch (\Exception $e) {
            return create_response($e->getMessage(), null, 400, false);
        }
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
    public function update(SavingWithdrawalControllerUpdateRequest $request, string $id)
    {
        $data           = (object) $request->validated();
        $withdrawal     = SavingWithdrawal::find($id);
        $account        = SavingAccount::find($withdrawal->saving_account_id);
        $categoryConf   = CategoryConfig::categoryID($withdrawal->category_id)
            ->first(['min_saving_withdrawal', 'max_saving_withdrawal']);

        // Validation
        $validationErrors = self::validateAmount($data->amount, $account->balance, $categoryConf->min_saving_withdrawal, $categoryConf->max_saving_withdrawal);
        if (!empty($validationErrors)) {
            return $validationErrors;
        }

        $withdrawal->update(self::fieldMapping($account, $data));
        return create_response(__('customValidations.client.withdrawal.update'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        SavingWithdrawal::find($id)->delete();
        return create_response(__('customValidations.client.withdrawal.delete'));
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
        try {
            return DB::transaction(function () use ($request, $id) {
                $account        = null;
                $requestData    = $request->validated();
                $withdrawal     = SavingWithdrawal::with(['SavingAccount:id,balance', 'Category:id,name,is_default'])->find($id);
                $categoryConf   = CategoryConfig::categoryID($withdrawal->category_id)->first(['saving_withdrawal_fee', 's_with_fee_acc_id']);
                $fee            = $categoryConf->saving_withdrawal_fee;
                $feeAccId       = $categoryConf->s_with_fee_acc_id;

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
     * @param SavingWithdrawal $withdrawal
     * @param Account $account
     * @param int $fee
     * @return response
     */
    private static function validateWithdrawal(SavingWithdrawal $withdrawal, Account $account = null, int $fee)
    {
        if (!$withdrawal) {
            return create_validation_error_response(__('customValidations.client.withdrawal.not_found'));
        }
        if ($withdrawal->amount > $withdrawal->SavingAccount->balance) {
            return create_validation_error_response(__('customValidations.accounts.insufficient_balance'), 'balance');
        }
        if (!empty($fee) && ($withdrawal->amount + $fee) > $withdrawal->SavingAccount->balance) {
            return create_validation_error_response(__('customValidations.accounts.insufficient_balance'), 'fee');
        }
        if (!empty($account) && $account->balance < $withdrawal->amount) {
            return create_validation_error_response(__('customValidations.accounts.insufficient_balance'), 'account');
        }
        return false;
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
     * @param SavingAccount $account
     * @param object $requestData
     * @param boolean $is_store
     * @return array
     */
    private static function fieldMapping(SavingAccount $account, object $requestData, $is_store = false)
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
                'saving_account_id'  => $account->id,
                'acc_no'             => $account->acc_no,
                'creator_id'         => auth()->id(),
            ];
        }

        return $field_map;
    }

    /**
     * Process withdrawal
     *
     * @param SavingWithdrawal $withdrawal
     * @param Account $account
     * @param int $fee
     * @param int $feeAccId
     * @param array $requestData
     */
    private static function processWithdrawal(SavingWithdrawal $withdrawal, Account $account = null, int $fee, int $feeAccId, array $requestData): void
    {
        $data           = (object) $requestData;
        $savingAccount  = $withdrawal->SavingAccount;

        $expenseCatId   = ExpenseCategory::where('name', 'saving_withdrawal')->value('id');
        $categoryName   = !$withdrawal->category->is_default ? $withdrawal->category->name :  __("customValidations.category.default.{$withdrawal->category->name}");
        $acc_no         = Helper::tsNumbers($withdrawal->acc_no);
        $amount         = Helper::tsNumbers("৳{$withdrawal->amount}/-");
        $description    = __('customValidations.common.acc_no') . ' = ' . $acc_no . ', ' . __('customValidations.common.category') . ' = ' . $categoryName . ', ' . __('customValidations.common.saving') . ' ' . __('customValidations.common.withdrawal') . ' = ' . $amount;

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

            SavingAccountFee::create([
                'saving_account_id'         => $savingAccount->id,
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
            $savingAccount->increment('total_withdrawn', $fee);
        }

        $savingAccount->increment('total_withdrawn', $withdrawal->amount);
        $withdrawal->update(
            [
                'is_approved' => true,
                'approved_by' => auth()->id(),
                'approved_at' => Carbon::now()
            ]
        );
    }
}
