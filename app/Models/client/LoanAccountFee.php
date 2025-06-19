<?php

namespace App\Models\client;

use App\Http\Traits\HelperScopesTrait;
use Illuminate\Database\Eloquent\Model;
use App\Http\Traits\BelongsToAuthorTrait;
use App\Http\Traits\BelongsToLoanAccountTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LoanAccountFee extends Model
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
        'account_fees_category_id',
        'creator_id',
        'amount',
        'description',
    ];

    /**
     * Relationship belongs to AccountFeesCategory model
     *
     * @return response()
     */
    public function AccountFeesCategory()
    {
        return $this->belongsTo(AccountFeesCategory::class, 'account_fees_category_id', 'id');
    }
}
