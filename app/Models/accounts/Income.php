<?php

namespace App\Models\accounts;

use Carbon\Carbon;
use App\Models\User;
use App\Models\accounts\Account;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Income extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'account_id',
        'income_category_id',
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
     * Relationship belongs to Account model
     *
     * @return response()
     */
    public function Account()
    {
        return $this->belongsTo(Account::class)->withTrashed();
    }

    /**
     * Relationship belongs to Income Category model
     *
     * @return response()
     */
    public function IncomeCategory()
    {
        return $this->belongsTo(IncomeCategory::class)->withTrashed();
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
     * Relation with IncomeActionHistory Table
     */
    public function IncomeActionHistory()
    {
        return $this->hasMany(IncomeActionHistory::class);
    }

    /**
     * Income Store
     * 
     * @param int $account_id
     * @param int $income_category_id
     * @param int $amount
     * @param int $previous_balance
     * @param string $description
     * @param string $date
     * @param int $author
     */
    public static function store($account_id, $income_category_id, $amount, $previous_balance, $description = null, $date = null, $author = null)
    {
        $arr = [
            'account_id'            => $account_id,
            'income_category_id'    => $income_category_id,
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
