<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Audit\AuditReportPage;
use Illuminate\Support\Facades\Route;
use App\Models\client\SavingAccountFee;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\AuthController;
use Spatie\Permission\Models\Permission;
use App\Http\Controllers\field\FieldController;
use App\Http\Controllers\staffs\RoleController;
use App\Http\Controllers\staffs\UserController;
use App\Http\Controllers\center\CenterController;
use App\Http\Controllers\accounts\IncomeController;
use App\Http\Controllers\accounts\AccountController;
use App\Http\Controllers\accounts\ExpenseController;
use App\Http\Controllers\config\AppConfigController;
use App\Http\Controllers\Audit\AuditReportController;
use App\Http\Controllers\category\CategoryController;
use App\Http\Controllers\staffs\PermissionController;
use App\Http\Controllers\client\LoanAccountController;
use App\Http\Controllers\dashboard\DashboardController;
use App\Http\Controllers\client\SavingAccountController;
use App\Http\Controllers\Audit\AuditReportMetaController;
use App\Http\Controllers\Audit\AuditReportPageController;
use App\Http\Controllers\config\CategoryConfigController;
use App\Http\Controllers\closing\LoanAccClosingController;
use App\Http\Controllers\accounts\IncomeCategoryController;
use App\Http\Controllers\accounts\AccountTransferController;
use App\Http\Controllers\accounts\ExpenseCategoryController;
use App\Http\Controllers\closing\SavingAccClosingController;
use App\Http\Controllers\client\ClientRegistrationController;
use App\Http\Controllers\transactions\TransactionsController;
use App\Http\Controllers\accounts\AccountWithdrawalController;
use App\Http\Controllers\Collections\LoanCollectionController;
use App\Http\Controllers\Withdrawal\SavingWithdrawalController;
use App\Http\Controllers\Collections\SavingCollectionController;
use App\Http\Controllers\Withdrawal\LoanSavingWithdrawalController;
use App\Http\Controllers\ClientAccountFees\LoanAccountFeesController;
use App\Http\Controllers\ClientAccountFees\SavingAccountFeesController;
use App\Http\Controllers\ClientAccountChecks\LoanAccountCheckController;
use App\Http\Controllers\ClientAccountChecks\SavingAccountCheckController;

// =============================================================================
// ARTISAN UTILITY ROUTES
// =============================================================================
// Quick access endpoints for clearing caches and running maintenance tasks
// These should be disabled or protected in production environments
// =============================================================================

Route::GET('/cache-clear', function () {
    Artisan::call('cache:clear');

    return response('cache cleared');
});
Route::GET('/config-clear', function () {
    Artisan::call('config:clear');

    return response('config cleared');
});
Route::GET('/route-clear', function () {
    Artisan::call('route:clear');

    return response('route cleared');
});
Route::GET('/optimize-clear', function () {
    Artisan::call('optimize:clear');

    return response('optimize cleared');
});
Route::GET('/storage-link', function () {
    Artisan::call('storage:link');

    return response('storage Linked');
});

// =============================================================================
// PUBLIC ROUTES (No Authentication Required)
// =============================================================================
// Middleware: LangCheck - validates and sets application language from request
// Access: Anyone can hit these endpoints
// =============================================================================

Route::group(['middleware' => 'LangCheck'], function () {
    // Authentication & Account Recovery
    Route::controller(AuthController::class)->group(function () {
        Route::POST('login', 'login');
        Route::POST('forget-password', 'forget_password');
        Route::GET('otp-resend/{id}', 'otp_resend');
        Route::POST('account-verification', 'otp_verification');
        Route::PUT('reset-password', 'reset_password');
    });

    // Application Configuration (Public)
    Route::controller(AppConfigController::class)->group(function () {
        Route::GET('app-settings', 'index');
        Route::GET('approvals-config', 'get_all_approvals');
    });
});

// =============================================================================
// 404 HANDLER
// =============================================================================
// Returns JSON response for undefined API endpoints
// =============================================================================

Route::fallback(function () {
    return response()->json([
        'success' => false,
        'message' => 'API endpoint not found.'
    ], 404);
});


// =============================================================================
// PROTECTED ROUTES (Authentication Required)
// =============================================================================
// Middleware Stack:
//   - auth:sanctum: Requires valid API token
//   - verified: Email must be verified
//   - LangCheck: Language validation
//   - activeUser: User account must be active
// =============================================================================

