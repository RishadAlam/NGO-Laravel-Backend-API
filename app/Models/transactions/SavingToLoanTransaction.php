<?php

namespace App\Models\transactions;

use App\Http\Traits\BelongsToApproverTrait;
use App\Http\Traits\BelongsToAuthorTrait;
use App\Http\Traits\HelperScopesTrait;
use App\Models\client\LoanAccount;
use App\Models\client\SavingAccount;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SavingToLoanTransaction extends Model
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
     * Relationship belongs to SavingAccount model
     *
     * @return response()
     */
    public function TXAccount()
    {
        return $this->belongsTo(SavingAccount::class, 'tx_acc_id', 'id')->withTrashed();
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
