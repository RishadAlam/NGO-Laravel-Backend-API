<?php

namespace App\Models\accounts;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use App\Models\accounts\ExpenseCategory;
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
        'account_id',
        'expense_category_id',
        'amount',
        'previous_balance',
        'balance',
        'description',
        'date',
        'creator_id'
    ];

    /**
     * Relationship belongs to Account model
     *
     * @return response()
     */
    public function Account()
    {
        return $this->belongsTo(Account::class)->withTrashed();
    }

    /**
     * Relationship belongs to Expense Category model
     *
     * @return response()
     */
    public function ExpenseCategory()
    {
        return $this->belongsTo(ExpenseCategory::class)->withTrashed();
    }


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
