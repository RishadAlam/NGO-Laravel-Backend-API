<?php

namespace App\Models\client;

use App\Models\User;
use App\Http\Traits\HelperScopesTrait;
use Illuminate\Database\Eloquent\Model;
use App\Http\Traits\BelongsToSavingAccountTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SavingAccountCheck extends Model
{
    use HasFactory, HelperScopesTrait, BelongsToSavingAccountTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'saving_account_id',
        'balance',
        'installment_recovered',
        'description',
        'checked_by',
        'next_check_in_at',
    ];

    /**
     * Relationship belongs to User model
     *
     * @return response()
     */
    public function Author()
    {
        return $this->belongsTo(User::class, 'checked_by', 'id')->withTrashed();
    }
}
