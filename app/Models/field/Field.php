<?php

namespace App\Models\field;

use App\Models\User;
use App\Helpers\Helper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Http\Traits\HelperScopesTrait;
use Illuminate\Database\Eloquent\Model;
use App\Models\field\FieldActionHistory;
use App\Http\Traits\BelongsToAuthorTrait;
use App\Models\Collections\LoanCollection;
use App\Models\Collections\SavingCollection;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Field extends Model
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
        'description',
        'status',
        'creator_id'
    ];

    /**
     * Relation with FieldActionHistory Table
     */
    public function FieldActionHistory()
    {
        return $this->hasMany(FieldActionHistory::class);
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
     * Regular Field report.
     */
    public function scopeFieldSavingReport($query, $category_id, $isRegular = true)
    {
        $prefix = Helper::getPermissionPrefix($isRegular);
        $query->active()
            ->with(
                [
                    'SavingCollection' => function ($query) use ($category_id, $isRegular, $prefix) {
                        $query->select(
                            'field_id',
                            DB::raw('SUM(deposit) AS deposit')
                        );
                        $query->groupBy('field_id');
                        $query->categoryID($category_id);
                        $query->pending();
                        $query->when($isRegular, function ($query) {
                            $query->today();
                        });
                        $query->when(!Auth::user()->can("{$prefix}_saving_collection_list_view_as_admin"), function ($query) {
                            $query->createdBy();
                        });
                    }
                ]
            );
    }

    /**
     * Regular Field report.
     */
    public function scopeFieldLoanReport($query, $category_id, $isRegular = true)
    {
        $prefix = Helper::getPermissionPrefix($isRegular);
        $query->with(
            [
                'LoanCollection' => function ($query) use ($category_id, $isRegular, $prefix) {
                    $query->select(
                        'field_id',
                        DB::raw('SUM(deposit) AS deposit'),
                        DB::raw('SUM(loan) AS loan'),
                        DB::raw('SUM(interest) AS interest'),
                        DB::raw('SUM(total) AS total'),
                    );
                    $query->groupBy('field_id');
                    $query->categoryID($category_id);
                    $query->pending();
                    $query->when($isRegular, function ($query) {
                        $query->today();
                    });
                    $query->when(!Auth::user()->can("{$prefix}_loan_collection_list_view_as_admin"), function ($query) {
                        $query->createdBy();
                    });
                }
            ]
        );
    }
}
