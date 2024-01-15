<?php

namespace App\Models\client;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LoanAccountCheck extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'loan_account_id',
        'balance',
        'installment_recovered',
        'installment_remaining',
        'loan_recovered',
        'loan_remaining',
        'interest_recovered',
        'interest_remaining',
        'description',
        'checked_by',
        'next_check_in_at',
    ];
}
