<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
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
use App\Http\Controllers\category\CategoryController;
use App\Http\Controllers\staffs\PermissionController;
use App\Http\Controllers\client\LoanAccountController;
use App\Http\Controllers\client\SavingAccountController;
use App\Http\Controllers\Audit\AuditReportMetaController;
use App\Http\Controllers\config\CategoryConfigController;
use App\Http\Controllers\accounts\IncomeCategoryController;
use App\Http\Controllers\accounts\AccountTransferController;
use App\Http\Controllers\accounts\ExpenseCategoryController;
use App\Http\Controllers\client\ClientRegistrationController;
use App\Http\Controllers\accounts\AccountWithdrawalController;
use App\Http\Controllers\Collections\LoanCollectionController;
use App\Http\Controllers\Withdrawal\SavingWithdrawalController;
use App\Http\Controllers\Collections\SavingCollectionController;
use App\Http\Controllers\Withdrawal\LoanSavingWithdrawalController;
use App\Http\Controllers\ClientAccountChecks\LoanAccountCheckController;
use App\Http\Controllers\ClientAccountChecks\SavingAccountCheckController;

/*
 * ------------------------------------------------------------------------
 * Artisan Call Routes
 * ------------------------------------------------------------------------
 *
 * All routes are used by Artisan call
 */

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

/*
 * ------------------------------------------------------------------------
 * Public Routes
 * ------------------------------------------------------------------------
 *
 * All routes are protected by LangCheck middleware
 */
Route::group(['middleware' => 'LangCheck'], function () {
    /*
     * -------------------------------------------------------------------------
     * unAuthenticated Routes
     * -------------------------------------------------------------------------
     *
     * Here is where you can hit unAuthenticated routes
     */
    Route::controller(AuthController::class)->group(function () {
        Route::POST('login', 'login');
        Route::POST('forget-password', 'forget_password');
        Route::GET('otp-resend/{id}', 'otp_resend');
        Route::POST('account-verification', 'otp_verification');
        Route::PUT('reset-password', 'reset_password');
    });
    Route::controller(AppConfigController::class)->group(function () {
        Route::GET('app-settings', 'index');
        Route::GET('approvals-config', 'get_all_approvals');
    });
});

/*
 * -------------------------------------------------------------------------
 * Protected Routes
 * -------------------------------------------------------------------------
 *
 * Here is where you can hit Authenticate routes. All of them are protected
 * by auth Sanctum middleware and email verified
 */
