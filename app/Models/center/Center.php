<?php

namespace App\Models\center;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Center extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'description',
        'status',
        'created_by'
    ];

    /**
     * Relationship belongs to User model
     *
     * @return response()
     */
    public function Author()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    /**
     * Relation with CenterActionHistory Table
     */
    public function CenterActionHistory()
    {
        return $this->hasMany(CenterActionHistory::class);
    }
}
