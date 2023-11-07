<?php

namespace App\Models\client;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\client\ClientRegistrationActionHistory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ClientRegistration extends Model
{
    use HasFactory, SoftDeletes;

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
        'share',
        'present_address',
        'permanent_address',
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
}