Route::group(['middleware' => ['auth:sanctum', 'verified', 'LangCheck', 'activeUser']], function () {
    /*
     * -------------------------------------------------------------------------
     * Authorization Routes
     * -------------------------------------------------------------------------
     */
    Route::controller(AuthController::class)->group(function () {
        Route::GET('authorization', 'authorization');
        Route::POST('registration', 'registration');
        Route::POST('logout', 'logout');
        Route::PUT('change-password', 'change_password');
        Route::PUT('profile-update', 'profile_update');
    });

    /*
     * -------------------------------------------------------------------------
     * Api Resources Additional Routes
     * -------------------------------------------------------------------------
     *
     * Here you can see all the API routes that have been additionally added to
     * the resource controller
     */
    Route::GET('users/permissions/{id}', [UserController::class, 'get_user_permissions']);
    Route::GET('permissions/{id}', [PermissionController::class, 'index'])->name('permissions.index');
    Route::GET('users/active', [UserController::class, 'get_active_users']);
    Route::GET('fields/active', [FieldController::class, 'get_active_fields']);
    Route::GET('centers/active', [CenterController::class, 'get_active_Centers']);
    Route::GET('categories/active', [CategoryController::class, 'get_active_Categories']);
    Route::GET('categories/groups', [CategoryController::class, 'get_category_groups']);

    // Client additional routes
    Route::prefix('client/registration')->name('client.registration.')->group(function () {
        Route::GET('count-accounts/{id}', [ClientRegistrationController::class, 'countAccounts'])->name('count_accounts');
        Route::GET('occupations', [ClientRegistrationController::class, 'get_client_occupations'])->name('occupations');
        Route::GET('saving/nominee/occupations', [SavingAccountController::class, 'get_nominee_occupations'])->name('saving.occupations');
        Route::GET('saving/nominee/relations', [SavingAccountController::class, 'get_nominee_relations'])->name('saving.relations');
        Route::GET('loan/guarantor/occupations', [LoanAccountController::class, 'get_guarantor_occupations'])->name('loan.occupations');
        Route::GET('loan/guarantor/relations', [LoanAccountController::class, 'get_guarantor_relations'])->name('loan.relations');
        Route::GET('saving/short-summery/{id}', [SavingAccountController::class, 'get_short_summery'])->name('saving.short_summery');
        Route::GET('loan/short-summery/{id}', [LoanAccountController::class, 'get_short_summery'])->name('loan.short_summery');
    });

    // Accounts Additional Routes
    Route::prefix('accounts')->name('accounts.')->group(function () {
        // Get active data
        Route::GET('active', [AccountController::class, 'get_active_accounts'])->name('accounts.active');
        Route::GET('incomes/categories/active', [IncomeCategoryController::class, 'get_active_categories'])->name('accounts.incomes.categories.active');
        Route::GET('expenses/categories/active', [ExpenseCategoryController::class, 'get_active_categories'])->name('accounts.expenses.categories.active');

        // Get Account transaction
        Route::GET('transactions/{account_id?}', [AccountController::class, 'get_all_transactions']);
    });

    /*
     * -------------------------------------------------------------------------
     * Api Resources Change Status Routes
     * -------------------------------------------------------------------------
     *
     * Here you can see all the API routes that have been additionally added to
     * the Change Status method in resource controller
     */
    Route::PUT('users/change-status/{id}', [UserController::class, 'change_status'])->name('users.changeStatus');
    Route::PUT('fields/change-status/{id}', [FieldController::class, 'change_status'])->name('fields.changeStatus');
    Route::PUT('centers/change-status/{id}', [CenterController::class, 'change_status'])->name('centers.changeStatus');
    Route::PUT('categories/change-status/{id}', [CategoryController::class, 'change_status'])->name('categories.changeStatus');

    // Accounts
    Route::prefix('accounts')->name('accounts.')->group(function () {
        Route::PUT('change-status/{id}', [AccountController::class, 'change_status'])->name('changeStatus');
        Route::PUT('incomes/categories/change-status/{id}', [IncomeCategoryController::class, 'change_status'])->name('incomes.changeStatus');
        Route::PUT('expenses/categories/change-status/{id}', [ExpenseCategoryController::class, 'change_status'])->name('expenses.changeStatus');
    });

    /*
     * -------------------------------------------------------------------------
     * Api Resources Approval Routes
     * -------------------------------------------------------------------------
     *
     * Here you can see all the API routes that have been additionally added to
     * the Approval method in resource controller
     */
    Route::prefix('client/registration')->name('client.registration.')->group(function () {
        // Registration Pending Fetch Routes
        Route::GET('saving/active/{client_id}', [SavingAccountController::class, 'activeAccount'])->name('saving.activeAccount');
        Route::GET('saving/pending/{client_id}', [SavingAccountController::class, 'pendingAccount'])->name('saving.pendingAccount');
        Route::GET('saving/hold/{client_id}', [SavingAccountController::class, 'holdAccount'])->name('saving.holdAccount');
        Route::GET('saving/closed/{client_id}', [SavingAccountController::class, 'closedAccount'])->name('saving.closedAccount');
        Route::GET('loan/active/{client_id}', [LoanAccountController::class, 'activeAccount'])->name('loan.activeAccount');
        Route::GET('loan/pending/{client_id}', [LoanAccountController::class, 'pendingAccount'])->name('loan.pendingAccount');
        Route::GET('loan/hold/{client_id}', [LoanAccountController::class, 'holdAccount'])->name('loan.holdAccount');
        Route::GET('loan/closed/{client_id}', [LoanAccountController::class, 'closedAccount'])->name('loan.closedAccount');
        Route::GET('info', [ClientRegistrationController::class, 'clientInfo'])->name('clientInfo');
        Route::GET('accounts/{field_id?}/{center_id?}', [ClientRegistrationController::class, 'clientAccounts'])->name('clientAccounts');
        Route::GET('pending-forms', [ClientRegistrationController::class, 'pending_forms'])->name('pendingForms');
        Route::GET('saving/pending-forms', [SavingAccountController::class, 'pending_forms'])->name('saving.pendingForms');
        Route::GET('loan/pending-forms', [LoanAccountController::class, 'pending_forms'])->name('loan.pendingForms');
        Route::GET('loan/pending-loans', [LoanAccountController::class, 'pending_loans'])->name('loan.pendingLoans');

        // Registration Approval Routes
        Route::PUT('approved/{id}', [ClientRegistrationController::class, 'approved'])->name('approved');
        Route::PUT('saving/approved/{id}', [SavingAccountController::class, 'approved'])->name('saving.approved');
        Route::PUT('loan/approved/{id}', [LoanAccountController::class, 'approved'])->name('loan.approved');
        Route::PUT('loan/loan-approved/{id}', [LoanAccountController::class, 'loan_approved'])->name('loan.loanApproved');
    });

    // Collection Approval Routes
    Route::prefix('collection')->group(function () {
        Route::POST('saving/approved', [SavingCollectionController::class, 'approved'])->name('saving.approved');
        Route::POST('loan/approved', [LoanCollectionController::class, 'approved'])->name('loan.approved');
    });
    Route::prefix('withdrawal')->group(function () {
        // Pending Withdrawal Routes 
        Route::GET('saving/pending', [SavingWithdrawalController::class, 'pending_withdrawal']);
        Route::GET('loan-saving/pending', [LoanSavingWithdrawalController::class, 'pending_withdrawal']);

        // Withdrawal Approval Routes
        Route::PUT('saving/approved/{id}', [SavingWithdrawalController::class, 'approved']);
        Route::PUT('loan-saving/approved/{id}', [LoanSavingWithdrawalController::class, 'approved']);
    });

    /*
     * -------------------------------------------------------------------------
     * Api Resources Permanent Destroy Routes
     * -------------------------------------------------------------------------
     *
     * Here you can see all the API routes that have been additionally added to
     * the Permanent Destroy method in resource controller
     */
    // Client Registration Permanent Destroy Routes
    Route::prefix('client')->name('client.registration.')->group(function () {
        Route::DELETE('force-delete/{id}', [ClientRegistrationController::class, 'permanently_destroy'])->name('forceDelete');
        Route::DELETE('saving/force-delete/{id}', [SavingAccountController::class, 'permanently_destroy'])->name('saving.forceDelete');
        Route::DELETE('loan/force-delete/{id}', [LoanAccountController::class, 'permanently_destroy'])->name('loan.forceDelete');
    });

    // Collection Permanent Destroy Routes
    Route::prefix('collection')->group(function () {
        Route::DELETE('saving/force-delete/{id}', [SavingCollectionController::class, 'permanently_destroy'])->name('saving.forceDelete');
        Route::DELETE('loan/force-delete/{id}', [LoanCollectionController::class, 'permanently_destroy'])->name('loan.forceDelete');
    });

    /*
     * -------------------------------------------------------------------------
     * Api Resources Collection Additional Routes
     * -------------------------------------------------------------------------
     *
     * Here you can see all the API routes that have been additionally added to
     * the Collection Additional Routes in resource controller
     */
    Route::prefix('collection')->group(function () {
        Route::prefix('saving')->name('saving.')->group(function () {
            // Regular Collection
            Route::prefix('regular/collection-sheet')->name('regular.')->group(function () {
                Route::GET('/', [SavingCollectionController::class, 'regularCategoryReport'])->name('regularCategoryReport');
                Route::GET('{category_id}', [SavingCollectionController::class, 'regularFieldReport'])->name('regularFieldReport');
                Route::GET('{category_id}/{field_id}', [SavingCollectionController::class, 'regularCollectionSheet'])->name('regularCollectionSheet');
            });
            // Pending Collection
            Route::prefix('pending/collection-sheet')->name('pending.')->group(function () {
                Route::GET('/', [SavingCollectionController::class, 'pendingCategoryReport'])->name('pendingCategoryReport');
                Route::GET('{category_id}', [SavingCollectionController::class, 'pendingFieldReport'])->name('pendingFieldReport');
                Route::GET('{category_id}/{field_id}', [SavingCollectionController::class, 'pendingCollectionSheet'])->name('pendingCollectionSheet');
            });
        });
        Route::prefix('loan')->name('loan.')->group(function () {
            // Regular Collection
            Route::prefix('regular/collection-sheet')->name('regular.')->group(function () {
                Route::GET('/', [LoanCollectionController::class, 'regularCategoryReport'])->name('regularCategoryReport');
                Route::GET('{category_id}', [LoanCollectionController::class, 'regularFieldReport'])->name('regularFieldReport');
                Route::GET('{category_id}/{field_id}', [LoanCollectionController::class, 'regularCollectionSheet'])->name('regularCollectionSheet');
            });
            // Pending COllection
            Route::prefix('pending/collection-sheet')->name('pending.')->group(function () {
                Route::GET('/', [LoanCollectionController::class, 'pendingCategoryReport'])->name('pendingCategoryReport');
                Route::GET('{category_id}', [LoanCollectionController::class, 'pendingFieldReport'])->name('pendingFieldReport');
                Route::GET('{category_id}/{field_id}', [LoanCollectionController::class, 'pendingCollectionSheet'])->name('pendingCollectionSheet');
            });
        });
    });

    /*
     * -------------------------------------------------------------------------
     * Api Resources Controllers & Routes
     * -------------------------------------------------------------------------
     *
     * Here you can see all of the api resource routes and controllers with their
     * methods that should controller applied
     */
    // Staff Routes Controller
    Route::apiResource('permissions', PermissionController::class)->only('update');
    Route::apiResource('users', UserController::class)->except('show');
    Route::apiResource('roles', RoleController::class)->except('show');
    Route::apiResource('fields', FieldController::class)->except('show');
    Route::apiResource('centers', CenterController::class)->except('show');
    Route::apiResource('categories', CategoryController::class)->except('show');
    Route::apiResource('saving/check', SavingAccountCheckController::class);
    Route::apiResource('loan/check', LoanAccountCheckController::class);
    Route::apiResource('saving-collection', SavingCollectionController::class)->except('show');

    // Client Routes
    Route::prefix('client/registration')->name('client.registration.')->group(function () {
        Route::apiResource('/', ClientRegistrationController::class)->parameter('', 'registration');
        Route::apiResource('saving', SavingAccountController::class);
        Route::apiResource('loan', LoanAccountController::class);
    });

    // Collection Routes
    Route::prefix('collection')->group(function () {
        Route::apiResource('saving', SavingCollectionController::class)->except('show');
        Route::apiResource('loan', LoanCollectionController::class)->except('show');
    });

    // Withdrawal Routes
    Route::prefix('withdrawal')->group(function () {
        Route::apiResource('saving', SavingWithdrawalController::class);
        Route::apiResource('loan-saving', LoanSavingWithdrawalController::class);
    });

    // Accounts Routes
    Route::prefix('accounts')->name('accounts.')->group(function () {
        Route::apiResource('/', AccountController::class)->except('show')->parameter('', 'account');
        Route::apiResource('withdrawals', AccountWithdrawalController::class)->except('show');
        Route::apiResource('transfers', AccountTransferController::class)->only(['index', 'store']);

        // Income Routes
        Route::prefix('incomes')->name('incomes.')->group(function () {
            Route::apiResource('/', IncomeController::class)->except('show')->parameter('', 'income');
            Route::apiResource('categories', IncomeCategoryController::class)->except('show');
        });

        // Income Routes
        Route::prefix('expenses')->name('expenses.')->group(function () {
            Route::apiResource('/', ExpenseController::class)->except('show')->parameter('', 'expense');
            Route::apiResource('categories', ExpenseCategoryController::class)->except('show');
        });
    });

    // Audit Routes
    Route::prefix('audit')->name('audit.')->group(function () {
        Route::apiResource('meta', AuditReportMetaController::class)->except('show');
    });

    /*
     * -------------------------------------------------------------------------
     * Api Independent Controllers & Routes
     * -------------------------------------------------------------------------
     *
     * Here you can see all of the api independent routes and controllers
     */
    // App Config Routes
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

    // Temp Routes
    Route::POST('/add-permission', function (Request $request) {
        $permission = Permission::create(
            [
                'name' => $request->name,
                'group_name' => $request->group_name,
                'guard_name' => 'web',
            ]
        );
        auth()->user()->givePermissionTo($permission);

        return response(
            [
                'success' => true,
                'message' => __('customValidations.authorize.successful'),
            ],
            200
        );
    });
});
