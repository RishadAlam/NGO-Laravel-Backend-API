<?php

namespace App\Models\Collections;

use App\Http\Traits\HelperScopesTrait;
use Illuminate\Database\Eloquent\Model;
use App\Http\Traits\BelongsToFieldTrait;
use App\Http\Traits\BelongsToAuthorTrait;
use App\Http\Traits\BelongsToCenterTrait;
use App\Models\client\ClientRegistration;
use App\Http\Traits\BelongsToAccountTrait;
use App\Http\Traits\BelongsToCategoryTrait;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Http\Traits\BelongsToClientRegistrationTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Collections\LoanCollectionActionHistory;

class LoanCollection extends Model
{
    use HasFactory,
        SoftDeletes,
        HelperScopesTrait,
        BelongsToFieldTrait,
        BelongsToCenterTrait,
        BelongsToCategoryTrait,
        BelongsToAuthorTrait,
        BelongsToAccountTrait,
        BelongsToClientRegistrationTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'field_id',
        'center_id',
        'category_id',
        'client_registration_id',
        'loan_account_id',
        'account_id',
        'creator_id',
        'approved_by',
        'acc_no',
        'installment',
        'deposit',
        'loan',
        'interest',
        'total',
        'description',
        'is_approved'
    ];

    /**
     * Relation with LoanCollectionActionHistory Table.
     */
    public function LoanCollectionActionHistory()
    {
        return $this->hasMany(LoanCollectionActionHistory::class);
    }
}
