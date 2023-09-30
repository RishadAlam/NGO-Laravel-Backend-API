<?php

namespace App\Models\field;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FieldActionHistory extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'field_id',
        'author_id',
        'name',
        'image_uri',
        'action_type',
        'action_details'
    ];

    /**
     * Relationship belongs to User model
     *
     * @return response()
     */
    public function Field()
    {
        return $this->belongsTo(Field::class);
    }

    /**
     * Relationship belongs to User model
     *
     * @return response()
     */
    public function Author()
    {
        return $this->belongsTo(User::class, 'author_id', 'id');
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
