<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
        'expired_at',
    ];

    /**
     * Relationship belongs to User model
     * 
     * @return response()
     */
    public function User()
    {
        return $this->belongsTo(User::class);
    }
}
