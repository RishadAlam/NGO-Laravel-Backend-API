<?php

namespace App\Models\client;

use App\Models\User;
use App\Models\client\SavingAccount;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SavingAccountActionHistory extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'saving_account_id',
        'author_id',
        'name',
        'image_uri',
        'action_type',
        'action_details'
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
     * Relationship belongs to User model
     *
     * @return response()
     */
    public function Author()
    {
        return $this->belongsTo(User::class, 'author_id', 'id')->withTrashed();
    }

    /**
     * Mutator for action Details json Data
     */
    public function setActionDetailsAttribute($value)
    {
        $this->attributes['action_details'] = json_encode($value);
    }

    /**
     * accessor for action Details json Data
     */
    public function getActionDetailsAttribute($value)
    {
        return json_decode($value);
    }
}
