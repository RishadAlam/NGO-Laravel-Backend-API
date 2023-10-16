<?php

namespace App\Models\category;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'group',
        'description',
        'saving',
        'loan',
        'status',
        'is_default',
        'creator_id'
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
     * Relation with CenterActionHistory Table
     */
    public function CategoryActionHistory()
    {
        return $this->hasMany(CategoryActionHistory::class);
    }

    /**
     * Relation with Category Config Table
     */
    public function CategoryConfig()
    {
        return $this->hasMany(CategoryConfig::class)->withTrashed();
    }
}
