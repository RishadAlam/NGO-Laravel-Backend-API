<?php

namespace App\Models\client;

use App\Helpers\Helper;
use App\Models\accounts\Income;
use App\Models\accounts\Account;
use App\Models\accounts\IncomeCategory;
use App\Models\category\CategoryConfig;
use Illuminate\Database\Eloquent\Model;
use App\Models\client\AccountFeesCategory;
use App\Http\Traits\BelongsToLoanAccountTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LoanAccountClosing extends Model
{
    use HasFactory, BelongsToLoanAccountTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'loan_account_id',
        'creator_id',
        'approved_by',
        'account_id',
        'main_balance',
        'total_balance',
        'payable_installment',
        'total_rec_installment',
        'loan_given',
        'total_loan_rec',
        'total_loan_remaining',
        'total_payable_interest',
        'total_interest_rec',
        'total_interest_remaining',
        'description',
        'is_approved',
    ];

    /**
     * Set Field Map
     * 
     * @param object $data
     * @param LoanAccount $account
     * @param boolean $isStore
     * @param boolean $isApproved
     * 
     * @return array
     */
    public static function setFieldMap($data, $account, $isStore, $isApproved)
    {
        $map = [
            'payable_installment'       => $account->payable_installment,
            'total_rec_installment'     => $account->total_rec_installment,
            'main_balance'              => $account->balance,
            'total_balance'             => $data->total_balance,
            'description'               => $data->description,
            'loan_given'                => $account->loan_given,
            'total_loan_rec'            => $account->total_loan_rec,
            'total_loan_remaining'      => $account->total_loan_remaining,
            'total_payable_interest'    => $account->total_payable_interest,
            'total_interest_rec'        => $account->total_interest_rec,
            'total_interest_remaining'  => $account->total_interest_remaining,
        ];

        if ($isStore) {
            $map += [
                'loan_account_id'   => $data->account_id,
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

    public static function handleApprovedAccountClosing($data, $account)
    {
        $categoryConf = CategoryConfig::categoryID($account->category_id)
            ->first(['loan_acc_closing_fee', 'l_col_fee_acc_id']);

        if ($categoryConf->loan_acc_closing_fee > 0) {
            static::processClosingFee($account, $categoryConf);
        }

        static::deleteAccountAndAssociations($account);
        LoanAccountActionHistory::create(Helper::setActionHistory('loan_account_id', $account->id, 'delete', []));
    }

    public static function processClosingFee($account, $categoryConf)
    {
        $categoryName = !$account->category->is_default ? $account->category->name : __("customValidations.category.default.{$account->category->name}");
        $description = __('customValidations.common.acc_no') . ' = ' . Helper::tsNumbers($account->acc_no) . ', ' .
            __('customValidations.common.category') . ' = ' . $categoryName . ', ' .
            __('customValidations.common.loan') . ' ' . __('customValidations.common.closing') . ' ' .
            __('customValidations.common.fee') . ' = ' . Helper::tsNumbers($categoryConf->loan_acc_closing_fee);

        $categoryId = AccountFeesCategory::where('name', 'closing_fee')->value('id');
        $feeAccount = Account::find($categoryConf->l_col_fee_acc_id);
        $incomeCatId = IncomeCategory::where('name', 'closing_fee')->value('id');

        LoanAccountFee::create([
            'loan_account_id'           => $account->id,
            'account_fees_category_id'  => $categoryId,
            'creator_id'                => auth()->id(),
            'amount'                    => $categoryConf->loan_acc_closing_fee,
        ]);

        Income::store(
            $categoryConf->l_col_fee_acc_id,
            $incomeCatId,
            $categoryConf->loan_acc_closing_fee,
            $feeAccount->balance,
            $description
        );

        $feeAccount->increment('total_deposit', $categoryConf->loan_acc_closing_fee);
        $account->increment('total_withdrawn', $categoryConf->loan_acc_closing_fee);
    }

    public static function deleteAccountAndAssociations($account)
    {
        $account->delete();
        $account->LoanCollection()->delete();
        $account->LoanSavingWithdrawal()->delete();
    }
}
