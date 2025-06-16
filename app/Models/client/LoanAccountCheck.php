<?php

namespace App\Models\client;

use App\Models\User;
use App\Http\Traits\HelperScopesTrait;
use Illuminate\Database\Eloquent\Model;
use App\Http\Traits\BelongsToLoanAccountTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LoanAccountCheck extends Model
{
    use HasFactory, HelperScopesTrait, BelongsToLoanAccountTrait;

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

    /**
     * Relationship belongs to User model
     *
     * @return response()
     */
    public function Author()
    {
        return $this->belongsTo(User::class, 'checked_by', 'id')->withTrashed();
    }
}
