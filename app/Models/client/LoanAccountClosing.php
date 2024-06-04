<?php

namespace App\Models\client;

use Illuminate\Database\Eloquent\Model;
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
        'balance',
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
}
