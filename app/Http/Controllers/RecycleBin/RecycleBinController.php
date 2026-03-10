<?php

namespace App\Http\Controllers\RecycleBin;

use App\Http\Controllers\Controller;
use App\Models\Audit\AuditReportMeta;
use App\Models\Audit\AuditReportPage;
use App\Models\accounts\Account;
use App\Models\accounts\Expense;
use App\Models\accounts\ExpenseCategory;
use App\Models\accounts\Income;
use App\Models\accounts\IncomeCategory;
use App\Models\category\Category;
use App\Models\category\CategoryConfig;
use App\Models\center\Center;
use App\Models\client\ClientRegistration;
use App\Models\client\LoanAccount;
use App\Models\client\SavingAccount;
use App\Models\Collections\LoanCollection;
use App\Models\Collections\SavingCollection;
use App\Models\field\Field;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RecycleBinController extends Controller
{
    private const MAX_PER_PAGE = 200;

    /**
     * Instantiate a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('can:recycle_bin_view')->only('index', 'folders', 'items');
        $this->middleware('can:recycle_bin_restore')->only('restore');
        $this->middleware('can:recycle_bin_force_delete')->only('forceDelete');
    }

    /**
     * Backward-compatible alias for module folder summaries.
     */
    public function index(Request $request)
    {
        return $this->folders($request);
    }

    /**
     * Display recycle bin modules as folders with summary info.
     */
    public function folders(Request $request)
    {
        $typeConfigs = $this->typeConfigs();
        $search = trim((string) $request->query('search', ''));
        $deletedFrom = $this->parseDate($request->query('deleted_from'));
        $deletedTo = $this->parseDate($request->query('deleted_to'));
        $folders = collect($typeConfigs)->map(function ($config, $type) use ($search, $deletedFrom, $deletedTo) {
            $query = $this->buildTypeQuery($config, $search, $deletedFrom, $deletedTo);
            $totalItems = (clone $query)->count();
            $lastDeletedAtRaw = (clone $query)->max('deleted_at');
            $lastDeletedAt = !empty($lastDeletedAtRaw) ? Carbon::parse($lastDeletedAtRaw) : null;

            return [
                'type' => $type,
                'label' => $config['label'],
                'total_items' => $totalItems,
                'last_deleted_at' => $lastDeletedAt?->toIso8601String(),
                'last_deleted_at_unix' => $lastDeletedAt?->timestamp ?? 0,
            ];
        })->values();

        return create_response(null, [
            'folders' => $folders,
            'types' => collect($typeConfigs)->map(function ($config, $type) {
                return [
                    'value' => $type,
                    'label' => $config['label'],
                ];
            })->values(),
            'filters' => [
                'search' => $search,
                'deleted_from' => $deletedFrom?->toDateString(),
                'deleted_to' => $deletedTo?->toDateString(),
            ],
        ]);
    }

    /**
     * Display soft deleted records for one module type.
     */
    public function items(Request $request)
    {
        $typeConfigs = $this->typeConfigs();
        $type = trim((string) $request->query('type'));

        if (empty($type)) {
            return create_validation_error_response(__('customValidations.recycle_bin.type_required'), 'type', 422);
        }

        if (!isset($typeConfigs[$type])) {
            return create_validation_error_response(__('customValidations.recycle_bin.invalid_type_requested'), 'type', 422);
        }

        $search = trim((string) $request->query('search', ''));
        $deletedFrom = $this->parseDate($request->query('deleted_from'));
        $deletedTo = $this->parseDate($request->query('deleted_to'));
        $canRestorePermission = auth()->user()->can('recycle_bin_restore');
        $canForceDeletePermission = auth()->user()->can('recycle_bin_force_delete');
        $rows = $this->collectTypeRows(
            $type,
            $typeConfigs[$type],
            $search,
            $deletedFrom,
            $deletedTo,
            $canRestorePermission,
            $canForceDeletePermission
        )->sortByDesc('deleted_at_unix')
            ->values();

        $fetchAll = filter_var($request->query('all', false), FILTER_VALIDATE_BOOLEAN);
        $total = $rows->count();
        $items = [];
        $currentPage = 1;
        $perPage = $total > 0 ? $total : 1;
        $lastPage = 1;

        if ($fetchAll) {
            $items = $rows
                ->map(function ($row) {
                    return Arr::except($row, ['deleted_at_unix']);
                })
                ->all();
        } else {
            $perPage = min(max((int) $request->query('per_page', 30), 1), self::MAX_PER_PAGE);
            $currentPage = max((int) $request->query('page', 1), 1);
            $lastPage = (int) ceil($total / $perPage);
            $items = $rows->forPage($currentPage, $perPage)
                ->values()
                ->map(function ($row) {
                    return Arr::except($row, ['deleted_at_unix']);
                })
                ->all();
        }

        return create_response(null, [
            'module' => [
                'value' => $type,
                'label' => $typeConfigs[$type]['label'],
            ],
            'items' => $items,
            'filters' => [
                'type' => $type,
                'search' => $search,
                'deleted_from' => $deletedFrom?->toDateString(),
                'deleted_to' => $deletedTo?->toDateString(),
                'all' => $fetchAll,
            ],
            'pagination' => [
                'current_page' => $currentPage,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => $lastPage,
            ],
        ]);
    }

    /**
     * Restore a soft deleted record.
     */
    public function restore(string $type, string $id)
    {
        $typeConfigs = $this->typeConfigs();
        if (!isset($typeConfigs[$type])) {
            return create_validation_error_response(__('customValidations.recycle_bin.invalid_type'), 'type', 404);
        }

        $modelClass = $typeConfigs[$type]['model'];
        $record = $modelClass::withTrashed()->find($id);

        if (!$record || !$record->trashed()) {
            return create_validation_error_response(__('customValidations.recycle_bin.record_not_found'), 'id', 404);
        }

        $restoreError = $this->validateRestoreDependencies($type, $record);
        if (!empty($restoreError)) {
            return create_validation_error_response($restoreError, 'message', 422);
        }

        try {
            DB::transaction(function () use ($type, $record) {
                $this->restoreByType($type, $record);
            });
        } catch (QueryException $exception) {
            return create_validation_error_response(
                $this->resolveQueryErrorMessage($exception, __('customValidations.recycle_bin.restore_conflict')),
                'message',
                422
            );
        } catch (\Throwable $throwable) {
            return create_validation_error_response(__('customValidations.recycle_bin.restore_failed'), 'message', 422);
        }

        return create_response(__('customValidations.recycle_bin.restore_success'));
    }

    /**
     * Permanently delete a soft deleted record.
     */
    public function forceDelete(string $type, string $id)
    {
        $typeConfigs = $this->typeConfigs();
        if (!isset($typeConfigs[$type])) {
            return create_validation_error_response(__('customValidations.recycle_bin.invalid_type'), 'type', 404);
        }

        $modelClass = $typeConfigs[$type]['model'];
        $record = $modelClass::withTrashed()->find($id);

        if (!$record || !$record->trashed()) {
            return create_validation_error_response(__('customValidations.recycle_bin.record_not_found'), 'id', 404);
        }

        if (!$this->canForceDelete($type, $record)) {
            return create_validation_error_response(__('customValidations.recycle_bin.force_delete_not_allowed'), 'message', 422);
        }

        try {
            DB::transaction(function () use ($type, $record) {
                $this->forceDeleteByType($type, $record);
            });
        } catch (QueryException $exception) {
            return create_validation_error_response(
                $this->resolveQueryErrorMessage($exception, __('customValidations.recycle_bin.force_delete_conflict')),
                'message',
                422
            );
        } catch (\Throwable $throwable) {
            return create_validation_error_response(__('customValidations.recycle_bin.force_delete_failed'), 'message', 422);
        }

        return create_response(__('customValidations.recycle_bin.force_delete_success'));
    }

    /**
     * Build supported recycle bin type configuration.
     */
    private function typeConfigs(): array
    {
        return [
            'field' => [
                'label' => __('customValidations.recycle_bin.type.field'),
                'model' => Field::class,
                'search_columns' => ['name', 'description'],
                'with' => ['Author:id,name', 'FieldActionHistory.Author:id,name'],
                'display' => fn(Field $record) => $record->name,
                'metadata' => fn(Field $record) => [
                    'description' => $record->description,
                    'creator' => $record->Author?->name,
                ],
                'deleted_by' => fn(Field $record) => $this->deletedByFromHistory($record->FieldActionHistory),
            ],
            'center' => [
                'label' => __('customValidations.recycle_bin.type.center'),
                'model' => Center::class,
                'search_columns' => ['name', 'description'],
                'with' => ['Author:id,name', 'Field:id,name', 'CenterActionHistory.Author:id,name'],
                'display' => fn(Center $record) => $record->name,
                'metadata' => fn(Center $record) => [
                    'field' => $record->Field?->name,
                    'description' => $record->description,
                    'creator' => $record->Author?->name,
                ],
                'deleted_by' => fn(Center $record) => $this->deletedByFromHistory($record->CenterActionHistory),
            ],
            'category' => [
                'label' => __('customValidations.recycle_bin.type.category'),
                'model' => Category::class,
                'search_columns' => ['name', 'group', 'description'],
                'with' => ['Author:id,name', 'CategoryActionHistory.Author:id,name'],
                'display' => fn(Category $record) => $record->name,
                'metadata' => fn(Category $record) => [
                    'group' => $record->group,
                    'saving' => (bool) $record->saving,
                    'loan' => (bool) $record->loan,
                    'creator' => $record->Author?->name,
                ],
                'deleted_by' => fn(Category $record) => $this->deletedByFromHistory($record->CategoryActionHistory),
            ],
            'client_registration' => [
                'label' => __('customValidations.recycle_bin.type.client_registration'),
                'model' => ClientRegistration::class,
                'search_columns' => ['acc_no', 'name', 'nid', 'primary_phone'],
                'with' => ['Author:id,name', 'Field:id,name', 'Center:id,name', 'ClientRegistrationActionHistory.Author:id,name'],
                'display' => fn(ClientRegistration $record) => trim("{$record->acc_no} - {$record->name}", ' -'),
                'image' => fn(ClientRegistration $record) => $record->image_uri,
                'metadata' => fn(ClientRegistration $record) => [
                    'account_no' => $record->acc_no,
                    'name' => $record->name,
                    'field' => $record->Field?->name,
                    'center' => $record->Center?->name,
                ],
                'deleted_by' => fn(ClientRegistration $record) => $this->deletedByFromHistory($record->ClientRegistrationActionHistory),
            ],
            'saving_account' => [
                'label' => __('customValidations.recycle_bin.type.saving_account'),
                'model' => SavingAccount::class,
                'search_columns' => ['acc_no', 'description'],
                'with' => ['Author:id,name', 'Field:id,name', 'Center:id,name', 'Category:id,name,is_default', 'ClientRegistration:id,name,acc_no,image_uri', 'SavingAccountActionHistory.Author:id,name'],
                'display' => fn(SavingAccount $record) => $record->acc_no,
                'image' => fn(SavingAccount $record) => $record->ClientRegistration?->image_uri,
                'metadata' => fn(SavingAccount $record) => [
                    'field' => $record->Field?->name,
                    'center' => $record->Center?->name,
                    'client' => $record->ClientRegistration?->name,
                    'category' => $record->Category?->name,
                    'balance' => $record->balance,
                ],
                'deleted_by' => fn(SavingAccount $record) => $this->deletedByFromHistory($record->SavingAccountActionHistory),
            ],
            'loan_account' => [
                'label' => __('customValidations.recycle_bin.type.loan_account'),
                'model' => LoanAccount::class,
                'search_columns' => ['acc_no', 'description'],
                'with' => ['Author:id,name', 'Field:id,name', 'Center:id,name', 'Category:id,name,is_default', 'ClientRegistration:id,name,acc_no,image_uri', 'LoanAccountActionHistory.Author:id,name'],
                'display' => fn(LoanAccount $record) => $record->acc_no,
                'image' => fn(LoanAccount $record) => $record->ClientRegistration?->image_uri,
                'metadata' => fn(LoanAccount $record) => [
                    'field' => $record->Field?->name,
                    'center' => $record->Center?->name,
                    'client' => $record->ClientRegistration?->name,
                    'category' => $record->Category?->name,
                    'balance' => $record->balance,
                ],
                'deleted_by' => fn(LoanAccount $record) => $this->deletedByFromHistory($record->LoanAccountActionHistory),
            ],
            'account' => [
                'label' => __('customValidations.recycle_bin.type.account'),
                'model' => Account::class,
                'search_columns' => ['name', 'acc_no', 'acc_details'],
                'with' => ['Author:id,name', 'AccountActionHistory.Author:id,name'],
                'display' => fn(Account $record) => $record->name,
                'metadata' => fn(Account $record) => [
                    'account_no' => $record->acc_no,
                    'details' => $record->acc_details,
                    'default' => (bool) $record->is_default,
                ],
                'deleted_by' => fn(Account $record) => $this->deletedByFromHistory($record->AccountActionHistory),
            ],
            'income_category' => [
                'label' => __('customValidations.recycle_bin.type.income_category'),
                'model' => IncomeCategory::class,
                'search_columns' => ['name', 'description'],
                'with' => ['Author:id,name'],
                'display' => fn(IncomeCategory $record) => $record->name,
                'metadata' => fn(IncomeCategory $record) => [
                    'description' => $record->description,
                    'default' => (bool) $record->is_default,
                    'creator' => $record->Author?->name,
                ],
                'deleted_by' => fn(IncomeCategory $record) => null,
            ],
            'expense_category' => [
                'label' => __('customValidations.recycle_bin.type.expense_category'),
                'model' => ExpenseCategory::class,
                'search_columns' => ['name', 'description'],
                'with' => ['Author:id,name'],
                'display' => fn(ExpenseCategory $record) => $record->name,
                'metadata' => fn(ExpenseCategory $record) => [
                    'description' => $record->description,
                    'default' => (bool) $record->is_default,
                    'creator' => $record->Author?->name,
                ],
                'deleted_by' => fn(ExpenseCategory $record) => null,
            ],
            'income' => [
                'label' => __('customValidations.recycle_bin.type.income'),
                'model' => Income::class,
                'search_columns' => ['description'],
                'with' => ['Author:id,name', 'Account:id,name', 'IncomeCategory:id,name,is_default', 'IncomeActionHistory.Author:id,name'],
                'display' => fn(Income $record) => "{$record->amount}",
                'metadata' => fn(Income $record) => [
                    'account' => $record->Account?->name,
                    'category' => $record->IncomeCategory?->name,
                    'amount' => $record->amount,
                    'date' => $record->date,
                ],
                'deleted_by' => fn(Income $record) => $this->deletedByFromHistory($record->IncomeActionHistory),
            ],
            'expense' => [
                'label' => __('customValidations.recycle_bin.type.expense'),
                'model' => Expense::class,
                'search_columns' => ['description'],
                'with' => ['Author:id,name', 'Account:id,name', 'ExpenseCategory:id,name,is_default', 'ExpenseActionHistory.Author:id,name'],
                'display' => fn(Expense $record) => "{$record->amount}",
                'metadata' => fn(Expense $record) => [
                    'account' => $record->Account?->name,
                    'category' => $record->ExpenseCategory?->name,
                    'amount' => $record->amount,
                    'date' => $record->date,
                ],
                'deleted_by' => fn(Expense $record) => $this->deletedByFromHistory($record->ExpenseActionHistory),
            ],
            'staff' => [
                'label' => __('customValidations.recycle_bin.type.staff'),
                'model' => User::class,
                'search_columns' => ['name', 'email', 'phone'],
                'with' => ['roles:id,name', 'UserActionHistory.Author:id,name'],
                'display' => fn(User $record) => $record->name,
                'image' => fn(User $record) => $record->image_uri,
                'metadata' => fn(User $record) => [
                    'email' => $record->email,
                    'phone' => $record->phone,
                    'role' => $record->roles->first()?->name,
                ],
                'deleted_by' => fn(User $record) => $this->deletedByFromHistory($record->UserActionHistory),
            ],
            'audit_report_meta' => [
                'label' => __('customValidations.recycle_bin.type.audit_report_meta'),
                'model' => AuditReportMeta::class,
                'search_columns' => ['meta_key', 'meta_value'],
                'with' => ['Author:id,name', 'AuditReportPage:id,name,is_default', 'AuditReportMetaActionHistory.Author:id,name'],
                'display' => fn(AuditReportMeta $record) => $record->meta_key,
                'metadata' => fn(AuditReportMeta $record) => [
                    'page' => $record->AuditReportPage?->name,
                    'meta_key' => $record->meta_key,
                    'column_no' => $record->column_no,
                ],
                'deleted_by' => fn(AuditReportMeta $record) => $this->deletedByFromHistory($record->AuditReportMetaActionHistory),
            ],
        ];
    }

    /**
     * Fetch deleted rows for one type.
     */
    private function collectTypeRows(
        string $type,
        array $config,
        string $search,
        ?Carbon $deletedFrom,
        ?Carbon $deletedTo,
        bool $canRestorePermission,
        bool $canForceDeletePermission
    ): Collection {
        $query = $this->buildTypeQuery($config, $search, $deletedFrom, $deletedTo);
        if (!empty($config['with'])) {
            $query->with($config['with']);
        }

        $records = $query->orderBy('deleted_at', 'DESC')->get();

        return $records->map(function (Model $record) use ($type, $config, $canRestorePermission, $canForceDeletePermission) {
            $displayName = value($config['display'], $record);
            $metadata = value($config['metadata'], $record);
            $deletedBy = value($config['deleted_by'], $record);
            $imageUri = value($config['image'] ?? null, $record);
            if (empty($imageUri)) {
                $imageUri = $record->getAttribute('image_uri') ?? $record->getAttribute('image');
            }
            $restoreError = $this->validateRestoreDependencies($type, $record);

            return [
                'id' => $record->id,
                'type' => $type,
                'type_label' => $config['label'],
                'display_name' => !empty($displayName)
                    ? $displayName
                    : __('customValidations.recycle_bin.id_fallback', ['id' => $record->id]),
                'deleted_at' => $record->deleted_at?->toIso8601String(),
                'deleted_at_unix' => $record->deleted_at?->timestamp ?? 0,
                'deleted_by' => $deletedBy,
                'image_uri' => !empty($imageUri) ? $imageUri : null,
                'metadata' => $metadata ?? [],
                'restorable' => $canRestorePermission && empty($restoreError),
                'force_deletable' => $canForceDeletePermission && $this->canForceDelete($type, $record),
            ];
        });
    }

    /**
     * Build one trashed query with common filters.
     */
    private function buildTypeQuery(
        array $config,
        string $search,
        ?Carbon $deletedFrom,
        ?Carbon $deletedTo
    )
    {
        $modelClass = $config['model'];
        $query = $modelClass::query()->onlyTrashed();

        if (!empty($search)) {
            $query->where(function ($searchQuery) use ($search, $config) {
                if (is_numeric($search)) {
                    $searchQuery->orWhere('id', (int) $search);
                }

                foreach ($config['search_columns'] ?? [] as $column) {
                    $searchQuery->orWhere($column, 'LIKE', "%{$search}%");
                }
            });
        }

        if (!empty($deletedFrom)) {
            $query->whereDate('deleted_at', '>=', $deletedFrom->toDateString());
        }
        if (!empty($deletedTo)) {
            $query->whereDate('deleted_at', '<=', $deletedTo->toDateString());
        }

        return $query;
    }

    /**
     * Parse date from request filter.
     */
    private function parseDate(mixed $value): ?Carbon
    {
        if (empty($value)) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable $throwable) {
            return null;
        }
    }

    /**
     * Check if one record can be restored.
     */
    private function validateRestoreDependencies(string $type, Model $record): ?string
    {
        return match ($type) {
            'center' => $this->validateParentModels([
                ['model' => Field::class, 'id' => $record->field_id, 'label' => __('customValidations.recycle_bin.label.field')],
            ]),
            'client_registration' => $this->validateParentModels([
                ['model' => Field::class, 'id' => $record->field_id, 'label' => __('customValidations.recycle_bin.label.field')],
                ['model' => Center::class, 'id' => $record->center_id, 'label' => __('customValidations.recycle_bin.label.center')],
            ]),
            'saving_account' => $this->validateParentModels([
                ['model' => Field::class, 'id' => $record->field_id, 'label' => __('customValidations.recycle_bin.label.field')],
                ['model' => Center::class, 'id' => $record->center_id, 'label' => __('customValidations.recycle_bin.label.center')],
                ['model' => Category::class, 'id' => $record->category_id, 'label' => __('customValidations.recycle_bin.label.category')],
                ['model' => ClientRegistration::class, 'id' => $record->client_registration_id, 'label' => __('customValidations.recycle_bin.label.client_registration')],
            ]),
            'loan_account' => $this->validateParentModels([
                ['model' => Field::class, 'id' => $record->field_id, 'label' => __('customValidations.recycle_bin.label.field')],
                ['model' => Center::class, 'id' => $record->center_id, 'label' => __('customValidations.recycle_bin.label.center')],
                ['model' => Category::class, 'id' => $record->category_id, 'label' => __('customValidations.recycle_bin.label.category')],
                ['model' => ClientRegistration::class, 'id' => $record->client_registration_id, 'label' => __('customValidations.recycle_bin.label.client_registration')],
            ]),
            'income' => $this->validateParentModels([
                ['model' => Account::class, 'id' => $record->account_id, 'label' => __('customValidations.recycle_bin.label.account')],
                ['model' => IncomeCategory::class, 'id' => $record->income_category_id, 'label' => __('customValidations.recycle_bin.label.income_category')],
            ]),
            'expense' => $this->validateParentModels([
                ['model' => Account::class, 'id' => $record->account_id, 'label' => __('customValidations.recycle_bin.label.account')],
                ['model' => ExpenseCategory::class, 'id' => $record->expense_category_id, 'label' => __('customValidations.recycle_bin.label.expense_category')],
            ]),
            'audit_report_meta' => $this->validateParentModels([
                ['model' => AuditReportPage::class, 'id' => $record->audit_report_page_id, 'label' => __('customValidations.recycle_bin.label.audit_report_page')],
            ]),
            default => null,
        };
    }

    /**
     * Validate parent records before restore.
     */
    private function validateParentModels(array $parents): ?string
    {
        foreach ($parents as $parent) {
            $model = $parent['model'];
            $id = $parent['id'];
            $label = $parent['label'];
            $state = $this->modelState($model, $id);

            if ($state === 'missing') {
                return __('customValidations.recycle_bin.parent_missing', ['label' => $label]);
            }

            if ($state === 'trashed') {
                return __('customValidations.recycle_bin.parent_still_trashed', ['label' => $label]);
            }
        }

        return null;
    }

    /**
     * Determine model state with soft delete awareness.
     */
    private function modelState(string $modelClass, mixed $id): string
    {
        if (empty($id)) {
            return 'missing';
        }

        $usesSoftDeletes = in_array(SoftDeletes::class, class_uses_recursive($modelClass));
        if (!$usesSoftDeletes) {
            return $modelClass::query()->whereKey($id)->exists() ? 'active' : 'missing';
        }

        $record = $modelClass::withTrashed()->find($id);
        if (!$record) {
            return 'missing';
        }

        return $record->trashed() ? 'trashed' : 'active';
    }

    /**
     * Execute restore logic by type.
     */
    private function restoreByType(string $type, Model $record): void
    {
        switch ($type) {
            case 'client_registration':
                $this->restoreClientRegistration($record);
                break;
            case 'saving_account':
                $this->restoreSavingAccount($record);
                break;
            case 'loan_account':
                $this->restoreLoanAccount($record);
                break;
            case 'income':
                $this->restoreIncome($record);
                break;
            case 'expense':
                $this->restoreExpense($record);
                break;
            case 'category':
                $this->restoreCategory($record);
                break;
            default:
                $record->restore();
                break;
        }
    }

    /**
     * Execute force delete logic by type.
     */
    private function forceDeleteByType(string $type, Model $record): void
    {
        $record->forceDelete();
    }

    /**
     * Check whether one record can be force deleted.
     */
    private function canForceDelete(string $type, Model $record): bool
    {
        if (in_array($type, ['account', 'category', 'income_category', 'expense_category'])) {
            return !(bool) ($record->is_default ?? false);
        }

        return true;
    }

    /**
     * Resolve common SQL errors into user-facing validation messages.
     */
    private function resolveQueryErrorMessage(QueryException $exception, string $defaultMessage): string
    {
        $errorCode = (string) ($exception->errorInfo[1] ?? '');
        $message = strtolower($exception->getMessage());

        if ($errorCode === '1451' || str_contains($message, 'foreign key constraint fails')) {
            return __('customValidations.recycle_bin.force_delete_blocked_by_related');
        }

        if ($errorCode === '1062' || str_contains($message, 'duplicate entry') || str_contains($message, 'unique constraint')) {
            return __('customValidations.recycle_bin.duplicate_unique_conflict');
        }

        return $defaultMessage;
    }

    /**
     * Resolve deleted by user from action history collection.
     */
    private function deletedByFromHistory(mixed $historyRows): ?array
    {
        $history = collect($historyRows)
            ->where('action_type', 'delete')
            ->sortByDesc('created_at')
            ->first();

        if (empty($history)) {
            return null;
        }

        return [
            'id' => $history->author_id ?? null,
            'name' => $history->Author?->name ?? $history->name ?? null,
        ];
    }

    /**
     * Restore category and ensure config row exists.
     */
    private function restoreCategory(Category $category): void
    {
        $category->restore();
        CategoryConfig::firstOrCreate(['category_id' => $category->id]);
    }

    /**
     * Restore client registration with related soft deleted rows.
     */
    private function restoreClientRegistration(ClientRegistration $registration): void
    {
        $registration->restore();
        SavingAccount::onlyTrashed()->where('client_registration_id', $registration->id)->restore();
        LoanAccount::onlyTrashed()->where('client_registration_id', $registration->id)->restore();
        SavingCollection::onlyTrashed()->where('client_registration_id', $registration->id)->restore();
        LoanCollection::onlyTrashed()->where('client_registration_id', $registration->id)->restore();
    }

    /**
     * Restore saving account with related soft deleted rows.
     */
    private function restoreSavingAccount(SavingAccount $account): void
    {
        $account->restore();
        SavingCollection::onlyTrashed()->where('saving_account_id', $account->id)->restore();
    }

    /**
     * Restore loan account with related soft deleted rows.
     */
    private function restoreLoanAccount(LoanAccount $account): void
    {
        $account->restore();
        LoanCollection::onlyTrashed()->where('loan_account_id', $account->id)->restore();
    }

    /**
     * Restore income and rollback the balance impact of soft delete.
     */
    private function restoreIncome(Income $income): void
    {
        $income->restore();
        Account::find($income->account_id)?->increment('total_deposit', $income->amount);
    }

    /**
     * Restore expense and rollback the balance impact of soft delete.
     */
    private function restoreExpense(Expense $expense): void
    {
        $expense->restore();
        Account::find($expense->account_id)?->decrement('total_withdrawal', $expense->amount);
    }

}
