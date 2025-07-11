<?php

namespace App\Models\client;

use App\Helpers\Helper;
use App\Models\accounts\Income;
use App\Models\accounts\Account;
use App\Models\accounts\Expense;
use Illuminate\Support\Facades\Auth;
use App\Http\Traits\HelperScopesTrait;
use App\Models\accounts\IncomeCategory;
use App\Models\category\CategoryConfig;
use Illuminate\Database\Eloquent\Model;
use App\Models\accounts\ExpenseCategory;
use App\Http\Traits\BelongsToAuthorTrait;
use App\Models\Withdrawal\SavingWithdrawal;
use App\Http\Traits\BelongsToSavingAccountTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SavingAccountClosing extends Model
{
    use HasFactory,
        HelperScopesTrait,
        BelongsToAuthorTrait,
        BelongsToSavingAccountTrait;

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
     * Pending Saving Account Closing Forms Scope.
     */
    public function scopePendingClosings($query)
    {
        return $query->pending()
            ->author('id', 'name')
            ->whereHas('SavingAccount', function ($query) {
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
                    'SavingAccount' => function ($query) {
                        $query->select('id', 'acc_no', 'field_id', 'center_id', 'category_id', 'client_registration_id')
                            ->ClientRegistration('id', 'name', 'image_uri')
                            ->field('id', 'name')
                            ->center('id', 'name')
                            ->category('id', 'name', 'is_default')
                            ->withTrashed();
                    },
                ]
            )
            ->when(!Auth::user()->can('pending_req_to_delete_saving_acc_list_view_as_admin'), function ($query) {
                $query->createdBy();
            })
            ->when((Auth::user()->can('pending_req_to_delete_saving_acc_list_view_as_admin') && request('user_id')), function ($query) {
                $query->createdBy(request('user_id'));
            })
            ->orderedBy();
    }

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

    /**
     * Handle to approved account closing process
     * @param object $data
     * 
     * @return void
     */
    public static function handleApprovedAccountClosing($data)
    {
        $account            = SavingAccount::with('Category:id,name,is_default')->find($data->account_id);
        $categoryConf       = CategoryConfig::categoryID($account->category_id)->first(['saving_acc_closing_fee', 's_col_fee_acc_id']);

        $withdrawal_account = null;
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

    /**
     * Account Withdrawal Processing
     * 
     * @param SavingAccount $account
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
            'description' => __('customValidations.common.acc_no') . ' = ' . Helper::tsNumbers($account->acc_no) . ', ' .
                __('customValidations.common.category') . ' = ' . $categoryName . ', ' .
                __('customValidations.common.saving') . ' ' . __('customValidations.common.closing') . ' ' .
                __('customValidations.common.withdrawal') . ' = ' . Helper::tsNumbers($amount)
        ];

        $withdrawal = SavingWithdrawal::create(SavingWithdrawal::fieldMapping($account, (object) $data, true));
        SavingWithdrawal::processWithdrawal($withdrawal, $withdrawal_account, null, null, (array) $data);
    }

    /**
     * Account Interest Processing
     * 
     * @param SavingAccount $account
     * @param int $amount
     * @param int $withdrawal_account_id
     * @param Account $withdrawal_account
     * 
     * @return void
     */
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

    /**
     * Account Closing fee Processing
     * 
     * @param SavingAccount $account
     * @param object $categoryConf
     * 
     * @return void
     */
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

    /**
     * Delete Account And Associations
     * 
     * @param SavingAccount $account
     * 
     * @return void
     */
    public static function deleteAccountAndAssociations($account)
    {
        $account->delete();
        $account->SavingCollection()->delete();
        $account->SavingWithdrawal()->delete();
    }
}
