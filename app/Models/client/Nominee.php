<?php

namespace App\Models\client;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Nominee extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'saving_account_id',
        'name',
        'father_name',
        'husband_name',
        'mother_name',
        'nid',
        'dob',
        'occupation',
        'relation',
        'gender',
        'primary_phone',
        'secondary_phone',
        'image',
        'image_uri',
        'signature',
        'signature_uri',
        'address',
    ];

    /**
     * Relationship belongs to SavingAccount model
     *
     * @return response()
     */
    public function SavingAccount()
    {
        return $this->belongsTo(SavingAccount::class)->withTrashed();
    }

    /**
     * Mutator for address json Data
     */
    public function setAddressAttribute($value)
    {
        $this->attributes['address'] = json_encode($value);
    }

    /**
     * accessor for json Data
     */
    public function getAddressAttribute($value)
    {
        return json_decode($value);
    }
}
