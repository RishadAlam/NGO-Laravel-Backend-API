<?php

namespace App\Http\Controllers\Analytics;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Collections\LoanCollection;
use App\Models\Collections\SavingCollection;
use App\Models\client\ClientRegistration;
use App\Models\client\LoanAccount;
use App\Models\client\SavingAccount;
use App\Models\Withdrawal\SavingWithdrawal;
use App\Models\Withdrawal\LoanSavingWithdrawal;
use App\Models\accounts\Income;
use App\Models\accounts\Expense;
use App\Models\accounts\AccountTransfer;
use App\Models\accounts\AccountWithdrawal;
use App\Models\transactions\SavingToSavingTransaction;
use App\Models\transactions\SavingToLoanTransaction;
use App\Models\transactions\LoanToSavingTransaction;
use App\Models\transactions\LoanToLoanTransaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class AnalyticsController extends Controller
{
    private const SOURCE_METHODS = [
        'saving_collection' => 'fetchSavingCollections',
        'loan_collection' => 'fetchLoanCollections',
        'saving_withdrawal' => 'fetchSavingWithdrawals',
        'loan_saving_withdrawal' => 'fetchLoanSavingWithdrawals',
        'saving_to_saving' => 'fetchSavingToSavingTransactions',
        'saving_to_loan' => 'fetchSavingToLoanTransactions',
        'loan_to_saving' => 'fetchLoanToSavingTransactions',
        'loan_to_loan' => 'fetchLoanToLoanTransactions',
        'income' => 'fetchIncomes',
        'expense' => 'fetchExpenses',
        'account_transfer' => 'fetchAccountTransfers',
        'account_withdrawal' => 'fetchAccountWithdrawals',
        'client_registration' => 'fetchClientRegistrations',
        'saving_account_registration' => 'fetchSavingAccountRegistrations',
        'loan_account_registration' => 'fetchLoanAccountRegistrations',
        'loan_given' => 'fetchLoanGivenRecords',
    ];
    private const SOURCE_PRIORITIES = [
        'client_registration' => 0,
        'saving_account_registration' => 1,
        'loan_account_registration' => 2,
        'loan_given' => 3,
    ];

    /**
     * Instantiate a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('can:analytics_dashboard_view')->only('index');
    }

    /**
     * Display analytics records with filters.
     */
    public function index(Request $request)
    {
        foreach (['field_id', 'center_id', 'category_id', 'creator_id', 'approved_by', 'account_id', 'source_type', 'date_range'] as $key) {
            if ($request->input($key) === '') {
                $request->merge([$key => null]);
            }
        }

        $request->validate([
            'date_range' => ['nullable', 'string'],
            'field_id' => ['nullable', 'integer', 'exists:fields,id'],
            'center_id' => ['nullable', 'integer', 'exists:centers,id'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'creator_id' => ['nullable', 'integer', 'exists:users,id'],
            'approved_by' => ['nullable', 'integer', 'exists:users,id'],
            'account_id' => ['nullable', 'integer', 'exists:accounts,id'],
            'source_type' => [
                'nullable',
                'string',
                'in:' . implode(',', array_keys(self::SOURCE_METHODS)),
            ],
            'approval_status' => ['nullable', 'string', 'in:all,approved,pending'],
        ]);

        $filters = [
            'date_range' => Helper::getDateRange($request->input('date_range')),
            'field_id' => $request->integer('field_id') ?: null,
            'center_id' => $request->integer('center_id') ?: null,
            'category_id' => $request->integer('category_id') ?: null,
            'creator_id' => $request->integer('creator_id') ?: null,
            'approved_by' => $request->integer('approved_by') ?: null,
            'account_id' => $request->integer('account_id') ?: null,
            'source_type' => $request->input('source_type'),
            'approval_status' => $request->input('approval_status', 'all'),
        ];

        $sourceKeys = $this->resolveSourceKeys($filters['source_type']);
        $sourceMethods = collect($sourceKeys)->mapWithKeys(function ($sourceKey) {
            return [$sourceKey => self::SOURCE_METHODS[$sourceKey]];
        })->all();

        $records = collect();
        foreach ($sourceMethods as $method) {
            $records = $records->merge($this->{$method}($filters));
        }

        $records = $records
            ->sort(function (array $a, array $b) {
                $priorityA = $this->resolveSourcePriority($a['source_type'] ?? null);
                $priorityB = $this->resolveSourcePriority($b['source_type'] ?? null);

                if ($priorityA !== $priorityB) {
                    return $priorityA <=> $priorityB;
                }

                $timestampA = $this->resolveRecordSortTimestamp($a);
                $timestampB = $this->resolveRecordSortTimestamp($b);

                if ($timestampA !== $timestampB) {
                    return $timestampB <=> $timestampA;
                }

                return ($b['source_id'] ?? 0) <=> ($a['source_id'] ?? 0);
            })
            ->values()
            ->all();

        return create_response(null, $records);
    }

    private function fetchClientRegistrations(array $filters): Collection
    {
        if (!empty($filters['category_id']) || !empty($filters['account_id'])) {
            return collect();
        }

        $query = ClientRegistration::query()
            ->with([
                'Field:id,name',
                'Center:id,name',
                'Author:id,name',
                'Approver:id,name',
            ])
            ->whereBetween('created_at', $filters['date_range']);

        $this->applyCommonFilters($query, $filters);
        $this->applyFieldCenterFilters($query, $filters);
        $this->applyApprovalFilters($query, $filters);

        return $query->latest('created_at')->get()->map(function ($item) {
            return [
                'id' => 'client_registration_' . $item->id,
                'source_id' => $item->id,
                'source_type' => 'client_registration',
                'transaction_type' => 'registration',
                'date' => $item->created_at,
                'amount' => (int) ($item->share ?? 0),
                'description' => $item->name,
                'acc_no' => $item->acc_no,
                'client' => $this->asClientEntity($item),
                'account' => null,
                'from_account' => null,
                'to_account' => null,
                'field' => $this->asNamedEntity($item->Field),
                'center' => $this->asNamedEntity($item->Center),
                'category' => null,
                'author' => $this->asUserEntity($item->Author),
                'approver' => $this->asUserEntity($item->Approver),
                'approved_at' => $item->approved_at,
                'is_approved' => (bool) $item->is_approved,
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at,
                'meta' => [
                    'name' => $item->name,
                    'nid' => $item->nid,
                    'primary_phone' => $item->primary_phone,
                    'share' => (int) ($item->share ?? 0),
                ],
            ];
        });
    }

    private function fetchSavingAccountRegistrations(array $filters): Collection
    {
        if (!empty($filters['account_id'])) {
            return collect();
        }

        $query = SavingAccount::query()
            ->with([
                'Field:id,name',
                'Center:id,name',
                'Category:id,name,is_default',
                'ClientRegistration:id,acc_no,name',
                'Author:id,name',
                'Approver:id,name',
            ])
            ->whereBetween('created_at', $filters['date_range']);

        $this->applyCommonFilters($query, $filters);
        $this->applyDimensionFilters($query, $filters);
        $this->applyApprovalFilters($query, $filters);

        return $query->latest('created_at')->get()->map(function ($item) {
            return [
                'id' => 'saving_account_registration_' . $item->id,
                'source_id' => $item->id,
                'source_type' => 'saving_account_registration',
                'transaction_type' => 'registration',
                'date' => $item->created_at,
                'amount' => (int) ($item->payable_deposit ?? 0),
                'description' => $item->description,
                'acc_no' => $item->acc_no,
                'client' => $this->asClientEntity($item->ClientRegistration),
                'account' => null,
                'from_account' => null,
                'to_account' => null,
                'field' => $this->asNamedEntity($item->Field),
                'center' => $this->asNamedEntity($item->Center),
                'category' => $this->asNamedEntity($item->Category, true),
                'author' => $this->asUserEntity($item->Author),
                'approver' => $this->asUserEntity($item->Approver),
                'approved_at' => $item->approved_at,
                'is_approved' => (bool) $item->is_approved,
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at,
                'meta' => [
                    'client' => $this->asNamedEntity($item->ClientRegistration),
                    'start_date' => $item->start_date,
                    'duration_date' => $item->duration_date,
                    'payable_installment' => (int) ($item->payable_installment ?? 0),
                    'payable_deposit' => (int) ($item->payable_deposit ?? 0),
                    'payable_interest' => (int) ($item->payable_interest ?? 0),
                    'total_deposit_with_interest' => (int) ($item->total_deposit_with_interest ?? 0),
                    'total_installment' => (int) ($item->total_installment ?? 0),
                    'status' => (bool) $item->status,
                ],
            ];
        });
    }

    private function fetchLoanAccountRegistrations(array $filters): Collection
    {
        if (!empty($filters['account_id'])) {
            return collect();
        }

        $query = LoanAccount::query()
            ->with([
                'Field:id,name',
                'Center:id,name',
                'Category:id,name,is_default',
                'ClientRegistration:id,acc_no,name',
                'Author:id,name',
                'Approver:id,name',
                'LoanApprover:id,name',
            ])
            ->whereBetween('created_at', $filters['date_range']);

        $this->applyCommonFilters($query, $filters);
        $this->applyDimensionFilters($query, $filters);
        $this->applyLoanRegistrationApprovalFilters($query, $filters);

        return $query->latest('created_at')->get()->map(function ($item) {
            $loanGivenDate = $item->is_loan_approved_at ?: $item->start_date ?: $item->created_at;

            return [
                'id' => 'loan_account_registration_' . $item->id,
                'source_id' => $item->id,
                'source_type' => 'loan_account_registration',
                'transaction_type' => 'registration',
                'date' => $item->created_at,
                'amount' => (int) ($item->loan_given ?? 0),
                'description' => $item->description,
                'acc_no' => $item->acc_no,
                'client' => $this->asClientEntity($item->ClientRegistration),
                'account' => null,
                'from_account' => null,
                'to_account' => null,
                'field' => $this->asNamedEntity($item->Field),
                'center' => $this->asNamedEntity($item->Center),
                'category' => $this->asNamedEntity($item->Category, true),
                'author' => $this->asUserEntity($item->Author),
                'approver' => $this->asUserEntity($item->Approver),
                'approved_at' => $item->approved_at,
                'is_approved' => (bool) $item->is_approved,
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at,
                'meta' => [
                    'client' => $this->asNamedEntity($item->ClientRegistration),
                    'loan_given_date' => $loanGivenDate,
                    'start_date' => $item->start_date,
                    'duration_date' => $item->duration_date,
                    'loan_given' => (int) ($item->loan_given ?? 0),
                    'payable_installment' => (int) ($item->payable_installment ?? 0),
                    'payable_interest' => (int) ($item->payable_interest ?? 0),
                    'total_payable_loan_with_interest' => (int) ($item->total_payable_loan_with_interest ?? 0),
                    'total_payable_interest' => (int) ($item->total_payable_interest ?? 0),
                    'is_loan_approved' => (bool) $item->is_loan_approved,
                    'registration_approver' => $this->asUserEntity($item->Approver),
                    'loan_approver' => $this->asUserEntity($item->LoanApprover),
                ],
            ];
        });
    }

    private function fetchLoanGivenRecords(array $filters): Collection
    {
        if (!empty($filters['account_id'])) {
            return collect();
        }

        $query = LoanAccount::query()
            ->with([
                'Field:id,name',
                'Center:id,name',
                'Category:id,name,is_default',
                'ClientRegistration:id,acc_no,name',
                'Author:id,name',
                'Approver:id,name',
                'LoanApprover:id,name',
            ])
            ->where('is_approved', true);

        $this->applyLoanGivenDateFilters($query, $filters);
        $this->applyCommonFilters($query, $filters);
        $this->applyDimensionFilters($query, $filters);
        $this->applyLoanRegistrationApprovalFilters($query, $filters);

        return $query->latest('created_at')->get()->map(function ($item) {
            $loanGivenDate = $item->is_loan_approved_at ?: $item->start_date ?: $item->created_at;

            return [
                'id' => 'loan_given_' . $item->id,
                'source_id' => $item->id,
                'source_type' => 'loan_given',
                'transaction_type' => 'registration',
                'date' => $loanGivenDate,
                'sort_date' => $loanGivenDate,
                'amount' => (int) ($item->loan_given ?? 0),
                'description' => $item->description,
                'acc_no' => $item->acc_no,
                'client' => $this->asClientEntity($item->ClientRegistration),
                'account' => null,
                'from_account' => null,
                'to_account' => null,
                'field' => $this->asNamedEntity($item->Field),
                'center' => $this->asNamedEntity($item->Center),
                'category' => $this->asNamedEntity($item->Category, true),
                'author' => $this->asUserEntity($item->Author),
                'approver' => $this->asUserEntity($item->LoanApprover ?: $item->Approver),
                'approved_at' => $item->is_loan_approved_at ?: $item->approved_at,
                'is_approved' => (bool) $item->is_approved,
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at,
                'meta' => [
                    'client' => $this->asNamedEntity($item->ClientRegistration),
                    'loan_given_date' => $loanGivenDate,
                    'start_date' => $item->start_date,
                    'duration_date' => $item->duration_date,
                    'loan_given' => (int) ($item->loan_given ?? 0),
                    'payable_installment' => (int) ($item->payable_installment ?? 0),
                    'payable_interest' => (int) ($item->payable_interest ?? 0),
                    'total_payable_loan_with_interest' => (int) ($item->total_payable_loan_with_interest ?? 0),
                    'total_payable_interest' => (int) ($item->total_payable_interest ?? 0),
                    'is_loan_approved' => (bool) $item->is_loan_approved,
                    'registration_approver' => $this->asUserEntity($item->Approver),
                    'loan_approver' => $this->asUserEntity($item->LoanApprover),
                ],
            ];
        });
    }

    private function resolveSourceKeys(?string $sourceType): array
    {
        if (empty($sourceType)) {
            return array_keys(self::SOURCE_METHODS);
        }

        return [$sourceType];
    }

    private function resolveSourcePriority(?string $sourceType): int
    {
        if (!$sourceType) {
            return 999;
        }

        return self::SOURCE_PRIORITIES[$sourceType] ?? 100;
    }

    private function resolveRecordSortTimestamp(array $item): int
    {
        $dateValue = $item['sort_date'] ?? $item['date'] ?? $item['created_at'] ?? now()->toDateTimeString();
        $timestamp = strtotime((string) $dateValue);

        return $timestamp ?: 0;
    }

    private function fetchSavingCollections(array $filters): Collection
    {
        $query = SavingCollection::query()
            ->with([
                'Field:id,name',
                'Center:id,name',
                'Category:id,name,is_default',
                'ClientRegistration:id,acc_no,name',
                'Account:id,name,is_default',
                'Author:id,name',
                'Approver:id,name',
            ])
            ->whereBetween('created_at', $filters['date_range']);

        $this->applyCommonFilters($query, $filters);
        $this->applyDimensionFilters($query, $filters);
        $this->applyAccountFilter($query, $filters);
        $this->applyApprovalFilters($query, $filters);

        return $query->latest('created_at')->get()->map(function ($item) {
            return [
                'id' => 'saving_collection_' . $item->id,
                'source_id' => $item->id,
                'source_type' => 'saving_collection',
                'transaction_type' => 'collection',
                'date' => $item->created_at,
                'amount' => (int) $item->deposit,
                'description' => $item->description,
                'acc_no' => $item->acc_no,
                'client' => $this->asClientEntity($item->ClientRegistration),
                'account' => $this->asNamedEntity($item->Account, true),
                'field' => $this->asNamedEntity($item->Field),
                'center' => $this->asNamedEntity($item->Center),
                'category' => $this->asNamedEntity($item->Category, true),
                'author' => $this->asUserEntity($item->Author),
                'approver' => $this->asUserEntity($item->Approver),
                'approved_at' => $item->approved_at,
                'is_approved' => (bool) $item->is_approved,
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at,
                'meta' => [
                    'client' => $this->asClientEntity($item->ClientRegistration),
                    'installment' => (int) $item->installment,
                    'deposit' => (int) $item->deposit,
                ],
            ];
        });
    }

    private function fetchLoanCollections(array $filters): Collection
    {
        $query = LoanCollection::query()
            ->with([
                'Field:id,name',
                'Center:id,name',
                'Category:id,name,is_default',
                'ClientRegistration:id,acc_no,name',
                'Account:id,name,is_default',
                'Author:id,name',
                'Approver:id,name',
            ])
            ->whereBetween('created_at', $filters['date_range']);

        $this->applyCommonFilters($query, $filters);
        $this->applyDimensionFilters($query, $filters);
        $this->applyAccountFilter($query, $filters);
        $this->applyApprovalFilters($query, $filters);

        return $query->latest('created_at')->get()->map(function ($item) {
            return [
                'id' => 'loan_collection_' . $item->id,
                'source_id' => $item->id,
                'source_type' => 'loan_collection',
                'transaction_type' => 'collection',
                'date' => $item->created_at,
                'amount' => (int) $item->total,
                'description' => $item->description,
                'acc_no' => $item->acc_no,
                'client' => $this->asClientEntity($item->ClientRegistration),
                'account' => $this->asNamedEntity($item->Account, true),
                'field' => $this->asNamedEntity($item->Field),
                'center' => $this->asNamedEntity($item->Center),
                'category' => $this->asNamedEntity($item->Category, true),
                'author' => $this->asUserEntity($item->Author),
                'approver' => $this->asUserEntity($item->Approver),
                'approved_at' => $item->approved_at,
                'is_approved' => (bool) $item->is_approved,
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at,
                'meta' => [
                    'client' => $this->asClientEntity($item->ClientRegistration),
                    'installment' => (int) $item->installment,
                    'deposit' => (int) $item->deposit,
                    'loan' => (int) $item->loan,
                    'interest' => (int) $item->interest,
                    'total' => (int) $item->total,
                ],
            ];
        });
    }

    private function fetchSavingWithdrawals(array $filters): Collection
    {
        $query = SavingWithdrawal::query()
            ->with([
                'Field:id,name',
                'Center:id,name',
                'Category:id,name,is_default',
                'ClientRegistration:id,acc_no,name',
                'Account:id,name,is_default',
                'Author:id,name',
                'Approver:id,name',
            ])
            ->whereBetween('created_at', $filters['date_range']);

        $this->applyCommonFilters($query, $filters);
        $this->applyDimensionFilters($query, $filters);
        $this->applyAccountFilter($query, $filters);
        $this->applyApprovalFilters($query, $filters);

        return $query->latest('created_at')->get()->map(function ($item) {
            return [
                'id' => 'saving_withdrawal_' . $item->id,
                'source_id' => $item->id,
                'source_type' => 'saving_withdrawal',
                'transaction_type' => 'withdrawal',
                'date' => $item->created_at,
                'amount' => (int) $item->amount,
                'description' => $item->description,
                'acc_no' => $item->acc_no,
                'client' => $this->asClientEntity($item->ClientRegistration),
                'account' => $this->asNamedEntity($item->Account, true),
                'field' => $this->asNamedEntity($item->Field),
                'center' => $this->asNamedEntity($item->Center),
                'category' => $this->asNamedEntity($item->Category, true),
                'author' => $this->asUserEntity($item->Author),
                'approver' => $this->asUserEntity($item->Approver),
                'approved_at' => $item->approved_at,
                'is_approved' => (bool) $item->is_approved,
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at,
                'meta' => [
                    'client' => $this->asClientEntity($item->ClientRegistration),
                    'balance' => (int) $item->balance,
                    'balance_remaining' => (int) $item->balance_remaining,
                ],
            ];
        });
    }

    private function fetchLoanSavingWithdrawals(array $filters): Collection
    {
        $query = LoanSavingWithdrawal::query()
            ->with([
                'Field:id,name',
                'Center:id,name',
                'Category:id,name,is_default',
                'ClientRegistration:id,acc_no,name',
                'Account:id,name,is_default',
                'Author:id,name',
                'Approver:id,name',
            ])
            ->whereBetween('created_at', $filters['date_range']);

        $this->applyCommonFilters($query, $filters);
        $this->applyDimensionFilters($query, $filters);
        $this->applyAccountFilter($query, $filters);
        $this->applyApprovalFilters($query, $filters);

        return $query->latest('created_at')->get()->map(function ($item) {
            return [
                'id' => 'loan_saving_withdrawal_' . $item->id,
                'source_id' => $item->id,
                'source_type' => 'loan_saving_withdrawal',
                'transaction_type' => 'withdrawal',
                'date' => $item->created_at,
                'amount' => (int) $item->amount,
                'description' => $item->description,
                'acc_no' => $item->acc_no,
                'client' => $this->asClientEntity($item->ClientRegistration),
                'account' => $this->asNamedEntity($item->Account, true),
                'field' => $this->asNamedEntity($item->Field),
                'center' => $this->asNamedEntity($item->Center),
                'category' => $this->asNamedEntity($item->Category, true),
                'author' => $this->asUserEntity($item->Author),
                'approver' => $this->asUserEntity($item->Approver),
                'approved_at' => $item->approved_at,
                'is_approved' => (bool) $item->is_approved,
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at,
                'meta' => [
                    'client' => $this->asClientEntity($item->ClientRegistration),
                    'balance' => (int) $item->balance,
                    'balance_remaining' => (int) $item->balance_remaining,
                ],
            ];
        });
    }

    private function fetchSavingToSavingTransactions(array $filters): Collection
    {
        return $this->fetchClientTransactions(SavingToSavingTransaction::class, 'saving_to_saving', $filters);
    }

    private function fetchSavingToLoanTransactions(array $filters): Collection
    {
        return $this->fetchClientTransactions(SavingToLoanTransaction::class, 'saving_to_loan', $filters);
    }

    private function fetchLoanToSavingTransactions(array $filters): Collection
    {
        return $this->fetchClientTransactions(LoanToSavingTransaction::class, 'loan_to_saving', $filters);
    }

    private function fetchLoanToLoanTransactions(array $filters): Collection
    {
        return $this->fetchClientTransactions(LoanToLoanTransaction::class, 'loan_to_loan', $filters);
    }

    private function fetchClientTransactions(string $modelClass, string $sourceType, array $filters): Collection
    {
        if (!empty($filters['account_id'])) {
            return collect();
        }

        $query = $modelClass::query()
            ->with([
                'TXAccount:id,acc_no,client_registration_id,field_id,center_id,category_id',
                'RXAccount:id,acc_no,client_registration_id,field_id,center_id,category_id',
                'TXAccount.Field:id,name',
                'TXAccount.Center:id,name',
                'TXAccount.Category:id,name,is_default',
                'TXAccount.ClientRegistration:id,acc_no,name',
                'RXAccount.ClientRegistration:id,acc_no,name',
                'Author:id,name',
                'Approver:id,name',
            ])
            ->whereBetween('created_at', $filters['date_range']);

        $this->applyCommonFilters($query, $filters);
        $this->applyApprovalFilters($query, $filters);

        $query->when(!empty($filters['field_id']), function ($q) use ($filters) {
            $q->whereHas('TXAccount', function ($accountQuery) use ($filters) {
                $accountQuery->where('field_id', $filters['field_id']);
            });
        })->when(!empty($filters['center_id']), function ($q) use ($filters) {
            $q->whereHas('TXAccount', function ($accountQuery) use ($filters) {
                $accountQuery->where('center_id', $filters['center_id']);
            });
        })->when(!empty($filters['category_id']), function ($q) use ($filters) {
            $q->whereHas('TXAccount', function ($accountQuery) use ($filters) {
                $accountQuery->where('category_id', $filters['category_id']);
            });
        });

        return $query->latest('created_at')->get()->map(function ($item) use ($sourceType) {
            $txAccount = $item->TXAccount;
            $rxAccount = $item->RXAccount;

            return [
                'id' => $sourceType . '_' . $item->id,
                'source_id' => $item->id,
                'source_type' => $sourceType,
                'transaction_type' => 'transfer',
                'date' => $item->created_at,
                'amount' => (int) $item->amount,
                'description' => $item->description,
                'acc_no' => $txAccount?->acc_no,
                'client' => $this->asClientEntity($txAccount?->ClientRegistration),
                'account' => null,
                'from_account' => $this->asClientAccountEntity($txAccount),
                'to_account' => $this->asClientAccountEntity($rxAccount),
                'field' => $this->asNamedEntity($txAccount?->Field),
                'center' => $this->asNamedEntity($txAccount?->Center),
                'category' => $this->asNamedEntity($txAccount?->Category, true),
                'author' => $this->asUserEntity($item->Author),
                'approver' => $this->asUserEntity($item->Approver),
                'approved_at' => $item->approved_at,
                'is_approved' => (bool) $item->is_approved,
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at,
                'meta' => [
                    'tx_client' => $this->asClientEntity($txAccount?->ClientRegistration),
                    'rx_client' => $this->asClientEntity($rxAccount?->ClientRegistration),
                    'tx_prev_balance' => (int) $item->tx_prev_balance,
                    'tx_balance' => (int) $item->tx_balance,
                    'rx_prev_balance' => (int) $item->rx_prev_balance,
                    'rx_balance' => (int) $item->rx_balance,
                ],
            ];
        });
    }

    private function fetchIncomes(array $filters): Collection
    {
        if ($this->hasDimensionFilters($filters) || $this->hasApprovalOnlyFilters($filters)) {
            return collect();
        }

        $query = Income::query()
            ->with([
                'Account:id,name,is_default',
                'IncomeCategory:id,name,is_default',
                'Author:id,name',
            ])
            ->whereBetween('date', $filters['date_range']);

        $this->applyCommonFilters($query, $filters);
        $this->applyAccountFilter($query, $filters);

        return $query->latest('date')->get()->map(function ($item) {
            return [
                'id' => 'income_' . $item->id,
                'source_id' => $item->id,
                'source_type' => 'income',
                'transaction_type' => 'income',
                'date' => $item->date,
                'amount' => (int) $item->amount,
                'description' => $item->description,
                'acc_no' => null,
                'client' => null,
                'account' => $this->asNamedEntity($item->Account, true),
                'field' => null,
                'center' => null,
                'category' => $this->asNamedEntity($item->IncomeCategory, true),
                'author' => $this->asUserEntity($item->Author),
                'approver' => null,
                'approved_at' => null,
                'is_approved' => null,
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at,
                'meta' => [
                    'previous_balance' => (int) $item->previous_balance,
                    'balance' => (int) $item->balance,
                ],
            ];
        });
    }

    private function fetchExpenses(array $filters): Collection
    {
        if ($this->hasDimensionFilters($filters) || $this->hasApprovalOnlyFilters($filters)) {
            return collect();
        }

        $query = Expense::query()
            ->with([
                'Account:id,name,is_default',
                'ExpenseCategory:id,name,is_default',
                'Author:id,name',
            ])
            ->whereBetween('date', $filters['date_range']);

        $this->applyCommonFilters($query, $filters);
        $this->applyAccountFilter($query, $filters);

        return $query->latest('date')->get()->map(function ($item) {
            return [
                'id' => 'expense_' . $item->id,
                'source_id' => $item->id,
                'source_type' => 'expense',
                'transaction_type' => 'expense',
                'date' => $item->date,
                'amount' => (int) $item->amount,
                'description' => $item->description,
                'acc_no' => null,
                'client' => null,
                'account' => $this->asNamedEntity($item->Account, true),
                'field' => null,
                'center' => null,
                'category' => $this->asNamedEntity($item->ExpenseCategory, true),
                'author' => $this->asUserEntity($item->Author),
                'approver' => null,
                'approved_at' => null,
                'is_approved' => null,
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at,
                'meta' => [
                    'previous_balance' => (int) $item->previous_balance,
                    'balance' => (int) $item->balance,
                ],
            ];
        });
    }

    private function fetchAccountTransfers(array $filters): Collection
    {
        if ($this->hasDimensionFilters($filters) || $this->hasApprovalOnlyFilters($filters)) {
            return collect();
        }

        $query = AccountTransfer::query()
            ->with([
                'TxAccount:id,name,is_default',
                'RxAccount:id,name,is_default',
                'Author:id,name',
            ])
            ->whereBetween('date', $filters['date_range']);

        $this->applyCommonFilters($query, $filters);
        $query->when(!empty($filters['account_id']), function ($q) use ($filters) {
            $q->where(function ($orQuery) use ($filters) {
                $orQuery->where('tx_acc_id', $filters['account_id'])
                    ->orWhere('rx_acc_id', $filters['account_id']);
            });
        });

        return $query->latest('date')->get()->map(function ($item) {
            return [
                'id' => 'account_transfer_' . $item->id,
                'source_id' => $item->id,
                'source_type' => 'account_transfer',
                'transaction_type' => 'transfer',
                'date' => $item->date,
                'amount' => (int) $item->amount,
                'description' => $item->description,
                'acc_no' => null,
                'client' => null,
                'account' => null,
                'from_account' => $this->asNamedEntity($item->TxAccount, true),
                'to_account' => $this->asNamedEntity($item->RxAccount, true),
                'field' => null,
                'center' => null,
                'category' => null,
                'author' => $this->asUserEntity($item->Author),
                'approver' => null,
                'approved_at' => null,
                'is_approved' => null,
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at,
                'meta' => [
                    'tx_prev_balance' => (int) $item->tx_prev_balance,
                    'tx_balance' => (int) $item->tx_balance,
                    'rx_prev_balance' => (int) $item->rx_prev_balance,
                    'rx_balance' => (int) $item->rx_balance,
                ],
            ];
        });
    }

    private function fetchAccountWithdrawals(array $filters): Collection
    {
        if ($this->hasDimensionFilters($filters) || $this->hasApprovalOnlyFilters($filters)) {
            return collect();
        }

        $query = AccountWithdrawal::query()
            ->with([
                'Account:id,name,is_default',
                'Author:id,name',
            ])
            ->whereBetween('date', $filters['date_range']);

        $this->applyCommonFilters($query, $filters);
        $this->applyAccountFilter($query, $filters);

        return $query->latest('date')->get()->map(function ($item) {
            return [
                'id' => 'account_withdrawal_' . $item->id,
                'source_id' => $item->id,
                'source_type' => 'account_withdrawal',
                'transaction_type' => 'withdrawal',
                'date' => $item->date,
                'amount' => (int) $item->amount,
                'description' => $item->description,
                'acc_no' => null,
                'client' => null,
                'account' => $this->asNamedEntity($item->Account, true),
                'field' => null,
                'center' => null,
                'category' => null,
                'author' => $this->asUserEntity($item->Author),
                'approver' => null,
                'approved_at' => null,
                'is_approved' => null,
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at,
                'meta' => [
                    'previous_balance' => (int) $item->previous_balance,
                    'balance' => (int) $item->balance,
                ],
            ];
        });
    }

    private function applyCommonFilters(Builder $query, array $filters): void
    {
        $query->when(!empty($filters['creator_id']), function ($q) use ($filters) {
            $q->where('creator_id', $filters['creator_id']);
        });
    }

    private function applyFieldCenterFilters(Builder $query, array $filters): void
    {
        $query->when(!empty($filters['field_id']), function ($q) use ($filters) {
            $q->where('field_id', $filters['field_id']);
        })->when(!empty($filters['center_id']), function ($q) use ($filters) {
            $q->where('center_id', $filters['center_id']);
        });
    }

    private function applyDimensionFilters(Builder $query, array $filters): void
    {
        $query->when(!empty($filters['field_id']), function ($q) use ($filters) {
            $q->where('field_id', $filters['field_id']);
        })->when(!empty($filters['center_id']), function ($q) use ($filters) {
            $q->where('center_id', $filters['center_id']);
        })->when(!empty($filters['category_id']), function ($q) use ($filters) {
            $q->where('category_id', $filters['category_id']);
        });
    }

    private function applyAccountFilter(Builder $query, array $filters): void
    {
        $query->when(!empty($filters['account_id']), function ($q) use ($filters) {
            $q->where('account_id', $filters['account_id']);
        });
    }

    private function applyApprovalFilters(Builder $query, array $filters): void
    {
        $query->when(!empty($filters['approved_by']), function ($q) use ($filters) {
            $q->where('approved_by', $filters['approved_by']);
        });

        if ($filters['approval_status'] === 'approved') {
            $query->where('is_approved', true);
        } elseif ($filters['approval_status'] === 'pending') {
            $query->where('is_approved', false);
        }
    }

    private function applyLoanRegistrationApprovalFilters(Builder $query, array $filters): void
    {
        $query->when(!empty($filters['approved_by']), function ($q) use ($filters) {
            $q->where(function ($approvalQuery) use ($filters) {
                $approvalQuery->where('approved_by', $filters['approved_by'])
                    ->orWhere('loan_approved_by', $filters['approved_by']);
            });
        });

        if ($filters['approval_status'] === 'approved') {
            $query->where('is_approved', true);
        } elseif ($filters['approval_status'] === 'pending') {
            $query->where('is_approved', false);
        }
    }

    private function applyLoanGivenDateFilters(Builder $query, array $filters): void
    {
        [$startDate, $endDate] = $filters['date_range'];

        $query->where(function ($dateQuery) use ($startDate, $endDate) {
            $dateQuery->whereBetween('is_loan_approved_at', [$startDate, $endDate])
                ->orWhere(function ($fallbackStartDateQuery) use ($startDate, $endDate) {
                    $fallbackStartDateQuery->whereNull('is_loan_approved_at')
                        ->whereBetween('start_date', [$startDate, $endDate]);
                })
                ->orWhere(function ($fallbackCreatedAtQuery) use ($startDate, $endDate) {
                    $fallbackCreatedAtQuery->whereNull('is_loan_approved_at')
                        ->whereNull('start_date')
                        ->whereBetween('created_at', [$startDate, $endDate]);
                });
        });
    }

    private function hasDimensionFilters(array $filters): bool
    {
        return !empty($filters['field_id']) || !empty($filters['center_id']) || !empty($filters['category_id']);
    }

    private function hasApprovalOnlyFilters(array $filters): bool
    {
        return !empty($filters['approved_by']) || in_array($filters['approval_status'], ['approved', 'pending'], true);
    }

    private function asNamedEntity($item, bool $withDefault = false): ?array
    {
        if (!$item) {
            return null;
        }

        $response = [
            'id' => $item->id,
            'name' => $item->name,
        ];

        if ($withDefault) {
            $response['is_default'] = (bool) ($item->is_default ?? false);
        }

        return $response;
    }

    private function asUserEntity($user): ?array
    {
        if (!$user) {
            return null;
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
        ];
    }

    private function asClientEntity($client): ?array
    {
        if (!$client) {
            return null;
        }

        return [
            'id' => $client->id,
            'name' => $client->name,
            'acc_no' => $client->acc_no ?? null,
        ];
    }

    private function asClientAccountEntity($account): ?array
    {
        if (!$account) {
            return null;
        }

        return [
            'id' => $account->id,
            'acc_no' => $account->acc_no,
            'client' => $this->asClientEntity($account->ClientRegistration ?? null),
        ];
    }
}
