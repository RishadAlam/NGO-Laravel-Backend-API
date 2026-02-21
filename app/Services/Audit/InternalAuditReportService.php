<?php

namespace App\Services\Audit;

use Carbon\Carbon;
use App\Helpers\Helper;
use App\Models\category\Category;
use App\Models\client\LoanAccount;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Collections\LoanCollection;
use App\Models\Collections\SavingCollection;

class InternalAuditReportService
{
    private const EPSILON = 0;

    /**
     * Build and return the internal audit report payload.
     */
    public function generate(array $filters = []): array
    {
        [$fromDate, $toDate] = $this->resolveDateRange($filters);
        $fieldId = $filters['field_id'] ?? null;
        $centerId = $filters['center_id'] ?? null;

        $savingCategories = Category::query()
            ->where('saving', true)
            ->active()
            ->orderBy('id')
            ->get(['id', 'name', 'is_default']);

        $loanCategories = Category::query()
            ->where('loan', true)
            ->active()
            ->orderBy('id')
            ->get(['id', 'name', 'is_default']);

        $savingDepositsByCategory = $this->savingCollectionSummaryQuery($fromDate, $toDate, $fieldId, $centerId)
            ->pluck('total_savings_deposit', 'category_id');

        $loanSavingsByCategory = $this->loanSavingSummaryQuery($fromDate, $toDate, $fieldId, $centerId)
            ->pluck('total_loan_savings', 'category_id');

        $loanSummaryByCategory = $this->loanAccountSummaryQuery($fromDate, $toDate, $fieldId, $centerId)
            ->get()
            ->keyBy('category_id');

        $savingsByCategory = $savingCategories->map(function ($category) use ($savingDepositsByCategory) {
            return [
                'categoryId' => (int) $category->id,
                'categoryName' => $this->categoryName($category),
                'totalSavingsDeposit' => (int) ($savingDepositsByCategory[$category->id] ?? 0),
            ];
        })->values()->all();

        $loanSavings = $loanCategories->map(function ($category) use ($loanSavingsByCategory) {
            return [
                'categoryId' => (int) $category->id,
                'categoryName' => $this->categoryName($category),
                'totalLoanSavings' => (int) ($loanSavingsByCategory[$category->id] ?? 0),
            ];
        })->values()->all();

        $loansByCategory = $loanCategories->map(function ($category) use ($loanSummaryByCategory) {
            $summary = $loanSummaryByCategory->get($category->id);

            $totalLoanGivenActual = (int) ($summary->total_loan_given_actual ?? 0);
            $totalLoanRecovery = (int) ($summary->total_loan_recovery ?? 0);
            $totalLoanRemaining = (int) ($summary->total_loan_remaining ?? 0);
            $totalLoanGivenCalculated = $totalLoanRecovery + $totalLoanRemaining;

            $totalInterestActual = (int) ($summary->total_interest_actual ?? 0);
            $totalInterestRecovery = (int) ($summary->total_interest_recovery ?? 0);
            $totalInterestRemaining = (int) ($summary->total_interest_remaining ?? 0);
            $totalInterestCalculated = $totalInterestRecovery + $totalInterestRemaining;

            return [
                'categoryId' => (int) $category->id,
                'categoryName' => $this->categoryName($category),
                'totalLoanGivenActual' => $totalLoanGivenActual,
                'totalLoanRecovery' => $totalLoanRecovery,
                'totalLoanRemaining' => $totalLoanRemaining,
                'totalLoanGivenCalculated' => $totalLoanGivenCalculated,
                'loanMismatch' => abs($totalLoanGivenActual - $totalLoanGivenCalculated) > self::EPSILON,
                'totalInterestActual' => $totalInterestActual,
                'totalInterestRecovery' => $totalInterestRecovery,
                'totalInterestRemaining' => $totalInterestRemaining,
                'totalInterestCalculated' => $totalInterestCalculated,
                'interestMismatch' => abs($totalInterestActual - $totalInterestCalculated) > self::EPSILON,
            ];
        })->values()->all();

        $totalSavingsDepositAll = array_sum(array_column($savingsByCategory, 'totalSavingsDeposit'));
        $totalLoanSavingsAll = array_sum(array_column($loanSavings, 'totalLoanSavings'));
        $totalAllSavingsCombined = $totalSavingsDepositAll + $totalLoanSavingsAll;
        $totalLoanRemainingAll = array_sum(array_column($loansByCategory, 'totalLoanRemaining'));
        $profitLoss = $totalLoanRemainingAll - $totalAllSavingsCombined;

        return [
            'savingsByCategory' => $savingsByCategory,
            'loanSavingsByCategory' => $loanSavings,
            'loansByCategory' => $loansByCategory,
            'totals' => [
                'totalSavingsDepositAll' => $totalSavingsDepositAll,
                'totalLoanSavingsAll' => $totalLoanSavingsAll,
                'totalAllSavingsCombined' => $totalAllSavingsCombined,
                'totalLoanRemainingAll' => $totalLoanRemainingAll,
                'profitLoss' => $profitLoss,
            ],
        ];
    }

