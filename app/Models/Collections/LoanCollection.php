<?php

namespace App\Models\Collections;

use Carbon\Carbon;
use App\Models\category\Category;
use Illuminate\Support\Facades\Auth;
use App\Http\Traits\HelperScopesTrait;
use Illuminate\Database\Eloquent\Model;
use App\Http\Traits\BelongsToFieldTrait;
use App\Http\Traits\BelongsToAuthorTrait;
use App\Http\Traits\BelongsToCenterTrait;
use App\Models\client\ClientRegistration;
use App\Http\Traits\BelongsToAccountTrait;
use App\Http\Traits\BelongsToCategoryTrait;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Http\Traits\BelongsToLoanAccountTrait;
use App\Http\Traits\BelongsToClientRegistrationTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Collections\LoanCollectionActionHistory;

class LoanCollection extends Model
{
    use HasFactory,
        SoftDeletes,
        HelperScopesTrait,
        BelongsToFieldTrait,
        BelongsToCenterTrait,
        BelongsToCategoryTrait,
        BelongsToAuthorTrait,
        BelongsToAccountTrait,
        BelongsToLoanAccountTrait,
        BelongsToClientRegistrationTrait;

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
        'loan_account_id',
        'account_id',
        'creator_id',
        'approved_by',
        'acc_no',
        'installment',
        'deposit',
        'loan',
        'interest',
        'total',
        'description',
        'is_approved'
    ];

    /**
     * Relation with LoanCollectionActionHistory Table.
     */
    public function LoanCollectionActionHistory()
    {
        return $this->hasMany(LoanCollectionActionHistory::class);
    }

    /**
     * Current Month Loan Collection summary
     */
    public static function loanCollectionSummery()
    {
        $currentDate    = [Carbon::now()->startOfMonth()->startOfDay(), Carbon::now()->endOfMonth()->endOfDay()];
        $lastMonthDate  = [Carbon::now()->subMonths()->startOfMonth()->startOfDay(), Carbon::now()->subMonths()->endOfMonth()->endOfDay()];

        $LMTLoanCollection  = static::approve()->where('category_id', '!=', Category::whereName('monthly_loan')->value('id'))->whereBetween('created_at', $lastMonthDate)->sum('loan');
        $CMTLoanCollSummary = static::approve()->where('category_id', '!=', Category::whereName('monthly_loan')->value('id'))->whereBetween('created_at', $currentDate)->groupBy('created_at')->selectRaw('SUM(loan) as amount, created_at as date')->get();
        $CMTLoanCollection  = !empty($CMTLoanCollSummary) ? $CMTLoanCollSummary->sum('amount') : 0;

        return [
            'last_amount'       => $LMTLoanCollection,
            'current_amount'    => $CMTLoanCollection,
            'data'              => $CMTLoanCollSummary,
            'cmp_amount'        => ceil((($CMTLoanCollection - $LMTLoanCollection) / ($LMTLoanCollection != 0 ? $LMTLoanCollection : ($CMTLoanCollection != 0 ? $CMTLoanCollection : 1))) * 100)
        ];
    }

    /**
     * Current Month Loan Saving Collection summary
     */
    public static function loanSavingCollectionSummery()
    {
        $currentDate    = [Carbon::now()->startOfMonth()->startOfDay(), Carbon::now()->endOfMonth()->endOfDay()];
        $lastMonthDate  = [Carbon::now()->subMonths()->startOfMonth()->startOfDay(), Carbon::now()->subMonths()->endOfMonth()->endOfDay()];

        $LMTSavingCollection  = static::approve()->whereBetween('created_at', $lastMonthDate)->sum('deposit');
        $CMTSavingCollSummary = static::approve()->whereBetween('created_at', $currentDate)->groupBy('created_at')->selectRaw('SUM(deposit) as amount, created_at as date')->get();
        $CMTSavingCollection  = !empty($CMTSavingCollSummary) ? $CMTSavingCollSummary->sum('amount') : 0;

        return  [
            'last_amount'       => $LMTSavingCollection,
            'current_amount'    => $CMTSavingCollection,
            'data'              => $CMTSavingCollSummary,
            'cmp_amount'        => ceil((($CMTSavingCollection - $LMTSavingCollection) / ($LMTSavingCollection != 0 ? $LMTSavingCollection : ($CMTSavingCollection != 0 ? $CMTSavingCollection : 1))) * 100)
        ];
    }

    /**
     * Current Month Monthly Loan Collection summary
     */
    public static function monthlyLoanCollectionSummery()
    {
        $currentDate    = [Carbon::now()->startOfMonth()->startOfDay(), Carbon::now()->endOfMonth()->endOfDay()];
        $lastMonthDate  = [Carbon::now()->subMonths()->startOfMonth()->startOfDay(), Carbon::now()->subMonths()->endOfMonth()->endOfDay()];

        $LMTLoanCollection  = static::approve()->where('category_id', Category::whereName('monthly_loan')->value('id'))->whereBetween('created_at', $lastMonthDate)->sum('loan');
        $CMTLoanCollSummary = static::approve()->where('category_id', Category::whereName('monthly_loan')->value('id'))->whereBetween('created_at', $currentDate)->groupBy('created_at')->selectRaw('SUM(loan) as amount, created_at as date')->get();
        $CMTLoanCollection  = !empty($CMTLoanCollSummary) ? $CMTLoanCollSummary->sum('amount') : 0;

        return [
            'last_amount'       => $LMTLoanCollection,
            'current_amount'    => $CMTLoanCollection,
            'data'              => $CMTLoanCollSummary,
            'cmp_amount'        => ceil((($CMTLoanCollection - $LMTLoanCollection) / ($LMTLoanCollection != 0 ? $LMTLoanCollection : ($CMTLoanCollection != 0 ? $CMTLoanCollection : 1))) * 100)
        ];
    }

    /**
     * Today Collection sources
     */
    public static function currentDayLoanCollectionSources()
    {
        $sources = static::with('Category:id,name,is_default')
            ->today()
            ->groupBy('category_id')
            ->selectRaw('SUM(total) as amount, category_id')->get();

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
    public static function currentDayLoanCollection()
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
            ->get(['id', 'field_id', 'center_id', 'category_id', 'client_registration_id', 'account_id', 'creator_id', 'acc_no', 'installment', 'deposit', 'loan', 'interest', 'total', 'description', 'created_at']);
    }
}
