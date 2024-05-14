<?php

namespace App\Http\Controllers\dashboard;

use App\Models\User;
use Illuminate\Http\Request;
use App\Models\client\LoanAccount;
use App\Http\Controllers\Controller;
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
}
