<?php

namespace App\Models\Collections;

use App\Http\Traits\HelperScopesTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LoanCollection extends Model
{
    use HasFactory, SoftDeletes, HelperScopesTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'field_id',
        'center_id',
        'category_id',
        'client_registration_id',
        'saving_account_id',
        'creator_id',
        'approved_by',
        'acc_no',
        'installment',
        'deposit',
        'loan',
        'interest',
        'total',
        'description',
        'is_approved'
    ];
}