Route::group(['middleware' => ['auth:sanctum', 'verified', 'LangCheck', 'activeUser']], function () {

    // =========================================================================
    // AUTHENTICATION & USER MANAGEMENT
    // =========================================================================

    Route::controller(AuthController::class)->group(function () {
        Route::GET('authorization', 'authorization');
        Route::POST('registration', 'registration');
        Route::POST('logout', 'logout');
        Route::POST('verify-user', 'verify_user');
        Route::PUT('change-password', 'change_password');
        Route::PUT('profile-update', 'profile_update');
    });

    // =========================================================================
    // DASHBOARD & UTILITIES
    // =========================================================================

    Route::GET('dashboard', [DashboardController::class, 'index']);
    Route::GET('users/permissions/{id}', [UserController::class, 'get_user_permissions']);
    Route::GET('permissions/{id}', [PermissionController::class, 'index']);
    Route::GET('users/active', [UserController::class, 'get_active_users']);
    Route::GET('fields/active', [FieldController::class, 'get_active_fields']);
    Route::GET('centers/active', [CenterController::class, 'get_active_Centers']);
    Route::GET('categories/active', [CategoryController::class, 'get_active_Categories']);
    Route::GET('categories/groups', [CategoryController::class, 'get_category_groups']);
    Route::GET('audit/page/get-all-pages', [AuditReportPageController::class, 'get_all_pages']);

    // =========================================================================
    // CLIENT REGISTRATION - Additional Operations
    // =========================================================================

    Route::prefix('client/registration')->group(function () {
        Route::PUT('field-update/{id}', [ClientRegistrationController::class, 'fieldUpdate']);
        Route::PUT('center-update/{id}', [ClientRegistrationController::class, 'centerUpdate']);
        Route::PUT('acc-no-update/{id}', [ClientRegistrationController::class, 'accNoUpdate']);
        Route::PUT('saving/category-update/{id}', [SavingAccountController::class, 'categoryUpdate']);
        Route::PUT('loan/category-update/{id}', [LoanAccountController::class, 'categoryUpdate']);
        Route::GET('count-accounts/{id}', [ClientRegistrationController::class, 'countAccounts']);
        Route::GET('occupations', [ClientRegistrationController::class, 'get_client_occupations']);
        Route::GET('saving/nominee/occupations', [SavingAccountController::class, 'get_nominee_occupations']);
        Route::GET('saving/nominee/relations', [SavingAccountController::class, 'get_nominee_relations']);
        Route::GET('loan/guarantor/occupations', [LoanAccountController::class, 'get_guarantor_occupations']);
        Route::GET('loan/guarantor/relations', [LoanAccountController::class, 'get_guarantor_relations']);
        Route::GET('saving/short-summery/{id}', [SavingAccountController::class, 'get_short_summery']);
        Route::GET('saving/get', [SavingAccountController::class, 'index']);
        Route::GET('loan/get', [LoanAccountController::class, 'index']);
        Route::GET('loan/short-summery/{id}', [LoanAccountController::class, 'get_short_summery']);
        Route::GET('transaction/saving/{id}', [SavingAccountController::class, 'getTransactionalAccInfo']);
        Route::GET('transaction/loan/{id}', [LoanAccountController::class, 'getTransactionalAccInfo']);
    });

    // =========================================================================
    // ACCOUNTS - Additional Operations
    // =========================================================================

    Route::prefix('accounts')->group(function () {
        // Get active data
        Route::GET('active', [AccountController::class, 'get_active_accounts']);
        Route::GET('incomes/categories/active', [IncomeCategoryController::class, 'get_active_categories']);
        Route::GET('expenses/categories/active', [ExpenseCategoryController::class, 'get_active_categories']);

        // Get Account transaction
        Route::GET('transactions/{account_id?}', [AccountController::class, 'get_all_transactions']);
    });

    // =========================================================================
    // STATUS MANAGEMENT
    // =========================================================================
    // Endpoints to activate/deactivate various entities
    // =========================================================================

    Route::PUT('users/change-status/{id}', [UserController::class, 'change_status']);
    Route::PUT('fields/change-status/{id}', [FieldController::class, 'change_status']);
    Route::PUT('centers/change-status/{id}', [CenterController::class, 'change_status']);
    Route::PUT('categories/change-status/{id}', [CategoryController::class, 'change_status']);

    // Accounts
    Route::prefix('accounts')->group(function () {
        Route::PUT('change-status/{id}', [AccountController::class, 'change_status']);
        Route::PUT('incomes/categories/change-status/{id}', [IncomeCategoryController::class, 'change_status']);
        Route::PUT('expenses/categories/change-status/{id}', [ExpenseCategoryController::class, 'change_status']);
    });

    // =========================================================================
    // APPROVAL WORKFLOWS
    // =========================================================================
    // Endpoints for approving/rejecting various requests and applications
    // =========================================================================

    // Client Registration Approvals
    Route::prefix('client/registration')->group(function () {
        Route::GET('saving/active/{client_id}', [SavingAccountController::class, 'activeAccount']);
        Route::GET('saving/pending/{client_id}', [SavingAccountController::class, 'pendingAccount']);
        Route::GET('saving/hold/{client_id}', [SavingAccountController::class, 'holdAccount']);
        Route::GET('saving/closed/{client_id}', [SavingAccountController::class, 'closedAccount']);
        Route::GET('loan/active/{client_id}', [LoanAccountController::class, 'activeAccount']);
        Route::GET('loan/pending/{client_id}', [LoanAccountController::class, 'pendingAccount']);
        Route::GET('loan/hold/{client_id}', [LoanAccountController::class, 'holdAccount']);
        Route::GET('loan/closed/{client_id}', [LoanAccountController::class, 'closedAccount']);
        Route::GET('info', [ClientRegistrationController::class, 'clientInfo']);
        Route::GET('accounts/{field_id?}/{center_id?}', [ClientRegistrationController::class, 'clientAccounts']);
        Route::GET('saving-accounts/{field_id?}/{center_id?}/{category_id?}', [SavingAccountController::class, 'getSavingAccounts']);
        Route::GET('loan-accounts/{field_id?}/{center_id?}/{category_id?}', [ClientRegistrationController::class, 'getLoanAccounts']);
        Route::GET('pending-forms', [ClientRegistrationController::class, 'pending_forms']);
        Route::GET('saving/pending-forms', [SavingAccountController::class, 'pending_forms']);
        Route::GET('loan/pending-forms', [LoanAccountController::class, 'pending_forms']);
        Route::GET('loan/pending-loans', [LoanAccountController::class, 'pending_loans']);

        // Registration Approval Routes
        Route::PUT('approved/{id}', [ClientRegistrationController::class, 'approved']);
        Route::PUT('saving/approved/{id}', [SavingAccountController::class, 'approved']);
        Route::PUT('saving/change-status/{id}', [SavingAccountController::class, 'changeStatus']);
        Route::PUT('loan/approved/{id}', [LoanAccountController::class, 'approved']);
        Route::PUT('loan/loan-approved/{id}', [LoanAccountController::class, 'loan_approved']);
        Route::PUT('loan/change-status/{id}', [LoanAccountController::class, 'changeStatus']);
    });

    // Collection Approvals
    Route::prefix('collection')->group(function () {
        // Approval Routes
        Route::POST('saving/approved', [SavingCollectionController::class, 'approved']);
        Route::POST('loan/approved', [LoanCollectionController::class, 'approved']);
    });

    // Withdrawal Approvals
    Route::prefix('withdrawal')->group(function () {
        // Pending Withdrawal Routes
        Route::GET('saving/pending', [SavingWithdrawalController::class, 'pending_withdrawal']);
        Route::GET('loan-saving/pending', [LoanSavingWithdrawalController::class, 'pending_withdrawal']);

        // Withdrawal Approval Routes
        Route::PUT('saving/approved/{id}', [SavingWithdrawalController::class, 'approved']);
        Route::PUT('loan-saving/approved/{id}', [LoanSavingWithdrawalController::class, 'approved']);
    });

    // Account Closing Approvals
    Route::prefix('closing')->group(function () {
        Route::PUT('saving/approved/{id}', [SavingAccClosingController::class, 'approved']);
        Route::PUT('loan/approved/{id}', [LoanAccClosingController::class, 'approved']);
    });

    // =========================================================================
    // PERMANENT DELETION
    // =========================================================================
    // Force delete endpoints - bypasses soft deletes (use with caution)
    // =========================================================================

    Route::prefix('client')->group(function () {
        Route::DELETE('force-delete/{id}', [ClientRegistrationController::class, 'permanently_destroy']);
        Route::DELETE('saving/force-delete/{id}', [SavingAccountController::class, 'permanently_destroy']);
        Route::DELETE('loan/force-delete/{id}', [LoanAccountController::class, 'permanently_destroy']);
    });

    Route::prefix('collection')->group(function () {
        Route::DELETE('saving/force-delete/{id}', [SavingCollectionController::class, 'permanently_destroy']);
        Route::DELETE('loan/force-delete/{id}', [LoanCollectionController::class, 'permanently_destroy']);
    });

    // =========================================================================
    // TRANSACTIONS
    // =========================================================================
    // Transaction history, creation, approval workflows
    // =========================================================================

    Route::prefix('transactions')->group(function () {
        Route::GET('saving/{id}', [SavingAccountController::class, 'getAllTransaction']);
        Route::GET('loan/{id}', [LoanAccountController::class, 'getAllTransaction']);

        // Store Transactions
        Route::POST('/', [TransactionsController::class, 'store']);
        Route::GET('pending-transactions/{type}', [TransactionsController::class, 'index']);
        Route::DELETE('delete-transactions/{id}/{type}', [TransactionsController::class, 'destroy']);
        Route::GET('approve-transactions/{id}/{type}', [TransactionsController::class, 'approved']);
        Route::GET('approved-transactions/{id}/{type}', [TransactionsController::class, 'getApprovedTransactions']);
    });

    // =========================================================================
    // COLLECTION SHEETS & REPORTS
    // =========================================================================
    // Organized collection reports by category, field, and center
    // Supports both regular and pending collection views
    // =========================================================================

    Route::prefix('collection')->group(function () {
        // Saving Collection Reports
        Route::prefix('saving')->group(function () {
            // Regular Collection
            Route::prefix('regular/collection-sheet')->group(function () {
                Route::GET('/', [SavingCollectionController::class, 'regularCategoryReport']);
                Route::GET('{category_id}', [SavingCollectionController::class, 'regularFieldReport']);
                Route::GET('{category_id}/{field_id}', [SavingCollectionController::class, 'regularCollectionSheet']);
            });
            // Pending Collection
            Route::prefix('pending/collection-sheet')->group(function () {
                Route::GET('/', [SavingCollectionController::class, 'pendingCategoryReport']);
                Route::GET('{category_id}', [SavingCollectionController::class, 'pendingFieldReport']);
                Route::GET('{category_id}/{field_id}', [SavingCollectionController::class, 'pendingCollectionSheet']);
            });
        });

        // Loan Collection Reports
        Route::prefix('loan')->group(function () {
            // Regular Collection
            Route::prefix('regular/collection-sheet')->group(function () {
                Route::GET('/', [LoanCollectionController::class, 'regularCategoryReport']);
                Route::GET('{category_id}', [LoanCollectionController::class, 'regularFieldReport']);
                Route::GET('{category_id}/{field_id}', [LoanCollectionController::class, 'regularCollectionSheet']);
            });
            // Pending COllection
            Route::prefix('pending/collection-sheet')->group(function () {
                Route::GET('/', [LoanCollectionController::class, 'pendingCategoryReport']);
                Route::GET('{category_id}', [LoanCollectionController::class, 'pendingFieldReport']);
                Route::GET('{category_id}/{field_id}', [LoanCollectionController::class, 'pendingCollectionSheet']);
            });
        });
    });

    // =========================================================================
    // API RESOURCE ROUTES (CRUD Operations)
    // =========================================================================
    // Standard REST endpoints: index, store, show, update, destroy
    // Some resources exclude 'show' or include only specific methods
    // =========================================================================

    // Staff & System Management
    Route::apiResource('permissions', PermissionController::class)->only('update');
    Route::apiResource('users', UserController::class)->except('show');
    Route::apiResource('roles', RoleController::class)->except('show');
    Route::apiResource('fields', FieldController::class)->except('show');
    Route::apiResource('centers', CenterController::class)->except('show');
    Route::apiResource('categories', CategoryController::class)->except('show');
    Route::apiResource('saving/check', SavingAccountCheckController::class)->names('saving.check');
    Route::apiResource('loan/check', LoanAccountCheckController::class)->names('loan.check');
    Route::apiResource('saving-collection', SavingCollectionController::class)->except('show');

    // Client Management
    Route::prefix('client/registration')->group(function () {
        Route::apiResource('/', ClientRegistrationController::class)->parameter('', 'registration');
        Route::apiResource('saving', SavingAccountController::class)->names('client.registration.saving');
        Route::apiResource('loan', LoanAccountController::class)->names('client.registration.loan');
    });

    Route::prefix('client/fees')->group(function () {
        Route::apiResource('saving', SavingAccountFeesController::class)->only('index')->names('client.fees.saving');
        Route::apiResource('loan', LoanAccountFeesController::class)->only('index')->names('client.fees.loan');
    });

    // Collections
    Route::prefix('collection')->group(function () {
        Route::apiResource('saving', SavingCollectionController::class)->except('show')->names('collection.saving');
        Route::apiResource('loan', LoanCollectionController::class)->except('show')->names('collection.loan');
    });

    // Withdrawals
    Route::prefix('withdrawal')->group(function () {
        Route::apiResource('saving', SavingWithdrawalController::class)->names('withdrawal.saving');
        Route::apiResource('loan-saving', LoanSavingWithdrawalController::class)->names('withdrawal.loan-saving');
    });

    // Account Closures
    Route::prefix('closing')->group(function () {
        Route::apiResource('saving', SavingAccClosingController::class)->except('update')->names('closing.saving');
        Route::apiResource('loan', LoanAccClosingController::class)->except('update')->names('closing.loan');
    });

    // Financial Accounts (Income & Expenses)
    Route::prefix('accounts')->group(function () {
        Route::apiResource('/', AccountController::class)->except('show')->parameter('', 'account')->names('accounts');
        Route::apiResource('withdrawals', AccountWithdrawalController::class)->except('show')->names('accounts.withdrawals');
        Route::apiResource('transfers', AccountTransferController::class)->only(['index', 'store'])->names('accounts.transfers');

        // Income Routes
        Route::prefix('incomes')->group(function () {
            Route::apiResource('/', IncomeController::class)->except('show')->parameter('', 'income')->names('accounts.incomes');
            Route::apiResource('categories', IncomeCategoryController::class)->except('show')->names('accounts.incomes.categories');
        });

        // Income Routes
        Route::prefix('expenses')->group(function () {
            Route::apiResource('/', ExpenseController::class)->except('show')->parameter('', 'expense')->names('accounts.expenses');
            Route::apiResource('categories', ExpenseCategoryController::class)->except('show')->names('accounts.expenses.categories');
        });
    });

    // Audit Reports
    Route::prefix('audit')->group(function () {
        Route::apiResource('meta', AuditReportMetaController::class)->except('show')->names('audit.meta');
        Route::apiResource('page', AuditReportPageController::class)->except('show')->names('audit.page');
        Route::apiResource('report/co-operative', AuditReportController::class)->only(['index', 'update'])->names('audit.report.co-operative');
    });

    // =========================================================================
    // APPLICATION CONFIGURATION
    // =========================================================================

    Route::controller(AppConfigController::class)->group(function () {
        Route::PUT('app-settings-update', 'app_settings_update');
        Route::PUT('approvals-config-update', 'approvals_update');
        Route::PUT('transfer-transaction-config-update', 'transfer_transaction_update');
    });

    Route::controller(CategoryConfigController::class)->group(function () {
        Route::GET('categories-config', 'get_all_categories_config');
        Route::POST('categories-config/element/{id}', 'get_element_config');
        Route::PUT('categories-config-update', 'config_update');
    });

    // =========================================================================
    // DEVELOPMENT/TESTING ROUTES
    // =========================================================================
    // TODO: Remove or secure this endpoint before production deployment
    // =========================================================================

    Route::POST('/add-permission', function (Request $request) {
        foreach ($request->permissions as $permission) {
            if (count(Permission::where('name', $permission)->get())) {
                continue;
            }

            $newPermission = Permission::create(
                [
                    'name' => $permission,
                    'group_name' => $request->group_name,
                    'guard_name' => 'web',
                ]
            );

            auth()->user()->givePermissionTo($newPermission);
        }

        return response(
            [
                'success' => true,
                'message' => __('customValidations.authorize.successful'),
            ],
            200
        );
    });
});
