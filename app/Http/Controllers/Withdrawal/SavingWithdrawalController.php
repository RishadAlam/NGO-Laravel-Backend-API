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
     * Instantiate a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('permission:pending_saving_withdrawal_list_view|pending_saving_withdrawal_list_view_as_admin')->only('pending_withdrawal');
        $this->middleware('can:permission_to_make_saving_withdrawal')->only('store');
        $this->middleware('can:pending_saving_withdrawal_update')->only('update');
        $this->middleware('can:pending_saving_withdrawal_delete')->only('destroy');
        $this->middleware('can:pending_saving_withdrawal_approval')->only('approved');
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
        $withdrawals = SavingWithdrawal::where('saving_account_id', request('saving_account_id'))
            ->whereBetween('created_at', $dateRange)
            ->approve()
            ->author('id', 'name')
            ->account('id', 'name', 'is_default')
            ->approver('id', 'name')
            ->orderedBy('id', 'DESC')
            ->get();

        return create_response(null, $withdrawals);
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
                $validationErrors = self::validateAmount($data->amount, $account->balance, $categoryConf->min_saving_withdrawal ?? 0, $categoryConf->max_saving_withdrawa ?? 0);
                if (!empty($validationErrors)) {
                    return $validationErrors;
                }

                $field_map = SavingWithdrawal::fieldMapping($account, $data, true);
                if ($is_approved) {
                    $categoryConf   = CategoryConfig::categoryID($account->category_id)->first(['saving_withdrawal_fee', 's_with_fee_acc_id']);
                    $fee            = $categoryConf->saving_withdrawal_fee ?? 0;
                    $feeAccId       = $categoryConf->s_with_fee_acc_id ?? 0;

                    if (!empty($fee) && ($data->amount + $fee) > $account->balance) {
                        return create_validation_error_response(__('customValidations.accounts.insufficient_balance'), 'fee');
                    }

                    $withdrawal = SavingWithdrawal::create($field_map);
                    SavingWithdrawal::processWithdrawal($withdrawal, null, $fee, $feeAccId, (array) $data);
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


        return create_response(
            null,
            [
                'id'        => $account->id,
                'name'      => $account->ClientRegistration->name,
                'balance'   => $account->balance,
                'min'       => $categoryConf->min_saving_withdrawal ?? 0,
                'max'       => $categoryConf->max_saving_withdrawal ?? 0
            ],
        );
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
        $validationErrors = self::validateAmount($data->amount, $account->balance, $categoryConf->min_saving_withdrawal ?? 0, $categoryConf->max_saving_withdrawal ?? 0);
        if (!empty($validationErrors)) {
            return $validationErrors;
        }

        $withdrawal->update(SavingWithdrawal::fieldMapping($account, $data));
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
        return create_response(null, $withdrawals);
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
                $fee            = $categoryConf->saving_withdrawal_fee ?? 0;
                $feeAccId       = $categoryConf->s_with_fee_acc_id ?? 0;

                if (isset($requestData['account'])) {
                    $account = Account::find($requestData['account']);
                }

                // Validation
                $validationErrors = self::validateWithdrawal($withdrawal, $account, $fee);
                if (!empty($validationErrors)) {
                    return $validationErrors;
                }

                // Process Withdrawal
                SavingWithdrawal::processWithdrawal($withdrawal, $account, $fee, $feeAccId, $requestData);
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
}
