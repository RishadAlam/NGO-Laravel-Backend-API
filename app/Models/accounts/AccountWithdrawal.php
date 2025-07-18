<?php

namespace App\Models\accounts;

use App\Models\User;
use App\Models\accounts\Account;
use Illuminate\Database\Eloquent\Model;
use App\Http\Traits\BelongsToAuthorTrait;
use App\Http\Traits\BelongsToAccountTrait;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\accounts\AccountWithdrawalActionHistory;

class AccountWithdrawal extends Model
{
    use HasFactory,
        SoftDeletes,
        BelongsToAccountTrait,
        BelongsToAuthorTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'account_id',
        'amount',
        'previous_balance',
        'balance',
        'description',
        'date',
        'creator_id'
    ];

    /**
     * Mutator for Date
     */
    public function setDateAttribute($value)
    {
        $this->attributes['date'] =  date('Y-m-d h:m:s', strtotime($value));
    }

    /**
     * Relation with AccountWithdrawalActionHistory Table
     */
    public function AccountWithdrawalActionHistory()
    {
        return $this->hasMany(AccountWithdrawalActionHistory::class, 'withdrawal_id', 'id');
    }
}
