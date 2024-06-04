<?php

namespace App\Models\client;

use Illuminate\Database\Eloquent\Model;
use App\Http\Traits\BelongsToSavingAccountTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SavingAccountClosing extends Model
{
    use HasFactory, BelongsToSavingAccountTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'saving_account_id',
        'creator_id',
        'approved_by',
        'account_id',
        'balance',
        'interest',
        'total_balance',
        'description',
        'is_approved',
    ];
}
