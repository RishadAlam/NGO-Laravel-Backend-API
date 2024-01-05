<?php

namespace App\Models\Collections;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use App\Models\Collections\LoanCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LoanCollectionActionHistory extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'loan_collection_id',
        'author_id',
        'name',
        'image_uri',
        'action_type',
        'action_details'
    ];

    /**
     * Relationship belongs to Loan Collection model
     *
     * @return response()
     */
    public function LoanCollection()
    {
        return $this->belongsTo(LoanCollection::class)->withTrashed();
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
