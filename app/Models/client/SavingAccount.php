<?php

namespace App\Models\client;

use App\Models\User;
use App\Models\field\Field;
use App\Models\center\Center;
use App\Models\category\Category;
use Illuminate\Support\Facades\Auth;
use App\Http\Traits\HelperScopesTrait;
use Illuminate\Database\Eloquent\Model;
use App\Http\Traits\BelongsToFieldTrait;
use App\Http\Traits\BelongsToAuthorTrait;
use App\Http\Traits\BelongsToCenterTrait;
use App\Http\Traits\BelongsToApproverTrait;
use App\Http\Traits\BelongsToCategoryTrait;
use App\Models\Withdrawal\SavingWithdrawal;
use App\Models\Collections\SavingCollection;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Http\Traits\BelongsToClientRegistrationTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SavingAccount extends Model
{
    use HasFactory,
        SoftDeletes,
        HelperScopesTrait,
        BelongsToFieldTrait,
        BelongsToCenterTrait,
        BelongsToCategoryTrait,
        BelongsToAuthorTrait,
        BelongsToClientRegistrationTrait,
        BelongsToApproverTrait;

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
        'acc_no',
        'start_date',
        'duration_date',
        'payable_installment',
        'payable_deposit',
        'payable_interest',
        'total_deposit_without_interest',
        'total_deposit_with_interest',
        'total_installment',
        'total_deposited',
        'total_withdrawn',
        'closing_balance',
        'closing_interest',
        'closing_balance_with_interest',
        'description',
        'status',
        'is_approved',
        'approved_by',
        'approved_at',
        'creator_id',
    ];

    /**
     * Relation with SavingAccountActionHistory Table.
     */
    public function SavingAccountActionHistory()
    {
        return $this->hasMany(SavingAccountActionHistory::class);
    }

    /**
     * Relation with Nominee Table.
     */
    public function Nominees()
    {
        return $this->hasMany(Nominee::class);
    }

    /**
     * Relation with Saving Collection Table
     */
    public function SavingCollection()
    {
        return $this->hasMany(SavingCollection::class)->withTrashed();
    }

    /**
     * Relation with Saving Withdrawal Table
     */
    public function SavingWithdrawal()
    {
        return $this->hasMany(SavingWithdrawal::class);
    }

    /**
     * Relation with Saving Account Fee Table
     */
    public function SavingAccountFee()
    {
        return $this->hasMany(SavingAccountFee::class);
    }

    /**
     * Relation with Saving Account Fee Table
     */
    public function SavingAccountClosing()
    {
        return $this->hasMany(SavingAccountClosing::class);
    }

    /**
     * Relationship belongs to ClientRegistration model.
     *
     * @return response()
     */
    public function ClientRegistration()
    {
        return $this->belongsTo(ClientRegistration::class)->withTrashed();
    }

    /**
     * Nominee Relation Scope
     */
    public function scopeNominees($query, ...$arg)
    {
        return $query->with("Nominees", function ($query) use ($arg) {
            $query->select(...$arg);
        });
    }

    /**
     * Pending Saving Registration Forms Scope.
     */
    public function scopeFetchPendingForms($query)
    {
        return $query->field('id', 'name')
            ->center('id', 'name')
            ->category('id', 'name', 'is_default')
            ->author('id', 'name')
            ->clientRegistration('id', 'acc_no', 'name', 'image_uri')
            ->nominees('id', 'saving_account_id', 'name', 'father_name', 'husband_name', 'mother_name', 'nid', 'dob', 'occupation', 'relation', 'gender', 'primary_phone', 'secondary_phone', 'image', 'image_uri', 'signature', 'signature_uri', 'address')
            ->pending()
            ->filter()
            ->when(!Auth::user()->can('pending_saving_acc_list_view_as_admin'), function ($query) {
                $query->createdBy();
            })
            ->orderedBy();
    }

    /**
     * Get Specific resource
     */
    public function scopeActiveSaving($query, $id)
    {
        $query->ClientRegistrationID($id)
            ->approve()
            ->active()
            ->field('id', 'name')
            ->center('id', 'name')
            ->clientRegistration('id', 'acc_no', 'name', 'image_uri')
            ->Category('id', 'name', 'is_default')
            ->Author('id', 'name')
            ->Approver('id', 'name');
    }

    /**
     * Get Specific resource
     */
    public function scopePendingSaving($query, $id)
    {
        $query->ClientRegistrationID($id)
            ->pending()
            ->Category('id', 'name', 'is_default')
            ->Author('id', 'name')
            ->Approver('id', 'name');
    }

    /**
     * Get Specific resource
     */
    public function scopeHoldSaving($query, $id)
    {
        $query->ClientRegistrationID($id)
            ->approve()
            ->hold()
            ->Category('id', 'name', 'is_default')
            ->Author('id', 'name')
            ->Approver('id', 'name');
    }

    /**
     * Get Specific resource
     */
    public function scopeClosedSaving($query, $id)
    {
        $query->ClientRegistrationID($id)
            ->approve()
            ->closed()
            ->Category('id', 'name', 'is_default')
            ->Author('id', 'name')
            ->Approver('id', 'name');
    }
}
