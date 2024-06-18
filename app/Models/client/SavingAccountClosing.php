<?php

namespace App\Models\client;

use App\Helpers\Helper;
use App\Models\accounts\Income;
use App\Models\accounts\Account;
use App\Models\accounts\Expense;
use App\Models\accounts\IncomeCategory;
use App\Models\category\CategoryConfig;
use Illuminate\Database\Eloquent\Model;
use App\Models\accounts\ExpenseCategory;
use App\Models\Withdrawal\SavingWithdrawal;
use App\Http\Traits\BelongsToSavingAccountTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SavingAccountClosing extends Model
{
    use HasFactory, BelongsToSavingAccountTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'saving_account_id',
        'creator_id',
        'approved_by',
        'account_id',
        'main_balance',
        'interest',
        'total_balance',
        'description',
        'is_approved',
    ];

    /**
     * Set Field Map
     * 
     * @param object $data
     * @param boolean $isStore
     * @param boolean $isApproved
     * 
     * @return array
     */
    public static function setFieldMap($data, $isStore, $isApproved)
    {
        $map = [
            'main_balance'  => $data->balance,
            'interest'      => $data->interest,
            'total_balance' => $data->total_balance,
            'description'   => $data->description,
        ];

        if ($isStore) {
            $map += [
                'saving_account_id' => $data->account_id,
                'creator_id'        => auth()->id(),
                'account_id'        => $data->closing_fee_acc_id,
                'closing_fee'       => $data->closing_fee,
            ];
        }

        if ($isApproved) {
            $map += [
                'approved_by'       => auth()->id(),
                'is_approved'       => $isApproved,
            ];
        }

        return $map;
    }

    public static function handleApprovedAccountClosing($data)
    {
        $withdrawal_account = null;
        $account            = SavingAccount::with('Category:id,name,is_default')->find($data->account_id);
        $categoryConf       = CategoryConfig::categoryID($account->category_id)
            ->first(['saving_acc_closing_fee', 's_col_fee_acc_id']);
        $withdraw_amount    = $account->balance - $categoryConf->saving_acc_closing_fee;

        if (!empty($data->withdrawal_account_id)) {
            $withdrawal_account = Account::find($data->withdrawal_account_id);
        }
        if ($categoryConf->saving_acc_closing_fee > 0) {
            static::processClosingFee($account, $categoryConf);
        }
        if (!empty($data->withdrawal_account_id) && !empty($data->interest)) {
            static::processInterest($account, $data->interest, $data->withdrawal_account_id, $withdrawal_account);
        }
        if ($withdraw_amount > 0) {
            static::processWithdrawal($account, $withdraw_amount, $data->withdrawal_account_id, $withdrawal_account);
        }

        static::deleteAccountAndAssociations($account);
        SavingAccountActionHistory::create(Helper::setActionHistory('saving_account_id', $account->id, 'delete', []));
    }

    private static function processWithdrawal($account, $amount, $withdrawal_account_id, $withdrawal_account)
    {
        $categoryName   = !$account->category->is_default ? $account->category->name : __("customValidations.category.default.{$account->category->name}");
        $data           = [
            'amount'        => $amount,
            'account'       => $withdrawal_account_id,
            'description' => __('customValidations.common.acc_no') . ' = ' . Helper::tsNumbers($account->acc_no) . ', ' .
                __('customValidations.common.category') . ' = ' . $categoryName . ', ' .
                __('customValidations.common.saving') . ' ' . __('customValidations.common.closing') . ' ' .
                __('customValidations.common.withdrawal') . ' = ' . Helper::tsNumbers($amount)
        ];

        $withdrawal = SavingWithdrawal::create(SavingWithdrawal::fieldMapping($account, (object) $data, true));
        SavingWithdrawal::processWithdrawal($withdrawal, $withdrawal_account, null, null, (array) $data);
    }

    private static function processInterest($account, $amount, $withdrawal_account_id, $withdrawal_account)
    {
        $expenseCatId   = ExpenseCategory::where('name', 'account_closing_interest')->value('id');
        $categoryName   = !$account->category->is_default ? $account->category->name : __("customValidations.category.default.{$account->category->name}");
        $description    = __('customValidations.common.acc_no') . ' = ' . Helper::tsNumbers($account->acc_no) . ', ' .
            __('customValidations.common.category') . ' = ' . $categoryName . ', ' .
            __('customValidations.common.saving') . ' ' . __('customValidations.common.closing') . ' ' . __('customValidations.common.interest') . ' ' .
            __('customValidations.common.withdrawal') . ' = ' . Helper::tsNumbers($amount);

        Expense::store(
            $withdrawal_account_id,
            $expenseCatId,
            $amount,
            $withdrawal_account->balance,
            $description
        );
        $withdrawal_account->increment('total_withdrawal', $amount);
    }

    public static function processClosingFee($account, $categoryConf)
    {
        $categoryName = !$account->category->is_default ? $account->category->name : __("customValidations.category.default.{$account->category->name}");
        $description = __('customValidations.common.acc_no') . ' = ' . Helper::tsNumbers($account->acc_no) . ', ' .
            __('customValidations.common.category') . ' = ' . $categoryName . ', ' .
            __('customValidations.common.saving') . ' ' . __('customValidations.common.closing') . ' ' .
            __('customValidations.common.withdrawal') . ' = ' . Helper::tsNumbers($categoryConf->saving_acc_closing_fee);

        $categoryId = AccountFeesCategory::where('name', 'closing_fee')->value('id');
        $feeAccount = Account::find($categoryConf->s_col_fee_acc_id);
        $incomeCatId = IncomeCategory::where('name', 'closing_fee')->value('id');

        SavingAccountFee::create([
            'saving_account_id' => $account->id,
            'account_fees_category_id' => $categoryId,
            'creator_id' => auth()->id(),
            'amount' => $categoryConf->saving_acc_closing_fee,
        ]);

        Income::store(
            $categoryConf->s_col_fee_acc_id,
            $incomeCatId,
            $categoryConf->saving_acc_closing_fee,
            $feeAccount->balance,
            $description
        );

        $feeAccount->increment('total_deposit', $categoryConf->saving_acc_closing_fee);
        $account->increment('total_withdrawn', $categoryConf->saving_acc_closing_fee);
    }

    public static function deleteAccountAndAssociations($account)
    {
        $account->delete();
        $account->SavingCollection()->delete();
        $account->SavingWithdrawal()->delete();
    }
}
