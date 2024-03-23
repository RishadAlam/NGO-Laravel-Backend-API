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
        $startDate  = Carbon::createFromDate($startYear, 7, 1)->startOfDay();
        $endDate    = Carbon::createFromDate($endYear, 6, 30)->endOfDay();

        $collectionMeta     = static::getCollectionMeta($startDate, $endDate);
        $distributionMeta   = static::getDistributionMeta($startDate, $endDate);
        $incomeReport       = static::getIncomeReport($startDate, $endDate);
        $expenseReport      = static::getExpenseReport($startDate, $endDate);

        $totals = static::calculateTotals($incomeReport, $expenseReport, $collectionMeta, $distributionMeta, ($startYear - 1) . '-' . $startYear);
        $report = static::constructReport($collectionMeta, $distributionMeta, $incomeReport, $expenseReport, $totals);

        Log::info(print_r($report, true));
    }

    /**
     * Get Collection Meta
     */
    private static function getCollectionMeta(string $startDate, string $endDate)
    {
        return collect([
            ['key' => 'collection_of_shares', 'value' => ClientRegistration::whereBetween('created_at', [$startDate, $endDate])->sum('share'), 'is_default' => true],
            ['key' => 'collection_of_savings_deposits', 'value' => SavingCollection::whereBetween('created_at', [$startDate, $endDate])->approve()->sum('deposit'), 'is_default' => true],
            ['key' => 'collection_of_loans', 'value' => LoanCollection::whereBetween('created_at', [$startDate, $endDate])->approve()->sum('loan'), 'is_default' => true],
            ['key' => 'collection_of_loan_interests', 'value' => LoanCollection::whereBetween('created_at', [$startDate, $endDate])->approve()->sum('interest'), 'is_default' => true],
            ['key' => 'collection_of_fixed_deposits', 'value' => 0, 'is_default' => true]
        ]);
    }

    /**
     * Get Distribution Meta
     */
    private static function getDistributionMeta(string $startDate, string $endDate)
    {
        return collect([
            ['key' => 'shares_return', 'value' => ClientRegistration::onlyTrashed()->whereBetween('deleted_at', [$startDate, $endDate])->sum('share'), 'is_default' => true],
            ['key' => 'savings_return', 'value' => SavingWithdrawal::whereBetween('approved_at', [$startDate, $endDate])->approve()->sum('amount'), 'is_default' => true],
            ['key' => 'loan_given', 'value' => LoanAccount::whereBetween('is_loan_approved_at', [$startDate, $endDate])->where('is_loan_approved', true)->sum('loan_given'), 'is_default' => true]
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
    private static function calculateTotals(SupportCollection $incomeReport, SupportCollection $expenseReport, SupportCollection $collectionMeta, SupportCollection $distributionMeta, string $financialYear)
    {
        $previousFund           = AuditReport::where('financial_year', $financialYear)->selectRaw("JSON_EXTRACT(data, '$.deposit_expenditure.total_distributions.current_fund.value') AS current_fund")->latest()->first();
        $previousFund           = empty($previousFund) ? 0 : $previousFund->current_fund;

        $totalIncomes           = $incomeReport->sum('value');
        $totalExpenses          = $expenseReport->sum('value');
        $net                    = $totalIncomes - $totalExpenses;
        $netProfits             = max($net, 0);
        $netLoss                = abs(min($net, 0));
        $totalEstIncomes        = $totalIncomes + $netLoss;
        $totalEstExpenses       = $totalExpenses + $netProfits;

        $totalCollections       = $collectionMeta->sum('value') + $totalIncomes;
        $totalDistributions     = $distributionMeta->sum('value') + $totalExpenses;

        $totalEstCollections    = $totalCollections + $previousFund;
        $currentFund            = $totalEstCollections - $totalDistributions;
        $totalEstDistributions  = $totalDistributions + $currentFund;

        return [
            'totalIncomes'          => $totalIncomes,
            'totalExpenses'         => $totalExpenses,
            'netProfits'            => $netProfits,
            'netLoss'               => $netLoss,
            'totalEstIncomes'       => $totalEstIncomes,
            'totalEstExpenses'      => $totalEstExpenses,
            'totalCollections'      => $totalCollections,
            'totalDistributions'    => $totalDistributions,
            'totalEstCollections'   => $totalEstCollections,
            'previous_fund'         => $previousFund,
            'currentFund'           => $currentFund,
            'totalEstDistributions' => $totalEstDistributions,
        ];
    }

    /**
     * Construct Audit Report
     */
    private static function constructReport(SupportCollection $collectionMeta, SupportCollection $distributionMeta, SupportCollection $incomeReport, SupportCollection $expenseReport, array $totals)
    {
        return [
            'deposit_expenditure'   => [
                'deposit_meta'      => $collectionMeta->toArray(),
                'expenditure_meta'  => $distributionMeta->toArray(),
                'total_collections'     => [
                    'total_collections' => (object)['key' => 'total_collections', 'value' => $totals['totalCollections'], 'is_default' => true],
                    'previous_fund'     => (object)['key' => 'previous_fund', 'value' => $totals['previous_fund'], 'is_default' => true],
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
                'total_incomes'     => [
                    'total_incomes' => (object)['key' => 'total_incomes', 'value' => $totals['totalIncomes'], 'is_default' => true],
                    'net_loss'      => (object)['key' => 'net_loss', 'value' => $totals['netLoss'], 'is_default' => true],
                    'total'         => (object)['key' => 'total', 'value' => $totals['totalEstIncomes'], 'is_default' => true],
                ],
                'total_expenses'     => [
                    'total_expenses' => (object)['key' => 'total_expenses', 'value' => $totals['totalExpenses'], 'is_default' => true],
                    'net_profits'    => (object)['key' => 'net_profits', 'value' => $totals['netProfits'], 'is_default' => true],
                    'total'          => (object)['key' => 'total', 'value' => $totals['totalEstExpenses'], 'is_default' => true],
                ],
            ]
        ];
    }
}
