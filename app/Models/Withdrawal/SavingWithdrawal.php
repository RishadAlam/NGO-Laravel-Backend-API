<?php

namespace App\Models\Withdrawal;

use App\Http\Traits\HelperScopesTrait;
use Illuminate\Database\Eloquent\Model;
use App\Http\Traits\BelongsToFieldTrait;
use App\Http\Traits\BelongsToAuthorTrait;
use App\Http\Traits\BelongsToCenterTrait;
use App\Http\Traits\BelongsToApproverTrait;
use App\Http\Traits\BelongsToCategoryTrait;
use App\Http\Traits\BelongsToSavingAccountTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SavingWithdrawal extends Model
{
    use HasFactory,
        HelperScopesTrait,
        BelongsToFieldTrait,
        BelongsToCenterTrait,
        BelongsToCategoryTrait,
        BelongsToAuthorTrait,
        BelongsToApproverTrait,
        BelongsToSavingAccountTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'field_id',
        'center_id',
        'category_id',
        'saving_account_id',
        'acc_no',
        'balance',
        'amount',
        'description',
        'creator_id',
    ];

    /**
     * Pending Saving Registration Forms Scope.
     */
    public function scopePendingWithdrawals($query)
    {
        return $query->pending()
            ->field('id', 'name')
            ->center('id', 'name')
            ->category('id', 'name', 'is_default')
            ->author('id', 'name')
            ->with('SavingAccount', function ($query) {
                $query->select('id', 'client_registration_id');
                $query->ClientRegistration('id', 'name', 'image_uri');
            })
            ->filter()
            ->orderedBy();
    }
}