    /**
     * Aggregate savings deposits by category.
     */
    private function savingCollectionSummaryQuery(?Carbon $fromDate, ?Carbon $toDate, ?int $fieldId, ?int $centerId): Builder
    {
        return SavingCollection::query()
            ->approve()
            ->selectRaw('category_id, SUM(deposit) AS total_savings_deposit')
            ->when($fromDate, function (Builder $query) use ($fromDate) {
                $query->where('created_at', '>=', $fromDate);
            })
            ->when($toDate, function (Builder $query) use ($toDate) {
                $query->where('created_at', '<=', $toDate);
            })
            ->when($fieldId, function (Builder $query) use ($fieldId) {
                $query->where('field_id', $fieldId);
            })
            ->when($centerId, function (Builder $query) use ($centerId) {
                $query->where('center_id', $centerId);
            })
            ->groupBy('category_id');
    }

    /**
     * Aggregate loan savings deposits by category.
     */
    private function loanSavingSummaryQuery(?Carbon $fromDate, ?Carbon $toDate, ?int $fieldId, ?int $centerId): Builder
    {
        return LoanCollection::query()
            ->approve()
            ->selectRaw('category_id, SUM(deposit) AS total_loan_savings')
            ->when($fromDate, function (Builder $query) use ($fromDate) {
                $query->where('created_at', '>=', $fromDate);
            })
            ->when($toDate, function (Builder $query) use ($toDate) {
                $query->where('created_at', '<=', $toDate);
            })
            ->when($fieldId, function (Builder $query) use ($fieldId) {
                $query->where('field_id', $fieldId);
            })
            ->when($centerId, function (Builder $query) use ($centerId) {
                $query->where('center_id', $centerId);
            })
            ->groupBy('category_id');
    }

    /**
     * Aggregate loan principal and interest totals by category.
     */
    private function loanAccountSummaryQuery(?Carbon $fromDate, ?Carbon $toDate, ?int $fieldId, ?int $centerId): Builder
    {
        return LoanAccount::query()
            ->approve('is_loan_approved')
            ->selectRaw('
                category_id,
                SUM(loan_given) AS total_loan_given_actual,
                SUM(total_loan_rec) AS total_loan_recovery,
                SUM(total_loan_remaining) AS total_loan_remaining,
                SUM(total_payable_interest) AS total_interest_actual,
                SUM(total_interest_rec) AS total_interest_recovery,
                SUM(total_interest_remaining) AS total_interest_remaining
            ')
            ->when($fromDate, function (Builder $query) use ($fromDate) {
                $query->where('is_loan_approved_at', '>=', $fromDate);
            })
            ->when($toDate, function (Builder $query) use ($toDate) {
                $query->where('is_loan_approved_at', '<=', $toDate);
            })
            ->when($fieldId, function (Builder $query) use ($fieldId) {
                $query->where('field_id', $fieldId);
            })
            ->when($centerId, function (Builder $query) use ($centerId) {
                $query->where('center_id', $centerId);
            })
            ->groupBy('category_id');
    }

    /**
     * Resolve filters to optional date boundaries.
     */
    private function resolveDateRange(array $filters): array
    {
        $fromDate = !empty($filters['fromDate']) ? Carbon::parse($filters['fromDate'])->startOfDay() : null;
        $toDate = !empty($filters['toDate']) ? Carbon::parse($filters['toDate'])->endOfDay() : null;

        if ((!$fromDate || !$toDate) && !empty($filters['date_range'])) {
            $dateRange = json_decode($filters['date_range'], true);

            if (is_array($dateRange)) {
                if (!$fromDate && !empty($dateRange[0])) {
                    $fromDate = Carbon::parse($dateRange[0])->startOfDay();
                }
                if (!$toDate && !empty($dateRange[1])) {
                    $toDate = Carbon::parse($dateRange[1])->endOfDay();
                }
            }
        }

        return [$fromDate, $toDate];
    }

    /**
     * Translate default category names and return user-ready labels.
     */
    private function categoryName(Category $category): string
    {
        return Helper::setDefaultName(
            (bool) $category->is_default,
            (string) $category->name,
            'customValidations.category.default.'
        );
    }
}
