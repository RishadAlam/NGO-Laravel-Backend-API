<?php

namespace App\Models\accounts;

use App\Models\User;
use App\Models\accounts\Account;
use Illuminate\Database\Eloquent\Model;
use App\Models\accounts\ExpenseCategory;
use App\Http\Traits\BelongsToAuthorTrait;
use App\Http\Traits\BelongsToAccountTrait;
use App\Models\accounts\AccountActionHistory;
use App\Models\accounts\ExpenseActionHistory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Expense extends Model
{
    use HasFactory,
        SoftDeletes,
        BelongsToAccountTrait,
        BelongsToAuthorTrait;

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
     * Mutator for Date
     */
    public function setDateAttribute($value)
    {
        $this->attributes['date'] =  date('Y-m-d h:m:s', strtotime($value));
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
     * Relation with ExpenseActionHistory Table
     */
    public function ExpenseActionHistory()
    {
        return $this->hasMany(ExpenseActionHistory::class);
    }

    /**
     * Expense Store
     * 
     * @param int $account_id
     * @param int $expense_category_id
     * @param int $amount
     * @param int $previous_balance
     * @param string $description
     * @param string $date
     * @param int $author
     */
    public static function store($account_id, $expense_category_id, $amount, $previous_balance, $description = null, $date = null, $author = null)
    {
        $arr = [
            'account_id'            => $account_id,
            'expense_category_id'    => $expense_category_id,
            'amount'                => $amount,
            'previous_balance'      => $previous_balance,
        ];

        if ($date) {
            $arr['date'] = $date;
        }
        if ($description) {
            $arr['description'] = $description;
        }

        $arr['creator_id'] = $author ? $author : auth()->id();
        return self::create($arr);
    }
}
