<?php

namespace App\Models\accounts;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use App\Models\accounts\AccountActionHistory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Expense extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'expense_category_id',
        'amount',
        'description',
        'date',
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
     * Relation with ExpenseActionHistory Table
     */
    public function ExpenseActionHistory()
    {
        return $this->hasMany(ExpenseActionHistory::class);
    }
}
