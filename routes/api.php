<?php

use Illuminate\Http\Request;
use App\Models\accounts\Account;
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
use App\Http\Controllers\config\CategoryConfigController;
use App\Http\Controllers\accounts\IncomeCategoryController;
use App\Http\Controllers\accounts\AccountTransferController;
use App\Http\Controllers\accounts\ExpenseCategoryController;
use App\Http\Controllers\accounts\AccountWithdrawalController;
use App\Http\Controllers\client\ClientRegistrationController;

/**
 * ------------------------------------------------------------------------
 * Artisan Call Routes
 * ------------------------------------------------------------------------
 *
 * All routes are used by Artisan call
 */
Route::GET('/cache-clear', function () {
    Artisan::call('cache:clear');
    return response("cache cleared");
});
Route::GET('/config-clear', function () {
    Artisan::call('config:clear');
    return response("config cleared");
});
Route::GET('/route-clear', function () {
    Artisan::call('route:clear');
    return response("route cleared");
});
Route::GET('/optimize-clear', function () {
    Artisan::call('optimize:clear');
    return response("optimize cleared");
});
Route::GET('/storage-link', function () {
    Artisan::call('storage:link');
    return response("storage Linked");
});

/**
 * ------------------------------------------------------------------------
 * Public Routes
 * ------------------------------------------------------------------------
 *
 * All routes are protected by LangCheck middleware
 */
Route::group(['middleware' => 'LangCheck'], function () {
    /**
     * -------------------------------------------------------------------------
     * unAuthenticated Routes
     * -------------------------------------------------------------------------
     *
     * Here is where you can hit unAuthenticated routes
     */
    Route::controller(AuthController::class)->group(function () {
        Route::POST('/login', 'login');
        Route::POST('/forget-password', 'forget_password');
        Route::GET('/otp-resend/{id}', 'otp_resend');
        Route::POST('/account-verification', 'otp_verification');
        Route::PUT('/reset-password', 'reset_password');
    });
    Route::controller(AppConfigController::class)->group(function () {
        Route::GET('/app-settings', 'index');
        Route::GET('/approvals-config', 'get_all_approvals');
    });
});



/**
 * -------------------------------------------------------------------------
 * Protected Routes
 * -------------------------------------------------------------------------
 *
 * Here is where you can hit Authenticate routes. All of them are protected
 * by auth Sanctum middleware and email verified
 */
