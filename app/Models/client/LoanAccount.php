<?php

namespace App\Models\client;

use Carbon\Carbon;
use App\Models\User;
use App\Models\field\Field;
use App\Models\center\Center;
use App\Models\client\Guarantor;
use App\Models\category\Category;
use Illuminate\Support\Facades\Auth;
use App\Http\Traits\HelperScopesTrait;
use Illuminate\Database\Eloquent\Model;
use App\Http\Traits\BelongsToFieldTrait;
use App\Http\Traits\BelongsToAuthorTrait;
use App\Http\Traits\BelongsToCenterTrait;
use App\Models\client\ClientRegistration;
use App\Models\Collections\LoanCollection;
use App\Http\Traits\BelongsToApproverTrait;
use App\Http\Traits\BelongsToCategoryTrait;
use App\Models\client\GuarantorRegistration;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\client\LoanAccountActionHistory;
use App\Models\Withdrawal\LoanSavingWithdrawal;
use App\Http\Traits\BelongsToClientRegistrationTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LoanAccount extends Model
{
    use HasFactory,
        SoftDeletes,
        HelperScopesTrait,
        BelongsToFieldTrait,
        BelongsToCenterTrait,
        BelongsToCategoryTrait,
        BelongsToAuthorTrait,
        BelongsToClientRegistrationTrait,
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
        'acc_no',
        'start_date',
        'duration_date',
        'loan_given',
        'payable_deposit',
        'payable_installment',
        'payable_interest',
        'total_payable_interest',
        'total_payable_loan_with_interest',
        'loan_installment',
        'interest_installment',
        'total_rec_installment',
        'total_deposited',
        'total_withdrawn',
        'total_loan_rec',
        'total_interest_rec',
        'closing_balance',
        'description',
        'status',
        'is_approved',
        'is_loan_approved',
        'loan_approved_by',
        'approved_by',
        'approved_at',
        'is_loan_approved_at',
        'creator_id',
    ];

    /**
     * Relation with LoanAccountActionHistory Table
     */
    public function LoanAccountActionHistory()
    {
        return $this->hasMany(LoanAccountActionHistory::class);
    }

    /**
     * Relation with Loan Collection Table
     */
    public function LoanCollection()
    {
        return $this->hasMany(LoanCollection::class)->withTrashed();
    }

    /**
     * Relation with Loan Saving Withdrawal Table
     */
    public function LoanSavingWithdrawal()
    {
        return $this->hasMany(LoanSavingWithdrawal::class);
    }

    /**
     * Relationship belongs to ClientRegistration model
     *
     * @return response()
     */
    public function ClientRegistration()
    {
        return $this->belongsTo(ClientRegistration::class)->withTrashed();
    }

    /**
     * Relationship belongs to User model
     *
     * @return response()
     */
    public function LoanApprover()
    {
        return $this->belongsTo(User::class, 'loan_approved_by', 'id')->withTrashed();
    }

    /**
     * Relation with Guarantor Table
     */
    public function Guarantors()
    {
        return $this->hasMany(Guarantor::class);
    }

    /**
     * Current Month Loan Distribution summary
     */
    public static function loanDistributionSummery()
    {
        $currentDate    = [Carbon::now()->startOfMonth()->format('Y-m-d'), Carbon::now()->endOfMonth()->format('Y-m-d')];
        $lastMonthData  = [Carbon::now()->subMonths()->startOfMonth()->format('Y-m-d'), Carbon::now()->subMonths()->endOfMonth()->format('Y-m-d')];

        $LMTLoanDistribute  = static::approve('is_loan_approved')->whereBetween('start_date', $lastMonthData)->sum('loan_given');
        $CMTLoanDistSummary = static::approve('is_loan_approved')->whereBetween('start_date', $currentDate)->groupBy('start_date')->selectRaw('SUM(loan_given) as amount, start_date as date')->get();
        $CMTLoanDistribute  = !empty($CMTLoanDistSummary) ? $CMTLoanDistSummary->sum('amount') : 0;

        return  [
            'last_amount'       => $LMTLoanDistribute,
            'current_amount'    => $CMTLoanDistribute,
            'data'              => $CMTLoanDistSummary,
            'cmp_amount'        => ceil((($CMTLoanDistribute - $LMTLoanDistribute) / ($LMTLoanDistribute != 0 ? $LMTLoanDistribute : ($CMTLoanDistribute != 0 ? $CMTLoanDistribute : 1))) * 100)
        ];
    }

    /**
     * Guarantor Relation Scope
     */
    public function scopeGuarantors($query, ...$arg)
    {
        return $query->with("Guarantors", function ($query) use ($arg) {
            $query->select(...$arg);
        });
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
                $query->CenterID(request('center_id'));
            })
            ->when(request('category_id'), function ($query) {
                $query->CategoryID(request('category_id'));
            });
    }

    /**
     * Pending Saving Registration Forms Scope.
     */
    public function scopeFetchPendingForms($query)
    {
        return $query->Field('id', 'name')
            ->Center('id', 'name')
            ->Category('id', 'name', 'is_default')
            ->Author('id', 'name')
            ->ClientRegistration('id', 'acc_no', 'name', 'image_uri')
            ->Guarantors('id', 'loan_account_id', 'name', 'father_name', 'husband_name', 'mother_name', 'nid', 'dob', 'occupation', 'relation', 'gender', 'primary_phone', 'secondary_phone', 'image', 'image_uri', 'signature', 'signature_uri', 'address')
            ->where('is_approved', false)
            ->when(!Auth::user()->can('pending_loan_acc_list_view_as_admin'), function ($query) {
                $query->createdBy();
            })
            ->filter()
            ->orderedBy();
    }

    /**
     * Pending Saving Registration Forms Scope.
     */
    public function scopeFetchPendingLoans($query, $month, $year)
    {
        return $query->Field('id', 'name')
            ->Center('id', 'name')
            ->Category('id', 'name', 'is_default')
            ->Author('id', 'name')
            ->ClientRegistration('id', 'name', 'image_uri')
            ->where('is_approved', true)
            ->when(!Auth::user()->can('pending_loan_view_as_admin'), function ($query) {
                $query->createdBy();
            })
            ->whereMonth('start_date', $month)
            ->whereYear('start_date', $year)
            ->filter()
            ->orderedBy()
            ->select(
                'id',
                'acc_no',
                'field_id',
                'center_id',
                'category_id',
                'client_registration_id',
                'creator_id',
                'start_date',
                'duration_date',
                'loan_given',
                'payable_deposit',
                'payable_installment',
                'payable_interest',
                'total_payable_interest',
                'total_payable_loan_with_interest',
                'loan_installment',
                'interest_installment',
                'is_loan_approved',
                'created_at'
            );
    }

    /**
     * Get Specific resource
     */
    public function scopeActiveLoan($query, $id)
    {
        $query->ClientRegistrationID($id)
            ->approve()
            ->active()
            ->field('id', 'name')
            ->center('id', 'name')
            ->clientRegistration('id', 'acc_no', 'name', 'image_uri')
            ->Category('id', 'name', 'is_default')
            ->Author('id', 'name')
            ->Approver('id', 'name')
            ->loanApprover('id', 'name');
    }

    /**
     * Get Specific resource
     */
    public function scopePendingLoan($query, $id)
    {
        $query->ClientRegistrationID($id)
            ->pending()
            ->Category('id', 'name', 'is_default')
            ->Author('id', 'name')
            ->Approver('id', 'name');
    }

    /**
     * Get Specific resource
     */
    public function scopeHoldLoan($query, $id)
    {
        $query->ClientRegistrationID($id)
            ->approve()
            ->hold()
            ->Category('id', 'name', 'is_default')
            ->Author('id', 'name')
            ->Approver('id', 'name')
            ->loanApprover('id', 'name');
    }
    /**
     * Get Specific resource
     */
    public function scopeClosedLoan($query, $id)
    {
        $query->ClientRegistrationID($id)
            ->approve()
            ->closed()
            ->Category('id', 'name', 'is_default')
            ->Author('id', 'name')
            ->Approver('id', 'name')
            ->loanApprover('id', 'name');
    }
}
