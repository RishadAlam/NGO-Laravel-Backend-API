<?php

namespace App\Http\Controllers\dashboard;

use Carbon\Carbon;
use App\Models\User;
use App\Models\client\LoanAccount;
use App\Models\category\Category;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Collections\LoanCollection;
use App\Models\Withdrawal\SavingWithdrawal;
use App\Models\Collections\SavingCollection;
use App\Models\Withdrawal\LoanSavingWithdrawal;

class DashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return create_response(null, [
            'loan_distributions'            => LoanAccount::loanDistributionSummery(),
            'loan_collections_summery'      => LoanCollection::loanCollectionSummery(),
            'loan_saving_collections'       => LoanCollection::loanSavingCollectionSummery(),
            'monthly_loan_collections'      => LoanCollection::monthlyLoanCollectionSummery(),
            'loan_collections_sources'      => LoanCollection::currentDayLoanCollectionSources(),
            'loan_collections'              => LoanCollection::currentDayLoanCollection(),
            'loan_saving_withdrawal'        => LoanSavingWithdrawal::currentDaySavingWithdrawal(),
            'saving_collections_summery'    => SavingCollection::savingCollectionSummery(),
            'dps_collections'               => SavingCollection::CurrentMonthDpsCollectionSummery(),
            'saving_collections_sources'    => SavingCollection::currentDaySavingCollectionSources(),
            'saving_collections'            => SavingCollection::currentDaySavingCollection(),
            'saving_withdrawal'             => SavingWithdrawal::currentDaySavingWithdrawal(),
            'top_collectionist'             => User::currentDayTopCollectionist(),
        ]);
    }

    /**
     * View all loan given (approved loans) for current month.
     */
    public function loanGivenViewAll()
    {
        $monthStart = Carbon::now()->startOfMonth()->format('Y-m-d');
        $monthEnd   = Carbon::now()->endOfMonth()->format('Y-m-d');

        $loans = LoanAccount::approve('is_loan_approved')
            ->whereBetween('is_loan_approved_at', [$monthStart, $monthEnd])
            ->clientRegistration('id', 'name', 'image_uri', 'primary_phone')
            ->field('id', 'name')
            ->center('id', 'name')
            ->category('id', 'name', 'is_default')
            ->author('id', 'name')
            ->approver('id', 'name')
            ->loanApprover('id', 'name')
            ->when(!Auth::user()->can('view_dashboard_as_admin'), fn($q) => $q->createdBy(Auth::user()->id))
            ->orderedBy('id', 'DESC')
            ->get();

        return create_response(null, $loans);
    }

    /**
     * View all loan recovered collections (non-monthly-loan category) for current month.
     */
    public function loanRecoveredViewAll()
    {
        $monthStart     = Carbon::now()->startOfMonth()->startOfDay();
        $monthEnd       = Carbon::now()->endOfMonth()->endOfDay();
        $monthlyLoanId  = Category::whereName('monthly_loan')->value('id');

        $collections = LoanCollection::approve()
            ->when(!is_null($monthlyLoanId), fn($q) => $q->where('category_id', '!=', $monthlyLoanId))
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->clientRegistration('id', 'name', 'image_uri')
            ->category('id', 'name', 'is_default')
            ->field('id', 'name')
            ->center('id', 'name')
            ->account('id', 'name', 'is_default')
            ->author('id', 'name', 'image_uri')
            ->when(!Auth::user()->can('view_dashboard_as_admin'), fn($q) => $q->createdBy(Auth::user()->id))
            ->latest()
            ->get(['id', 'field_id', 'center_id', 'category_id', 'client_registration_id', 'account_id', 'creator_id', 'acc_no', 'installment', 'deposit', 'loan', 'interest', 'total', 'description', 'created_at']);

        return create_response(null, $collections);
    }

    /**
     * View all loan saving collections (deposit portion of loan collections) for current month.
     */
    public function loanSavingViewAll()
    {
        $monthStart = Carbon::now()->startOfMonth()->startOfDay();
        $monthEnd   = Carbon::now()->endOfMonth()->endOfDay();

        $collections = LoanCollection::approve()
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->clientRegistration('id', 'name', 'image_uri')
            ->category('id', 'name', 'is_default')
            ->field('id', 'name')
            ->center('id', 'name')
            ->account('id', 'name', 'is_default')
            ->author('id', 'name', 'image_uri')
            ->when(!Auth::user()->can('view_dashboard_as_admin'), fn($q) => $q->createdBy(Auth::user()->id))
            ->latest()
            ->get(['id', 'field_id', 'center_id', 'category_id', 'client_registration_id', 'account_id', 'creator_id', 'acc_no', 'installment', 'deposit', 'loan', 'interest', 'total', 'description', 'created_at']);

        return create_response(null, $collections);
    }

    /**
     * View all monthly loan collections for current month.
     */
    public function monthlyLoanViewAll()
    {
        $monthStart         = Carbon::now()->startOfMonth()->startOfDay();
        $monthEnd           = Carbon::now()->endOfMonth()->endOfDay();
        $monthlyLoanId      = Category::whereName('monthly_loan')->value('id');

        $collections = LoanCollection::approve()
            ->when(!is_null($monthlyLoanId), fn($q) => $q->where('category_id', $monthlyLoanId))
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->clientRegistration('id', 'name', 'image_uri')
            ->category('id', 'name', 'is_default')
            ->field('id', 'name')
            ->center('id', 'name')
            ->account('id', 'name', 'is_default')
            ->author('id', 'name', 'image_uri')
            ->when(!Auth::user()->can('view_dashboard_as_admin'), fn($q) => $q->createdBy(Auth::user()->id))
            ->latest()
            ->get(['id', 'field_id', 'center_id', 'category_id', 'client_registration_id', 'account_id', 'creator_id', 'acc_no', 'installment', 'deposit', 'loan', 'interest', 'total', 'description', 'created_at']);

        return create_response(null, $collections);
    }

    /**
     * View all saving collections (non-DPS) for current month.
     */
    public function savingCollectionsViewAll()
    {
        $monthStart = Carbon::now()->startOfMonth()->startOfDay();
        $monthEnd   = Carbon::now()->endOfMonth()->endOfDay();
        $dpsId      = Category::whereName('dps')->value('id');

        $collections = SavingCollection::approve()
            ->when(!is_null($dpsId), fn($q) => $q->where('category_id', '!=', $dpsId))
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->clientRegistration('id', 'name', 'image_uri')
            ->category('id', 'name', 'is_default')
            ->field('id', 'name')
            ->center('id', 'name')
            ->account('id', 'name', 'is_default')
            ->author('id', 'name', 'image_uri')
            ->when(!Auth::user()->can('view_dashboard_as_admin'), fn($q) => $q->createdBy(Auth::user()->id))
            ->latest()
            ->get(['id', 'field_id', 'center_id', 'category_id', 'client_registration_id', 'account_id', 'creator_id', 'acc_no', 'installment', 'deposit', 'description', 'created_at']);

        return create_response(null, $collections);
    }

    /**
     * View all DPS collections for current month.
     */
    public function dpsCollectionsViewAll()
    {
        $monthStart = Carbon::now()->startOfMonth()->startOfDay();
        $monthEnd   = Carbon::now()->endOfMonth()->endOfDay();
        $dpsId      = Category::whereName('dps')->value('id');

        $collections = SavingCollection::approve()
            ->when(!is_null($dpsId), fn($q) => $q->where('category_id', $dpsId))
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->clientRegistration('id', 'name', 'image_uri')
            ->category('id', 'name', 'is_default')
            ->field('id', 'name')
            ->center('id', 'name')
            ->account('id', 'name', 'is_default')
            ->author('id', 'name', 'image_uri')
            ->when(!Auth::user()->can('view_dashboard_as_admin'), fn($q) => $q->createdBy(Auth::user()->id))
            ->latest()
            ->get(['id', 'field_id', 'center_id', 'category_id', 'client_registration_id', 'account_id', 'creator_id', 'acc_no', 'installment', 'deposit', 'description', 'created_at']);

        return create_response(null, $collections);
    }
}
