<?php

namespace App\Models\Withdrawal;

use Carbon\Carbon;
use App\Helpers\Helper;
use App\Models\accounts\Income;
use App\Models\accounts\Account;
use App\Models\accounts\Expense;
use App\Models\client\SavingAccount;
use Illuminate\Support\Facades\Auth;
use App\Http\Traits\HelperScopesTrait;
use App\Models\accounts\IncomeCategory;
use App\Models\client\SavingAccountFee;
use Illuminate\Database\Eloquent\Model;
use App\Http\Traits\BelongsToFieldTrait;
use App\Models\accounts\ExpenseCategory;
use App\Http\Traits\BelongsToAuthorTrait;
use App\Http\Traits\BelongsToCenterTrait;
use App\Http\Traits\BelongsToAccountTrait;
use App\Models\client\AccountFeesCategory;
use App\Http\Traits\BelongsToApproverTrait;
use App\Http\Traits\BelongsToCategoryTrait;
use App\Http\Traits\BelongsToSavingAccountTrait;
use App\Http\Traits\BelongsToClientRegistrationTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SavingWithdrawal extends Model
{
    use HasFactory,
        HelperScopesTrait,
        BelongsToFieldTrait,
        BelongsToCenterTrait,
        BelongsToCategoryTrait,
        BelongsToAuthorTrait,
        BelongsToApproverTrait,
        BelongsToClientRegistrationTrait,
        BelongsToSavingAccountTrait,
        BelongsToAccountTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'field_id',
        'center_id',
        'category_id',
        'client_registration_id',
        'saving_account_id',
        'approved_by',
        'is_approved',
        'approved_at',
        'acc_no',
        'balance',
        'amount',
        'description',
        'creator_id',
    ];

    /**
     * Relation with SavingWithdrawalActionHistory Table.
     */
    public function SavingWithdrawalActionHistory()
    {
        return $this->hasMany(SavingWithdrawalActionHistory::class);
    }

    /**
     * Today Collection
     */
    public static function currentDaySavingWithdrawal()
    {
        return static::today()
            ->clientRegistration('id', 'name', 'image_uri')
            ->category('id', 'name', 'is_default')
            ->field('id', 'name')
            ->center('id', 'name')
            ->account('id', 'name', 'is_default')
            ->author('id', 'name', 'image_uri')
            ->when(!Auth::user()->can('view_dashboard_as_admin'), function ($query) {
                $query->CreatedBy(Auth::user()->id);
            })
            ->latest()
            ->get(['id', 'field_id', 'center_id', 'category_id', 'client_registration_id', 'account_id', 'creator_id', 'acc_no', 'balance', 'amount', 'balance_remaining', 'description', 'created_at']);
    }

    /**
     * Pending Saving Registration Forms Scope.
     */
    public function scopePendingWithdrawals($query)
    {
        return $query->pending()
            ->field('id', 'name')
            ->center('id', 'name')
            ->category('id', 'name', 'is_default')
            ->author('id', 'name')
            ->with(
                [
                    'SavingAccount' => function ($query) {
                        $query->select('id', 'balance', 'client_registration_id');
                        $query->ClientRegistration('id', 'name', 'image_uri')
                            ->withTrashed();
                    },
                    'Category' => function ($query) {
                        $query->select('id', 'name', 'is_default');
                        $query->with('CategoryConfig:id,category_id,saving_withdrawal_fee')
                            ->withTrashed();
                    }
                ]
            )
            ->when(!Auth::user()->can('pending_saving_withdrawal_list_view_as_admin'), function ($query) {
                $query->createdBy();
            })
            ->filter()
            ->orderedBy();
    }

    /**
     * Field Mapping Withdrawal Data
     *
     * @param SavingAccount $account
     * @param object $requestData
     * @param boolean $is_store
     * @return array
     */
    public static function fieldMapping(SavingAccount $account, object $requestData, $is_store = false)
    {
        $field_map = [
            'balance'       => $account->balance,
            'amount'        => $requestData->amount,
            'description'   => $requestData->description,
        ];
        if ($is_store) {
            $field_map += [
                'field_id'                  => $account->field_id,
                'center_id'                 => $account->center_id,
                'category_id'               => $account->category_id,
                'client_registration_id'    => $account->client_registration_id,
                'saving_account_id'         => $account->id,
                'acc_no'                    => $account->acc_no,
                'creator_id'                => auth()->id(),
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
    public static function processWithdrawal(SavingWithdrawal $withdrawal, Account $account = null, int $fee = null, int $feeAccId = null, array $requestData): void
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
