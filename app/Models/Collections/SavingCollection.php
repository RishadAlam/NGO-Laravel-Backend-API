<?php

namespace App\Models\Collections;

use App\Models\User;
use App\Models\field\Field;
use App\Models\center\Center;
use App\Models\category\Category;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Http\Traits\HelperScopesTrait;
use Illuminate\Database\Eloquent\Model;
use App\Http\Traits\BelongsToFieldTrait;
use App\Http\Traits\BelongsToAuthorTrait;
use App\Http\Traits\BelongsToCenterTrait;
use App\Models\client\ClientRegistration;
use App\Http\Traits\BelongsToCategoryTrait;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Collections\SavingCollectionActionHistory;

class SavingCollection extends Model
{
    use HasFactory,
        SoftDeletes,
        HelperScopesTrait,
        BelongsToFieldTrait,
        BelongsToCenterTrait,
        BelongsToCategoryTrait,
        BelongsToAuthorTrait;

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
        'description',
        'is_approved'
    ];

    /**
     * Relationship belongs to User model.
     *
     * @return response()
     */
    public function Author()
    {
        return $this->belongsTo(User::class, 'creator_id', 'id')->withTrashed();
    }

    /**
     * Relationship belongs to ClientRegistration model.
     *
     * @return response()
     */
    public function ClientRegistration()
    {
        return $this->belongsTo(ClientRegistration::class)->withTrashed();
    }

    /**
     * Relationship belongs to Field model.
     *
     * @return response()
     */
    public function Field()
    {
        return $this->belongsTo(Field::class)->withTrashed();
    }

    /**
     * Relationship belongs to Center model.
     *
     * @return response()
     */
    public function Center()
    {
        return $this->belongsTo(Center::class)->withTrashed();
    }

    /**
     * Relationship belongs to Category model.
     *
     * @return response()
     */
    public function Category()
    {
        return $this->belongsTo(Category::class)->withTrashed();
    }

    /**
     * Relation with SavingCollectionActionHistory Table.
     */
    public function SavingCollectionActionHistory()
    {
        return $this->hasMany(SavingCollectionActionHistory::class);
    }

    /**
     * Regular Field report.
     */
    public function scopeRegularFieldReport($query, $category_id)
    {
        return $query->active()
            ->with(
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
     * Regular Collection Sheet.
     */
    public function scopeRegularCollectionSheet($query, $category_id, $field_id)
    {
        return $query->active()
            ->fieldID($field_id)
            ->with(
                [
                    'SavingAccount' => function ($query) use ($category_id) {
                        $query->select(
                            'id',
                            'center_id',
                            'client_id',
                            'acc_no',
                            'deposit'
                        );
                        $query->ClientRegistration('id', 'name', 'image_uri');
                        $query->with([
                            'SavingCollection' => function ($query) use ($category_id) {
                                $query->author('id', 'name');
                                $query->select('id', 'saving_account_id', 'deposit', 'description', 'creator_id', 'created_at');
                                $query->pending();
                                $query->today();
                                $query->categoryID($category_id);
                                $query->filter();
                                $query->permission();
                            }
                        ]);
                    }
                ]
            );
    }


    /**
     * Filter Scope
     */
    public function scopeFilter($query)
    {
        $query->when(request('user_id'), function ($query) {
            $query->createdBy(request('user_id'));
        })
            ->when(request('field_id'), function ($query) {
                $query->fieldID(request('field_id'));
            })
            ->when(request('center_id'), function ($query) {
                $query->centerID(request('center_id'));
            })
            ->when(request('category_id'), function ($query) {
                $query->categoryID(request('category_id'));
            });
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
