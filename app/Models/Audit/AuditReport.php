<?php

namespace App\Models\Audit;

use Carbon\Carbon;
use App\Models\accounts\Income;
use App\Models\accounts\Expense;
use App\Models\client\LoanAccount;
use Illuminate\Support\Facades\Log;
use App\Models\accounts\IncomeCategory;
use Illuminate\Database\Eloquent\Model;
use App\Models\accounts\ExpenseCategory;
use App\Models\client\ClientRegistration;
use App\Models\Collections\LoanCollection;
use App\Models\Withdrawal\SavingWithdrawal;
use App\Models\Collections\SavingCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Collection as SupportCollection;

class AuditReport extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'last_updated_by',
        'financial_year',
        'meta_value',
        'data',
    ];

    /**
     * Mutator for action Details json Data
     */
    public function setDataAttribute($value)
    {
        $this->attributes['data'] = json_encode($value);
    }

    /**
     * accessor for action Details json Data
     */
    public function getDataAttribute($value)
    {
        return json_decode($value);
    }

    /**
     * Create Audit Report
     *
     * @param string $startYear
     * @param string $endYear
     * @return void
     */
    public static function createReport(string $startYear, string $endYear)
    {
        if (!empty(AuditReport::where('financial_year', "{$startYear}-{$endYear}")->count())) {
            Log::error("Audit Report already Exists!");
            return create_response('Audit Report already Exists!', null, 401, false);
        }

        $startDate  = Carbon::createFromDate($startYear, 7, 1)->startOfDay();
        $endDate    = Carbon::createFromDate($endYear, 6, 30)->endOfDay();

        $incomeReport       = static::getIncomeReport($startDate, $endDate);
        $expenseReport      = static::getExpenseReport($startDate, $endDate);

        $totals = static::calculateTotals($incomeReport, $expenseReport, ($startYear - 1) . '-' . $startYear, $startDate, $endDate);

        $collectionMeta     = static::getCollectionMeta($totals);
        $distributionMeta   = static::getDistributionMeta($totals);
        $capitalMeta        = static::getCapitalMeta($totals);
        $resourceMeta       = static::getResourceMeta($totals);

        $report = static::constructReport($collectionMeta, $distributionMeta, $incomeReport, $expenseReport, $capitalMeta, $resourceMeta, $totals);

        AuditReport::create(['financial_year' => "{$startYear}-{$endYear}", 'data' => $report]);
        return create_response('Audit Report Created successfully.');
    }

    /**
     * Get Collection Meta
     */
    private static function getCollectionMeta(array $totals)
    {
        return collect([
            (object) ['key' => 'collection_of_shares', 'value' => $totals['shareCollections'], 'is_default' => true],
            (object) ['key' => 'collection_of_savings_deposits', 'value' => $totals['savingCollections'], 'is_default' => true],
            (object) ['key' => 'collection_of_loans', 'value' => $totals['loanCollections'], 'is_default' => true],
            (object) ['key' => 'collection_of_loan_interests', 'value' => $totals['loanIntCollections'], 'is_default' => true],
            (object) ['key' => 'collection_of_fixed_deposits', 'value' => $totals['FDRCollection'], 'is_default' => true]
        ]);
    }

    /**
     * Get Distribution Meta
     */
    private static function getDistributionMeta(array $totals)
    {
        return collect([
            (object) ['key' => 'shares_return', 'value' => $totals['sharesReturn'], 'is_default' => true],
            (object) ['key' => 'savings_return', 'value' => $totals['savingReturn'], 'is_default' => true],
            (object) ['key' => 'loan_given', 'value' => $totals['loanDistribute'], 'is_default' => true]
        ]);
    }

    /**
     * Get Income Report
     */
    private static function getIncomeReport(string $startDate, string $endDate)
    {
        return Income::with('IncomeCategory:id,name,is_default')
            ->whereBetween('date', [$startDate, $endDate])
            ->groupBy('income_category_id')
            ->selectRaw('sum(amount) as amount, income_category_id')
            ->get()
            ->filter(function ($income) {
                return !empty($income->amount);
            })
            ->map(function ($income) {
                return (object)[
                    'key' => $income->IncomeCategory->name,
                    'value' => $income->amount,
                    'is_default' => $income->IncomeCategory->is_default
                ];
            });
    }

    /**
     * Get Expense Report
     */
    private static function getExpenseReport(string $startDate, string $endDate)
    {
        return Expense::with('ExpenseCategory:id,name,is_default')
            ->whereBetween('date', [$startDate, $endDate])
            ->whereNotIn('expense_category_id', ExpenseCategory::whereIn('name', ['loan_given', 'saving_withdrawal', 'loan_saving_withdrawal', 'account_closing_interest'])->pluck('id'))
            ->groupBy('expense_category_id')
            ->selectRaw('sum(amount) as amount, expense_category_id')
            ->get()
            ->filter(function ($expense) {
                return !empty($expense->amount);
            })
            ->map(function ($expense) {
                return (object)[
                    'key' => $expense->ExpenseCategory->name,
                    'value' => $expense->amount,
                    'is_default' => $expense->ExpenseCategory->is_default
                ];
            });
    }

    /**
     * Get Calculation Totals
     */
    private static function calculateTotals(SupportCollection $incomeReport, SupportCollection $expenseReport, string $financialYear, string $startDate, string $endDate)
    {
        $defaultMeta            = AuditReportMeta::whereIn('meta_key', ['authorized_shares', 'share_per_each', 'accumulation_of_savings', 'furniture'])->pluck('meta_value', 'meta_key')->toArray();
        $authorizedShares       = $defaultMeta['authorized_shares'] ?? 0;
        $sharePerEach           = $defaultMeta['share_per_each'] ?? 0;
        $accumulationSavings    = $defaultMeta['accumulation_of_savings'] ?? 0;
        $furniture              = $defaultMeta['furniture'] ?? 0;

        $shareCollections       = ClientRegistration::whereBetween('created_at', [$startDate, $endDate])->sum('share');
        $savingCollections      = SavingCollection::whereBetween('created_at', [$startDate, $endDate])->approve()->sum('deposit');
        $loanCollections        = LoanCollection::whereBetween('created_at', [$startDate, $endDate])->approve()->sum('loan');
        $loanIntCollections     = LoanCollection::whereBetween('created_at', [$startDate, $endDate])->approve()->sum('interest');
        $FDRCollection          = 0;

        $sharesReturn           = ClientRegistration::onlyTrashed()->whereBetween('deleted_at', [$startDate, $endDate])->sum('share');
        $savingReturn           = SavingWithdrawal::whereBetween('approved_at', [$startDate, $endDate])->approve()->sum('amount');
        $loanDistribute         = LoanAccount::whereBetween('is_loan_approved_at', [$startDate, $endDate])->where('is_loan_approved', true)->sum('loan_given');

        $previousFund           = AuditReport::where('financial_year', $financialYear)->selectRaw("JSON_EXTRACT(data, '$.deposit_expenditure.total_distributions.current_fund.value') AS current_fund")->latest()->first();
        $previousFund           = empty($previousFund) ? 0 : $previousFund->current_fund;

        $previousCapital        = AuditReport::where('financial_year', $financialYear)->selectRaw("JSON_EXTRACT(data, '$.surplus_value.capital_meta') AS capital_meta")->latest()->first();
        $previousCapitalMeta    = empty($previousCapital->capital_meta) ? [] : array_column($previousCapital->capital_meta, 'value', 'key');

        $previousResource       = AuditReport::where('financial_year', $financialYear)->selectRaw("JSON_EXTRACT(data, '$.surplus_value.resource_meta') AS resource_meta")->latest()->first();
        $previousResourceMeta   = empty($previousResource->resource_meta) ? [] : array_column($previousCapital->resource_meta, 'value', 'key');

        $previousPaidUpShare    = $previousCapitalMeta['paid_up_shares'] ?? 0;
        $previousSavingsDeposit = $previousCapitalMeta['savings_deposit'] ?? 0;
        $previousReservedFund   = $previousCapitalMeta['reserved_fund'] ?? 0;
        $previousCoOpDevFund    = $previousCapitalMeta['cooperative_dev_fund'] ?? 0;
        $previousUndoProfit     = $previousCapitalMeta['undistributed_profit'] ?? 0;

        $previousLoanOwed       = $previousCapitalMeta['loan_owed'] ?? 0;
        $previousLoss           = $previousCapitalMeta['net_loss'] ?? 0;

        $paidUpShares           = ($previousPaidUpShare + $shareCollections) - $sharesReturn;
        $savingsDeposit         = ($previousSavingsDeposit + $savingCollections) - $savingReturn;

        $previousFixedDeposit   = 0;
        $fixedDeposit           = $previousFixedDeposit + $FDRCollection;

        $totalIncomes           = $incomeReport->sum('value');
        $totalExpenses          = $expenseReport->sum('value');
        $net                    = $totalIncomes - $totalExpenses;
        $netProfits             = max($net, 0);
        $netLoss                = abs(min($net, 0));
        $totalEstIncomes        = $totalIncomes + $netLoss;
        $totalEstExpenses       = $totalExpenses + $netProfits;

        $totalCollections       = $shareCollections + $savingCollections + $loanCollections + $loanIntCollections + $FDRCollection + $totalIncomes;
        $totalDistributions     = $sharesReturn + $savingReturn + $loanDistribute + $totalExpenses;

        $totalEstCollections    = $totalCollections + $previousFund;
        $currentFund            = $totalEstCollections - $totalDistributions;
        $totalEstDistributions  = $totalDistributions + $currentFund;

        $reserveFund            = 0;
        $cooperativeFund        = 0;
        $undistributedProfits   = 0;
        $currentYearNetProfits  = 0;
        $totalEstNet            = 0;

        if (!empty($netProfits)) {
            $reserveFund            = ceil($netProfits * 0.15);
            $cooperativeFund        = ceil($netProfits * 0.03);
            $undistributedProfits   = ceil($netProfits - ($reserveFund + $cooperativeFund));
            $currentYearNetProfits  = $netProfits;
            $totalEstNet            = $reserveFund + $cooperativeFund + $undistributedProfits;
        }

        $reservedFund           = $previousReservedFund + $reserveFund;
        $cooperativeDevFund     = $previousCoOpDevFund + $cooperativeFund;
        $undistributedProfit    = $previousUndoProfit + $undistributedProfits;

        $loanOwed               = max(($previousLoanOwed + $loanDistribute) - $loanCollections, 0);
        $currentNetLoss         = max($previousLoss - $netProfits, 0);

        $totalCapitals          = $paidUpShares + $savingsDeposit + $fixedDeposit + $accumulationSavings + $reservedFund + $cooperativeDevFund + $undistributedProfit;
        $totalResources         = $currentFund + $loanOwed + $furniture + $currentNetLoss;

        return compact('shareCollections', 'savingCollections', 'loanCollections', 'loanIntCollections', 'FDRCollection', 'sharesReturn', 'savingReturn', 'loanDistribute', 'totalIncomes', 'totalExpenses', 'netProfits', 'netLoss', 'totalEstIncomes', 'totalEstExpenses', 'totalCollections', 'totalDistributions', 'totalEstCollections', 'previousFund', 'currentFund', 'totalEstDistributions', 'reserveFund', 'cooperativeFund', 'undistributedProfits', 'currentYearNetProfits', 'totalEstNet', 'paidUpShares', 'previousPaidUpShare', 'savingsDeposit', 'previousSavingsDeposit', 'fixedDeposit', 'previousFixedDeposit', 'FDRCollection', 'reservedFund', 'previousReservedFund', 'cooperativeDevFund', 'previousCoOpDevFund', 'undistributedProfit', 'previousUndoProfit', 'loanOwed', 'previousLoanOwed', 'currentNetLoss', 'previousLoss', 'authorizedShares', 'sharePerEach', 'accumulationSavings', 'furniture', 'totalCapitals', 'totalResources');
    }

    /**
     * Get Capital Meta
     */
    private static function getCapitalMeta(array $totals)
    {
        return collect([
            (object) ['key' => 'authorized_shares', 'value' => $totals['authorizedShares'], 'is_default' => true, 'child_meta' => ['share_per_each' => $totals['sharePerEach']]],
            (object) ['key' => 'paid_up_shares', 'value' => $totals['paidUpShares'], 'is_default' => true, 'child_meta' => ['previous_paid_up_shares' => $totals['previousPaidUpShare'], 'current_share_collections' => $totals['shareCollections'], 'current_share_return' => $totals['sharesReturn']]],
            (object) ['key' => 'savings_deposit', 'value' => $totals['savingsDeposit'], 'is_default' => true, 'child_meta' => ['previous_savings_deposit' => $totals['previousSavingsDeposit'], 'current_saving_collections' => $totals['savingCollections'], 'current_saving_return' => $totals['savingReturn']]],
            (object) ['key' => 'fixed_deposit', 'value' => $totals['fixedDeposit'], 'is_default' => true, 'child_meta' => ['previous_fixed_deposit' => $totals['previousFixedDeposit'], 'current_fixed_deposit_collections' => $totals['FDRCollection']]],
            (object) ['key' => 'accumulation_of_savings', 'value' => $totals['accumulationSavings'], 'is_default' => true],
            (object) ['key' => 'reserved_fund', 'value' => $totals['reservedFund'], 'is_default' => true, 'child_meta' => ['previous_reserved_fund' => $totals['previousReservedFund'], 'current_reserved_fund' => $totals['reserveFund']]],
            (object) ['key' => 'cooperative_dev_fund', 'value' => $totals['cooperativeDevFund'], 'is_default' => true, 'child_meta' => ['previous_cooperative_dev_fund' => $totals['previousCoOpDevFund'], 'current_cooperative_dev_fund' => $totals['cooperativeFund']]],
            (object) ['key' => 'undistributed_profit', 'value' => $totals['undistributedProfit'], 'is_default' => true, 'child_meta' => ['previous_undistributed_profit' => $totals['previousUndoProfit'], 'current_undistributed_profit' => $totals['undistributedProfits']]],
        ]);
    }

    /**
     * Get Resource Meta
     */
    private static function getResourceMeta(array $totals)
    {
        return collect([
            (object) ['key' => 'current_fund', 'value' => $totals['currentFund'], 'is_default' => true],
            (object) ['key' => 'loan_owed', 'value' => $totals['loanOwed'], 'is_default' => true, 'child_meta' => ['previous_loan_owed' => $totals['previousLoanOwed'], 'current_loan_distribution' => $totals['loanDistribute'], 'current_loan_collections' => $totals['loanCollections']]],
            (object) ['key' => 'furniture', 'value' => $totals['furniture'], 'is_default' => true],
            (object) ['key' => 'net_loss', 'value' => $totals['currentNetLoss'], 'is_default' => true, 'child_meta' => ['previous_loss' => $totals['previousLoss'], 'current_net_profit' => $totals['netProfits']]],
        ]);
    }

    /**
     * Construct Audit Report
     */
    private static function constructReport(SupportCollection $collectionMeta, SupportCollection $distributionMeta, SupportCollection $incomeReport, SupportCollection $expenseReport, SupportCollection $capitalMeta, SupportCollection $resourceMeta, array $totals)
    {
        return [
            'deposit_expenditure'   => [
                'deposit_meta'      => $collectionMeta->toArray(),
                'expenditure_meta'  => $distributionMeta->toArray(),
                'total_collections'     => [
                    'total_collections' => (object)['key' => 'total_collections', 'value' => $totals['totalCollections'], 'is_default' => true],
                    'previous_fund'     => (object)['key' => 'previous_fund', 'value' => $totals['previousFund'], 'is_default' => true],
                    'total'             => (object)['key' => 'total', 'value' => $totals['totalEstCollections'], 'is_default' => true],
                ],
                'total_distributions'       => [
                    'total_distributions'   => (object)['key' => 'total_distributions', 'value' => $totals['totalDistributions'], 'is_default' => true],
                    'current_fund'          => (object)['key' => 'current_fund', 'value' => $totals['currentFund'], 'is_default' => true],
                    'total'                 => (object)['key' => 'total', 'value' => $totals['totalEstDistributions'], 'is_default' => true],
                ],
            ],
            'profit_loss'   => [
                'incomes'   => $incomeReport->toArray(),
                'expenses'  => $expenseReport->toArray(),
                'total'     => [
                    'total_incomes' => (object)['key' => 'total_incomes', 'value' => $totals['totalIncomes'], 'is_default' => true],
                    'net_loss'      => (object)['key' => 'net_loss', 'value' => $totals['netLoss'], 'is_default' => true],
                    'total'         => (object)['key' => 'total', 'value' => $totals['totalEstIncomes'], 'is_default' => true],
                ],
                'total_expenses'     => [
                    'total_expenses' => (object)['key' => 'total_expenses', 'value' => $totals['totalExpenses'], 'is_default' => true],
                    'net_profits'    => (object)['key' => 'net_profits', 'value' => $totals['netProfits'], 'is_default' => true],
                    'total'          => (object)['key' => 'total', 'value' => $totals['totalEstExpenses'], 'is_default' => true],
                ],
            ],
            'net_profit' => [
                'expense_meta' => [
                    ['key' => 'reserve_fund', 'value' => $totals['reserveFund'], 'is_default' => true],
                    ['key' => 'cooperative_dev_fund', 'value' => $totals['cooperativeFund'], 'is_default' => true],
                    ['key' => 'undistributed_profits', 'value' => $totals['undistributedProfits'], 'is_default' => true]
                ],
                'income_meta' => [
                    ['key' => 'current_year_net_profits', 'value' => $totals['currentYearNetProfits'], 'is_default' => true]
                ],
                'total_incomes' => [
                    'total' => (object)['key' => 'total', 'value' => $totals['currentYearNetProfits'], 'is_default' => true],
                ],
                'total_expenses' => [
                    'total' => (object)['key' => 'total', 'value' => $totals['totalEstNet'], 'is_default' => true],
                ],
            ],
            'surplus_value' => [
                'capital_meta'  => $capitalMeta->toArray(),
                'resource_meta' => $resourceMeta->toArray(),
                'total_capitals' => [
                    'total' => (object)['key' => 'total', 'value' => $totals['totalCapitals'], 'is_default' => true],
                ],
                'total_resource' => [
                    'total' => (object)['key' => 'total', 'value' => $totals['totalResources'], 'is_default' => true],
                ],
            ]
        ];
    }
}
