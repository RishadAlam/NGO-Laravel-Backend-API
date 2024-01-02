<?php

namespace App\Models\category;

use App\Models\User;
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
        return $this->hasMany(CategoryConfig::class)->withTrashed();
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
    public function scopeRegularCategorySavingReport($query)
    {
        $query->where('saving', true)
            ->active()
            ->with(
                [
                    'SavingCollection' => function ($query) {
                        $query->select(
                            'category_id',
                            DB::raw('SUM(deposit) AS deposit')
                        );
                        $query->groupBy('category_id');
                        $query->pending();
                        $query->today();
                        $query->when(!Auth::user()->can('regular_saving_collection_list_view_as_admin'), function ($query) {
                            $query->createdBy();
                        });
                    }
                ]
            );
    }

    /**
     * Regular Category report.
     */
    public function scopeRegularCategoryLoanReport($query)
    {
        $query->where('loan', true)
            ->active()
            ->with(
                [
                    'LoanCollection' => function ($query) {
                        $query->select(
                            'category_id',
                            DB::raw('SUM(deposit) AS deposit'),
                            DB::raw('SUM(loan) AS loan'),
                            DB::raw('SUM(interest) AS interest'),
                            DB::raw('SUM(total) AS total'),
                        );
                        $query->groupBy('category_id');
                        $query->pending();
                        $query->today();
                        $query->when(!Auth::user()->can('regular_loan_collection_list_view_as_admin'), function ($query) {
                            $query->createdBy();
                        });
                    }
                ]
            );
    }
}
