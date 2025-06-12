<?php

namespace App\Models\Collections;

use Carbon\Carbon;
use App\Models\User;
use App\Models\field\Field;
use App\Models\center\Center;
use App\Models\category\Category;
use Illuminate\Support\Facades\DB;
use App\Models\client\SavingAccount;
use Illuminate\Support\Facades\Auth;
use App\Http\Traits\HelperScopesTrait;
use Illuminate\Database\Eloquent\Model;
use App\Http\Traits\BelongsToFieldTrait;
use App\Http\Traits\BelongsToAuthorTrait;
use App\Http\Traits\BelongsToCenterTrait;
use App\Models\client\ClientRegistration;
use App\Http\Traits\BelongsToAccountTrait;
use App\Http\Traits\BelongsToApproverTrait;
use App\Http\Traits\BelongsToCategoryTrait;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Http\Traits\BelongsToSavingAccountTrait;
use App\Http\Traits\BelongsToClientRegistrationTrait;
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
        BelongsToAuthorTrait,
        BelongsToAccountTrait,
        BelongsToClientRegistrationTrait,
        BelongsToSavingAccountTrait,
        BelongsToApproverTrait;

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
        'account_id',
        'creator_id',
        'approved_by',
        'acc_no',
        'installment',
        'deposit',
        'description',
        'is_approved'
    ];

    /**
     * Relation with SavingCollectionActionHistory Table.
     */
    public function SavingCollectionActionHistory()
    {
        return $this->hasMany(SavingCollectionActionHistory::class);
    }

    /**
     * Current Month Saving Collection summary
     */
    public static function savingCollectionSummery()
    {
        $currentDate    = [Carbon::now()->startOfMonth()->startOfDay(), Carbon::now()->endOfMonth()->endOfDay()];
        $lastMonthDate  = [Carbon::now()->subMonths()->startOfMonth()->startOfDay(), Carbon::now()->subMonths()->endOfMonth()->endOfDay()];

        $LMTSavingCollection  = static::approve()->where('category_id', '!=', Category::whereName('dps')->value('id'))->whereBetween('created_at', $lastMonthDate)->sum('deposit');
        $CMTSavingCollSummary = static::approve()->where('category_id', '!=', Category::whereName('dps')->value('id'))->whereBetween('created_at', $currentDate)->groupBy('created_at')->selectRaw('SUM(deposit) as amount, created_at as date')->get();
        $CMTSavingCollection  = !empty($CMTSavingCollSummary) ? $CMTSavingCollSummary->sum('amount') : 0;

        return [
            'last_amount'       => $LMTSavingCollection,
            'current_amount'    => $CMTSavingCollection,
            'data'              => $CMTSavingCollSummary,
            'cmp_amount'        => ceil((($CMTSavingCollection - $LMTSavingCollection) / ($LMTSavingCollection != 0 ? $LMTSavingCollection : ($CMTSavingCollection != 0 ? $CMTSavingCollection : 1))) * 100)
        ];
    }

    /**
     * Current Month DPS Collection summary
     */
    public static function CurrentMonthDpsCollectionSummery()
    {
        $currentDate    = [Carbon::now()->startOfMonth()->startOfDay(), Carbon::now()->endOfMonth()->endOfDay()];
        $lastMonthDate  = [Carbon::now()->subMonths()->startOfMonth()->startOfDay(), Carbon::now()->subMonths()->endOfMonth()->endOfDay()];

        $LMTSavingCollection  = SavingCollection::approve()->where('category_id', Category::whereName('dps')->value('id'))->whereBetween('created_at', $lastMonthDate)->sum('deposit');
        $CMTSavingCollSummary = SavingCollection::approve()->where('category_id', Category::whereName('dps')->value('id'))->whereBetween('created_at', $currentDate)->groupBy('created_at')->selectRaw('SUM(deposit) as amount, created_at as date')->get();
        $CMTSavingCollection  = !empty($CMTSavingCollSummary) ? $CMTSavingCollSummary->sum('amount') : 0;

        return  [
            'last_amount'       => $LMTSavingCollection,
            'current_amount'    => $CMTSavingCollection,
            'data'              => $CMTSavingCollSummary,
            'cmp_amount'        => ceil((($CMTSavingCollection - $LMTSavingCollection) / ($LMTSavingCollection != 0 ? $LMTSavingCollection : ($CMTSavingCollection != 0 ? $CMTSavingCollection : 1))) * 100)
        ];
    }

    /**
     * Today Collection sources
     */
    public static function currentDaySavingCollectionSources()
    {
        $sources = static::with('Category:id,name,is_default')
            ->today()
            ->groupBy('category_id')
            ->selectRaw('SUM(deposit) as amount, category_id')->get();

        return $sources->map(function ($source) {
            return (object)[
                'name'          => $source->Category->name,
                'is_default'    => $source->Category->is_default,
                'amount'        => $source->amount
            ];
        });
    }

    /**
     * Today Collection
     */
    public static function currentDaySavingCollection()
    {
        return static::today()
            ->clientRegistration('id', 'name', 'image_uri')
            ->category('id', 'name', 'is_default')
            ->field('id', 'name')
            ->center('id', 'name')
            ->account('id', 'name', 'is_default')
            ->author('id', 'name', 'image_uri')
            ->when(!Auth::user()->can('view_dashboard_as_admin'), function ($query) {
                $query->CreatedBy(Auth::user()->id);
            })
            ->latest()
            ->get(['id', 'field_id', 'center_id', 'category_id', 'client_registration_id', 'account_id', 'creator_id', 'acc_no', 'installment', 'deposit', 'description', 'created_at']);
    }

    /**
     * Regular Collection Sheet.
     */
    public function scopeRegularCollectionSheet($query, $category_id, $field_id)
    {
        $query->active()
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
                        $query->whereHas('ClientRegistration', function ($q) {
                            $q->approve();
                            $q->whereNull('deleted_at');
                        })->with(['ClientRegistration' => function ($q) {
                            $q->approve();
                            $q->whereNull('deleted_at');
                            $q->select('id', 'name', 'image_uri');
                        }]);
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
     * Permission
     */
    public function scopePermission($query)
    {
        $query->when(!Auth::user()->can('pending_saving_acc_list_view_as_admin'), function ($query) {
            $query->createdBy();
        });
    }
}
