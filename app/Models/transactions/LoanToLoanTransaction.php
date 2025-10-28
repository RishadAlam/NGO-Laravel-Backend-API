<?php

namespace App\Models\transactions;

use App\Models\client\LoanAccount;
use App\Http\Traits\HelperScopesTrait;
use Illuminate\Database\Eloquent\Model;
use App\Http\Traits\BelongsToAuthorTrait;
use App\Http\Traits\BelongsToApproverTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LoanToLoanTransaction extends Model
{
    use HasFactory,
        HelperScopesTrait,
        BelongsToAuthorTrait,
        BelongsToApproverTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'creator_id',
        'approved_by',
        'tx_acc_id',
        'rx_acc_id',
        'amount',
        'tx_prev_balance',
        'rx_prev_balance',
        'description',
        'is_approved',
        'approved_at',
    ];

    /**
     * Relationship belongs to LoanAccount model
     *
     * @return response()
     */
    public function TXAccount()
    {
        return $this->belongsTo(LoanAccount::class, 'tx_acc_id', 'id')->withTrashed();
    }

    /**
     * Relationship belongs to LoanAccount model
     *
     * @return response()
     */
    public function RXAccount()
    {
        return $this->belongsTo(LoanAccount::class, 'rx_acc_id', 'id')->withTrashed();
    }
}
