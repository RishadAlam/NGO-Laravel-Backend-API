<?php

namespace App\Models\accounts;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use App\Http\Traits\BelongsToAuthorTrait;
use App\Models\accounts\AccountActionHistory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Account extends Model
{
    use HasFactory,
        SoftDeletes,
        BelongsToAuthorTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'acc_no',
        'acc_details',
        'total_deposit',
        'total_withdraw',
        'balance',
        'is_default',
        'status',
        'creator_id'
    ];

    /**
     * Relation with AccountActionHistory Table
     */
    public function AccountActionHistory()
    {
        return $this->hasMany(AccountActionHistory::class);
    }
}
