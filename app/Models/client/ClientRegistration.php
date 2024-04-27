<?php

namespace App\Models\client;

use App\Models\client\LoanAccount;
use App\Models\client\SavingAccount;
use Illuminate\Support\Facades\Auth;
use App\Http\Traits\HelperScopesTrait;
use Illuminate\Database\Eloquent\Model;
use App\Http\Traits\BelongsToFieldTrait;
use App\Http\Traits\BelongsToAuthorTrait;
use App\Http\Traits\BelongsToCenterTrait;
use App\Models\Collections\LoanCollection;
use App\Http\Traits\BelongsToApproverTrait;
use App\Models\Withdrawal\SavingWithdrawal;
use App\Models\Collections\SavingCollection;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Withdrawal\LoanSavingWithdrawal;
use App\Models\client\ClientRegistrationActionHistory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ClientRegistration extends Model
{
    use HasFactory,
        SoftDeletes,
        HelperScopesTrait,
        BelongsToFieldTrait,
        BelongsToCenterTrait,
        BelongsToAuthorTrait,
        BelongsToApproverTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'field_id',
        'center_id',
        'acc_no',
        'name',
        'father_name',
        'husband_name',
        'mother_name',
        'nid',
        'dob',
        'occupation',
        'religion',
        'gender',
        'primary_phone',
        'secondary_phone',
        'image',
        'image_uri',
        'signature',
        'signature_uri',
        'share',
        'annual_income',
        'bank_acc_no',
        'bank_check_no',
        'present_address',
        'permanent_address',
        'is_approved',
        'creator_id',
        'approved_by'
    ];

    /**
     * Relation with ClientRegistrationActionHistory Table
     */
    public function ClientRegistrationActionHistory()
    {
        return $this->hasMany(ClientRegistrationActionHistory::class, 'registration_id');
    }

    /**
     * Relation with SavingAccount Table
     */
    public function ActiveSavingAccount()
    {
        return $this->hasMany(SavingAccount::class);
    }

    /**
     * Relation with LoanAccount Table
     */
    public function ActiveLoanAccount()
    {
        return $this->hasMany(LoanAccount::class);
    }

    /**
     * Relation with SavingAccount Table
     */
    public function SavingAccount()
    {
        return $this->hasMany(SavingAccount::class)->withTrashed();
    }

    /**
     * Relation with LoanAccount Table
     */
    public function LoanAccount()
    {
        return $this->hasMany(LoanAccount::class)->withTrashed();
    }

    /**
     * Relation with SavingCollection Table
     */
    public function SavingCollection()
    {
        return $this->hasMany(SavingCollection::class)->withTrashed();
    }

    /**
     * Relation with LoanCollection Table
     */
    public function LoanCollection()
    {
        return $this->hasMany(LoanCollection::class)->withTrashed();
    }

    /**
     * Relation with SavingWithdrawal Table
     */
    public function SavingWithdrawal()
    {
        return $this->hasMany(SavingWithdrawal::class);
    }

    /**
     * Relation with LoanSavingWithdrawal Table
     */
    public function LoanSavingWithdrawal()
    {
        return $this->hasMany(LoanSavingWithdrawal::class);
    }

    /**
     * Mutator for address json Data
     */
    public function setPresentAddressAttribute($value)
    {
        $this->attributes['present_address'] = json_encode($value);
    }

    /**
     * Mutator for address json Data
     */
    public function setPermanentAddressAttribute($value)
    {
        $this->attributes['permanent_address'] = json_encode($value);
    }

    /**
     * accessor for json Data
     */
    public function getPresentAddressAttribute($value)
    {
        return json_decode($value);
    }

    /**
     * accessor for json Data
     */
    public function getPermanentAddressAttribute($value)
    {
        return json_decode($value);
    }

    /**
     * Pending Saving Registration Forms Scope.
     */
    public function scopeInfo($query)
    {
        return $query->approve()
            ->filter()
            ->orderedBy('acc_no', 'ASC');
    }

    /**
     * Pending Saving Registration Forms Scope.
     */
    public function scopeFetchPendingForms($query)
    {
        return $query->Field('id', 'name')
            ->Center('id', 'name')
            ->Author('id', 'name')
            ->pending()
            ->when(!Auth::user()->can('pending_client_registration_list_view_as_admin'), function ($query) {
                $query->createdBy();
            })
            ->filter()
            ->orderedBy();
    }

    /**
     * Pending Saving Registration Forms Scope.
     */
    public function scopeFetchAccounts($query, $field_id, $center_id)
    {
        return $query->fieldID($field_id)
            ->centerID($center_id)
            ->approve()
            ->orderedBy('acc_no', 'ASC');
    }

    /**
     * Filter Scope
     */
    public function scopeFilter($query)
    {
        $query->when(request('user_id'), function ($query) {
            $query->createdBy(request('user_id'));
        })
            ->when(request('field_id'), function ($query) {
                $query->fieldID(request('field_id'));
            })
            ->when(request('center_id'), function ($query) {
                $query->CenterID(request('center_id'));
            })
            ->when(request('search'), function ($query) {
                $query->where('acc_no', 'LIKE', '%' . request('search') . '%')
                    ->orWhere('name', 'LIKE', '%' . request('search') . '%');
            })
            ->when(request('limit'), function ($query) {
                $query->take(request('limit'));
            });
    }

    /**
     * Get Specific resource
     */
    public function scopeClient($query)
    {
        $query->approve()
            ->with(['ClientRegistrationActionHistory', 'ClientRegistrationActionHistory.Author:id,name,image_uri'])
            ->Field('id', 'name')
            ->Center('id', 'name')
            ->Author('id', 'name')
            ->Approver('id', 'name');
    }
}
