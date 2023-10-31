<?php

namespace App\Models\accounts;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AccountTransfer extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tx_acc_id',
        'rx_acc_id',
        'amount',
        'tx_prev_balance',
        'tx_balance',
        'rx_prev_balance',
        'rx_balance',
        'description',
        'date',
        'creator_id'
    ];

    /**
     * Relationship belongs to User model
     *
     * @return response()
     */
    public function Author()
    {
        return $this->belongsTo(User::class, 'creator_id', 'id')->withTrashed();
    }

    /**
     * Relationship belongs to Account model
     *
     * @return response()
     */
    public function TxAccount()
    {
        return $this->belongsTo(Account::class, 'tx_acc_id', 'id')->withTrashed();
    }

    /**
     * Relationship belongs to Account model
     *
     * @return response()
     */
    public function RxAccount()
    {
        return $this->belongsTo(Account::class, 'rx_acc_id', 'id')->withTrashed();
    }
}
