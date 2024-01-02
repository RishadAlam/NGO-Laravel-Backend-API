<?php

namespace App\Models\field;

use App\Models\User;
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
    public function scopeRegularFieldReport($query, $category_id)
    {
        return $query->with(
            [
                'SavingCollection' => function ($query) use ($category_id) {
                    $query->select(
                        'field_id',
                        DB::raw('SUM(deposit) AS deposit')
                    );
                    $query->groupBy('field_id');
                    $query->categoryID($category_id);
                    $query->pending();
                    $query->today();
                    $query->permission();
                }
            ]
        );
    }

    /**
     * Permission
     */
    public function scopePermission($query)
    {
        $query->when(!Auth::user()->can('pending_saving_acc_list_view_as_admin'), function ($query) {
            $query->createdBy();
        });
    }
}
