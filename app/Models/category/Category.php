<?php

namespace App\Models\category;

use App\Models\User;
use App\Helpers\Helper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Http\Traits\HelperScopesTrait;
use App\Models\category\CategoryConfig;
use Illuminate\Database\Eloquent\Model;
use App\Http\Traits\BelongsToAuthorTrait;
use App\Models\Collections\LoanCollection;
use App\Models\Collections\SavingCollection;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\category\CategoryActionHistory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Category extends Model
{
    use HasFactory,
        SoftDeletes,
        HelperScopesTrait,
        BelongsToAuthorTrait;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'group',
        'description',
        'saving',
        'loan',
        'status',
        'is_default',
        'creator_id'
    ];

    /**
     * Relation with CenterActionHistory Table
     */
    public function CategoryActionHistory()
    {
        return $this->hasMany(CategoryActionHistory::class);
    }

    /**
     * Relation with Category Config Table
     */
    public function CategoryConfig()
    {
        return $this->hasOne(CategoryConfig::class);
    }

    /**
     * Relation with Saving Collection Table
     */
    public function SavingCollection()
    {
        return $this->hasMany(SavingCollection::class)->withTrashed();
    }

    /**
     * Relation with Loan Collection Table
     */
    public function LoanCollection()
    {
        return $this->hasMany(LoanCollection::class)->withTrashed();
    }

    /**
     * Regular Category report.
     */
    public function scopeCategorySavingReport($query, $isRegular = true)
    {
        $prefix = Helper::getPermissionPrefix($isRegular);
        $query->where('saving', true)
            ->active()
            ->with(
                [
                    'SavingCollection' => function ($query) use ($isRegular, $prefix) {
                        $query->select(
                            'category_id',
                            DB::raw('SUM(deposit) AS deposit')
                        );
                        $query->groupBy('category_id');
                        $query->pending();
                        $query->when($isRegular, function ($query) {
                            $query->today();
                        });
                        $query->when(!$isRegular, function ($query) {
                            $query->notToday();
                        });
                        $query->when(!Auth::user()->can("{$prefix}_saving_collection_list_view_as_admin"), function ($query) {
                            $query->createdBy();
                        });
                        $query->withoutTrashed();
                    }
                ]
            );
    }

    /**
     * Regular Category report.
     */
    public function scopeCategoryLoanReport($query, $isRegular = true)
    {
        $prefix = Helper::getPermissionPrefix($isRegular);
        $query->where('loan', true)
            ->active()
            ->with(
                [
                    'LoanCollection' => function ($query) use ($isRegular, $prefix) {
                        $query->select(
                            'category_id',
                            DB::raw('SUM(deposit) AS deposit'),
                            DB::raw('SUM(loan) AS loan'),
                            DB::raw('SUM(interest) AS interest'),
                            DB::raw('SUM(total) AS total'),
                        );
                        $query->groupBy('category_id');
                        $query->pending();
                        $query->when($isRegular, function ($query) {
                            $query->today();
                        });
                        $query->when(!$isRegular, function ($query) {
                            $query->notToday();
                        });
                        $query->when(!Auth::user()->can("{$prefix}_loan_collection_list_view_as_admin"), function ($query) {
                            $query->createdBy();
                        });
                        $query->withoutTrashed();
                    }
                ]
            );
    }
}
