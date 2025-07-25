<?php

namespace App\Models\client;

use App\Helpers\Helper;
use App\Models\accounts\Income;
use App\Models\accounts\Account;
use Illuminate\Support\Facades\Auth;
use App\Http\Traits\HelperScopesTrait;
use App\Models\accounts\IncomeCategory;
use App\Models\category\CategoryConfig;
use Illuminate\Database\Eloquent\Model;
use App\Http\Traits\BelongsToAuthorTrait;
use App\Models\client\AccountFeesCategory;
use App\Http\Traits\BelongsToLoanAccountTrait;
use App\Models\Withdrawal\LoanSavingWithdrawal;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LoanAccountClosing extends Model
{
    use HasFactory,
        HelperScopesTrait,
        BelongsToAuthorTrait,
        BelongsToLoanAccountTrait;

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
     * Pending Saving Account Closing Forms Scope.
     */
    public function scopePendingClosings($query)
    {
        return $query->pending()
            ->author('id', 'name')
            ->whereHas('LoanAccount', function ($query) {
                $query->when(request('field_id'), function ($query) {
                    $query->where('field_id', request('field_id'));
                })
                    ->when(request('center_id'), function ($query) {
                        $query->centerID(request('center_id'));
                    })
                    ->when(request('category_id'), function ($query) {
                        $query->categoryID(request('category_id'));
                    });
            })
            ->with(
                [
                    'LoanAccount' => function ($query) {
                        $query->select('id', 'acc_no', 'field_id', 'center_id', 'category_id', 'client_registration_id')
                            ->ClientRegistration('id', 'name', 'image_uri')
                            ->field('id', 'name')
                            ->center('id', 'name')
                            ->category('id', 'name', 'is_default')
                            ->withTrashed();
                    },
                ]
            )
            ->when(!Auth::user()->can('pending_req_to_delete_loan_acc_list_view_as_admin'), function ($query) {
                $query->createdBy();
            })
            ->when((Auth::user()->can('pending_req_to_delete_loan_acc_list_view_as_admin') && request('user_id')), function ($query) {
                $query->createdBy(request('user_id'));
            })
            ->orderedBy();
    }

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

    /**
     * Handle to approved account closing process
     * @param LoanAccount $account
     * @param object $data
     * 
     * @return void
     */
    public static function handleApprovedAccountClosing($account, $data)
    {

        $withdrawal_account = null;
        $categoryConf       = CategoryConfig::categoryID($account->category_id)
            ->first(['loan_acc_closing_fee', 'l_col_fee_acc_id']);
        $withdraw_amount    = $account->balance - $categoryConf->loan_acc_closing_fee;

        if (!empty($data->withdrawal_account_id)) {
            $withdrawal_account = Account::find($data->withdrawal_account_id);
        }
        if ($categoryConf->loan_acc_closing_fee > 0) {
            static::processClosingFee($account, $categoryConf);
        }
        if ($withdraw_amount > 0) {
            static::processWithdrawal($account, $withdraw_amount,  $data->withdrawal_account_id, $withdrawal_account);
        }

        static::deleteAccountAndAssociations($account);
        LoanAccountActionHistory::create(Helper::setActionHistory('loan_account_id', $account->id, 'delete', []));
    }

    /**
     * Account Withdrawal Processing
     * 
     * @param LoanAccount $account
     * @param int $amount
     * @param int $withdrawal_account_id
     * @param Account $withdrawal_account
     * 
     * @return void
     */
    private static function processWithdrawal($account, $amount, $withdrawal_account_id, $withdrawal_account)
    {
        $categoryName   = !$account->category->is_default ? $account->category->name : __("customValidations.category.default.{$account->category->name}");
        $data           = [
            'amount'        => $amount,
            'account'       => $withdrawal_account_id,
            'description'   => __('customValidations.common.acc_no') . ' = ' . Helper::tsNumbers($account->acc_no) . ', ' .
                __('customValidations.common.category') . ' = ' . $categoryName . ', '  . __('customValidations.common.loan') . '-' .
                __('customValidations.common.saving') . ' ' . __('customValidations.common.closing') . ' ' .
                __('customValidations.common.withdrawal') . ' = ' . Helper::tsNumbers($amount)
        ];

        $withdrawal = LoanSavingWithdrawal::create(LoanSavingWithdrawal::fieldMapping($account, (object) $data, true));
        LoanSavingWithdrawal::processWithdrawal($withdrawal, $withdrawal_account, null, null, (array) $data);
    }

    /**
     * Account Closing fee Processing
     * 
     * @param LoanAccount $account
     * @param object $categoryConf
     * 
     * @return void
     */
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

    /**
     * Delete Account And Associations
     * 
     * @param LoanAccount $account
     * 
     * @return void
     */
    public static function deleteAccountAndAssociations($account)
    {
        $account->delete();
        $account->LoanCollection()->delete();
        $account->LoanSavingWithdrawal()->delete();
    }
}
