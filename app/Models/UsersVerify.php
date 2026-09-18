<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UsersVerify extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'otp',
        'purpose',
        'expired_at',
    ];

    /**
     * Relationship belongs to User model
     *
     * @return response()
     */
    public function User()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}
