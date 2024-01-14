<?php

namespace App\Models\category;

use App\Models\accounts\Account;
use App\Http\Traits\HelperScopesTrait;
use Illuminate\Database\Eloquent\Model;
use App\Http\Traits\BelongsToCategoryTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CategoryConfig extends Model
{
    use HasFactory, HelperScopesTrait, BelongsToCategoryTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'category_id',
        's_reg_fee_acc_id',
        's_col_fee_acc_id',
        'l_reg_fee_acc_id',
        'l_col_fee_acc_id',
        's_with_fee_acc_id',
        'ls_with_fee_acc_id',
        'saving_acc_reg_fee',
        'saving_acc_closing_fee',
        'loan_acc_reg_fee',
        'loan_acc_closing_fee',
        'saving_withdrawal_fee',
        'loan_saving_withdrawal_fee',
        'min_saving_withdrawal',
        'max_saving_withdrawal',
        'min_loan_saving_withdrawal',
        'max_loan_saving_withdrawal',
        'saving_acc_check_time_period',
        'loan_acc_check_time_period',
        'disable_unchecked_saving_acc',
        'disable_unchecked_loan_acc',
        'inactive_saving_acc_disable_time_period',
        'inactive_loan_acc_disable_time_period',
    ];

    /**
     * Relationship belongs to Account model
     *
     * @return response()
     */
    public function saving_reg_fee_store_acc()
    {
        return $this->belongsTo(Account::class, 's_reg_fee_acc_id')->withTrashed();
    }

    /**
     * Relationship belongs to Account model
     *
     * @return response()
     */
    public function loan_reg_fee_store_acc()
    {
        return $this->belongsTo(Account::class, 'l_reg_fee_acc_id')->withTrashed();
    }
}