Route::group(['middleware' => ['auth:sanctum', 'verified', 'LangCheck', 'activeUser']], function () {
    /**
     * -------------------------------------------------------------------------
     * Authorization Routes
     * -------------------------------------------------------------------------
     */
    Route::controller(AuthController::class)->group(function () {
        Route::GET('/authorization', 'authorization');
        Route::POST('/registration', 'registration');
        Route::POST('/logout', 'logout');
        Route::PUT('/change-password', 'change_password');
        Route::PUT('/profile-update', 'profile_update');
    });

    /**
     * -------------------------------------------------------------------------
     * Api Resources Additional Routes
     * -------------------------------------------------------------------------
     *
     * Here you can see all the API routes that have been additionally added to
     * the resource controller
     */
    Route::GET('/users/permissions/{id}', [UserController::class, 'get_user_permissions']);                                     // Get Specified users permissions
    Route::GET('permissions/{id}', [PermissionController::class, 'index'])->name('permissions.index');                          // Permissions Index
    Route::GET('/fields/active', [FieldController::class, 'get_active_fields']);                                                // Get all active fields
    Route::GET('/centers/active', [CenterController::class, 'get_active_Centers']);                                                // Get all active fields
    Route::GET('/categories/groups', [CategoryController::class, 'get_category_groups']);                                       // Get all Category Groups
    Route::GET('/client/registration/occupations', [ClientRegistrationController::class, 'get_client_occupations'])->name('client.registration.occupations');                                       // Get all Category Groups

    // Accounts Additional Routes
    Route::prefix('accounts')->name('accounts.')->group(function () {
        // Get active data
        Route::GET('/active', [AccountController::class, 'get_active_accounts'])->name('accounts.active');
        Route::GET('/incomes/categories/active', [IncomeCategoryController::class, 'get_active_categories'])->name('accounts.incomes.categories.active');
        Route::GET('/expenses/categories/active', [ExpenseCategoryController::class, 'get_active_categories'])->name('accounts.expenses.categories.active');

        // Get Account transaction
        Route::GET('/transactions/{account_id?}', [AccountController::class, 'get_all_transactions']);
    });

    /**
     * -------------------------------------------------------------------------
     * Api Resources Change Status Routes
     * -------------------------------------------------------------------------
     *
     * Here you can see all the API routes that have been additionally added to
     * the Change Status method in resource controller
     */
    Route::PUT('/users/change-status/{id}', [UserController::class, 'change_status'])->name('users.changeStatus');
    Route::PUT('/fields/change-status/{id}', [FieldController::class, 'change_status'])->name('fields.changeStatus');
    Route::PUT('/centers/change-status/{id}', [CenterController::class, 'change_status'])->name('centers.changeStatus');
    Route::PUT('/categories/change-status/{id}', [CategoryController::class, 'change_status'])->name('categories.changeStatus');

    // Accounts
    Route::prefix('accounts')->name('accounts.')->group(function () {
        Route::PUT('/change-status/{id}', [AccountController::class, 'change_status'])->name('accounts.changeStatus');
        Route::PUT('/incomes/categories/change-status/{id}', [IncomeCategoryController::class, 'change_status'])->name('accounts.incomes.changeStatus');
        Route::PUT('/expenses/categories/change-status/{id}', [ExpenseCategoryController::class, 'change_status'])->name('accounts.expenses.changeStatus');
    });

    /**
     * -------------------------------------------------------------------------
     * Api Resources Approval Routes
     * -------------------------------------------------------------------------
     *
     * Here you can see all the API routes that have been additionally added to
     * the Approval method in resource controller
     */
    Route::PUT('/client/registration/approved/{id}', [ClientRegistrationController::class, 'approved'])->name('client.registration.approved');

    /**
     * -------------------------------------------------------------------------
     * Api Resources Permanent Destroy Routes
     * -------------------------------------------------------------------------
     *
     * Here you can see all the API routes that have been additionally added to
     * the Permanent Destroy method in resource controller
     */
    Route::DELETE('/client/registration/force-delete/{id}', [ClientRegistrationController::class, 'permanently_destroy'])->name('client.registration.forceDelete');

    /**
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

    // Client Routes
    Route::prefix('client/registration')->name('client.registration.')->group(function () {
        Route::apiResource('/', ClientRegistrationController::class)->except('show')->parameter('', 'registration');
    });

    /**
     * -------------------------------------------------------------------------
     * Api Independent Controllers & Routes
     * -------------------------------------------------------------------------
     *
     * Here you can see all of the api independent routes and controllers
     */
    // App Config Routes
    Route::controller(AppConfigController::class)->group(function () {
        Route::PUT('/app-settings-update', 'app_settings_update');
        Route::PUT('/approvals-config-update', 'approvals_update');
        Route::PUT('/transfer-transaction-config-update', 'transfer_transaction_update');
    });
    Route::controller(CategoryConfigController::class)->group(function () {
        Route::GET('/categories-config', 'get_all_categories_config');
        Route::PUT('/categories-config-update', 'config_update');
    });


    // Temp Routes
    Route::POST('/add-permission', function (Request $request) {
        $permission = Permission::create(
            [
                'name'          => $request->name,
                'group_name'    => $request->group_name,
                'guard_name'    => 'web'
            ]
        );
        auth()->user()->givePermissionTo($permission);
        return response(
            [
                'success'           => true,
                'message'           => __('customValidations.authorize.successful')
            ],
            200
        );
    });
});
