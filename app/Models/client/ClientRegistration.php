<?php

namespace App\Models\client;

use App\Models\User;
use App\Models\field\Field;
use App\Models\center\Center;
use Illuminate\Support\Facades\Auth;
use App\Http\Traits\HelperScopesTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\client\ClientRegistrationActionHistory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ClientRegistration extends Model
{
    use HasFactory, SoftDeletes, HelperScopesTrait;

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
     * Relation with ClientRegistrationActionHistory Table
     */
    public function ClientRegistrationActionHistory()
    {
        return $this->hasMany(ClientRegistrationActionHistory::class);
    }

    /**
     * Relationship belongs to Field model
     *
     * @return response()
     */
    public function Field()
    {
        return $this->belongsTo(Field::class)->withTrashed();
    }

    /**
     * Relationship belongs to Center model
     *
     * @return response()
     */
    public function Center()
    {
        return $this->belongsTo(Center::class)->withTrashed();
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
    public function scopeFetchPendingForms($query)
    {
        return $query->Field('id', 'name')
            ->Center('id', 'name')
            ->Author('id', 'name')
            ->where('is_approved', false)
            ->filter()
            ->orderedBy();
    }

    /**
     * Filter Scope
     */
    public function scopeFilter($query)
    {
        $query->when(request('user_id'), function ($query) {
            $query->createdBy(request('user_id'));
        })
            ->when(!Auth::user()->can('pending_client_registration_list_view_as_admin'), function ($query) {
                $query->createdBy();
            })
            ->when(request('field_id'), function ($query) {
                $query->fieldID(request('field_id'));
            })
            ->when(request('center_id'), function ($query) {
                $query->CenterID(request('center_id'));
            });
    }
}
