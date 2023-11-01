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

/**
 * ------------------------------------------------------------------------
 * Artisan Call Routes
 * ------------------------------------------------------------------------
 *
 * All routes are used by Artisan call
 */
Route::GET('/cache-clear', function () {
    Artisan::call('cache:clear');
    return response("Cache is cleared");
});
Route::GET('/config-clear', function () {
    Artisan::call('config:clear');
    return response("Cache is cleared");
});
Route::GET('/route-clear', function () {
    Artisan::call('route:clear');
    return response("Cache is cleared");
});
Route::GET('/optimize-clear', function () {
    Artisan::call('optimize:clear');
    return response("Cache is cleared");
});
Route::GET('/storage-link', function () {
    Artisan::call('storage:link');
    return response("Cache is cleared");
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
    Route::POST('/login', [AuthController::class, 'login']);
    Route::POST('/forget-password', [AuthController::class, 'forget_password']);
    Route::GET('/otp-resend/{id}', [AuthController::class, 'otp_resend']);
    Route::POST('/account-verification', [AuthController::class, 'otp_verification']);
    Route::PUT('/reset-password', [AuthController::class, 'reset_password']);
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
    Route::GET('/authorization', [AuthController::class, 'authorization']);
    Route::GET('/app-settings', [AppConfigController::class, 'index']);
    Route::POST('/registration', [AuthController::class, 'registration']);
    Route::POST('/logout', [AuthController::class, 'logout']);
    Route::PUT('/change-password', [AuthController::class, 'change_password']);
    Route::PUT('/profile-update', [AuthController::class, 'profile_update']);

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
    Route::GET('/categories/groups', [CategoryController::class, 'get_category_groups']);                                       // Get all Category Groups
    Route::GET('/accounts/active', [AccountController::class, 'get_active_accounts']);                                          // Get all Category Groups
    Route::GET('/accounts/transactions/{account_id?}/{date_range?}', [AccountController::class, 'get_all_transactions']);                                          // Get all Category Groups
    Route::GET('/incomes/{date_range}', [IncomeController::class, 'index'])->name('incomes.index');                             // Get all income according to date range
    Route::GET('/expenses/{date_range}', [ExpenseController::class, 'index'])->name('expenses.index');                          // Get all Expense according to date range
    Route::GET('/accounts/withdrawals/{date_range}', [AccountWithdrawalController::class, 'index'])->name('withdrawal.index');  // Get all Account Withdrawal according to date range
    Route::GET('/accounts/transfers/{date_range}', [AccountTransferController::class, 'index'])->name('transfer.index');        // Get all Account Transfer according to date range
    Route::GET('/income-categories/active', [IncomeCategoryController::class, 'get_active_categories']);                        // Get all active income Categories
    Route::GET('/expense-categories/active', [ExpenseCategoryController::class, 'get_active_categories']);                      // Get all active expense Categories

    // Change Status Routes
    Route::PUT('/users/change-status/{id}', [UserController::class, 'change_status']);
    Route::PUT('/fields/change-status/{id}', [FieldController::class, 'change_status']);
    Route::PUT('/centers/change-status/{id}', [CenterController::class, 'change_status']);
    Route::PUT('/categories/change-status/{id}', [CategoryController::class, 'change_status']);
    Route::PUT('/accounts/change-status/{id}', [AccountController::class, 'change_status']);
    Route::PUT('/income-categories/change-status/{id}', [IncomeCategoryController::class, 'change_status']);
    Route::PUT('/expense-categories/change-status/{id}', [ExpenseCategoryController::class, 'change_status']);

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
    Route::apiResource('accounts', AccountController::class)->except('show');
    Route::apiResource('income-categories', IncomeCategoryController::class)->except('show');
    Route::apiResource('expense-categories', ExpenseCategoryController::class)->except('show');
    Route::apiResource('incomes', IncomeController::class)->except('show');
    Route::apiResource('expenses', ExpenseController::class)->except('show');
    Route::apiResource('accounts/withdrawals', AccountWithdrawalController::class)->except('show');
    Route::apiResource('accounts/transfers', AccountTransferController::class)->only(['index', 'store']);

    /**
     * -------------------------------------------------------------------------
     * Api Independent Controllers & Routes
     * -------------------------------------------------------------------------
     *
     * Here you can see all of the api independent routes and controllers
     */
    // App Config Routes
    Route::GET('/approvals-config', [AppConfigController::class, 'get_all_approvals']);
    Route::GET('/categories-config', [CategoryConfigController::class, 'get_all_categories_config']);
    Route::PUT('/app-settings-update', [AppConfigController::class, 'app_settings_update']);
    Route::PUT('/approvals-config-update', [AppConfigController::class, 'approvals_update']);
    Route::PUT('/categories-config-update', [CategoryConfigController::class, 'config_update']);


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
