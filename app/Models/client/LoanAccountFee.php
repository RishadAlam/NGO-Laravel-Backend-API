<?php

namespace App\Models\client;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LoanAccountFee extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'loan_account_id',
        'account_fees_category_id',
        'creator_id',
        'amount',
        'description',
    ];
}
