<?php

namespace App\Models\category;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CategoryConfig extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'category_id',
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
     * Relationship belongs to User model
     *
     * @return response()
     */
    public function Category()
    {
        return $this->belongsTo(Category::class);
    }
}
