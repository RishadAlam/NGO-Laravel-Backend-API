<?php

namespace App\Models\Withdrawal;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SavingWithdrawal extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'field_id',
        'center_id',
        'category_id',
        'saving_account_id',
        'acc_no',
        'balance',
        'amount',
        'description',
        'creator_id',
    ];
}
