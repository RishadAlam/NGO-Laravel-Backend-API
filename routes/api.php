<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\config\AppConfigController;
use App\Http\Controllers\staffs\PermissionController;
use App\Http\Controllers\staffs\RoleController;
use App\Http\Controllers\staffs\UserController;
use Spatie\Permission\Models\Permission;

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
    Route::PUT('/users/change-status/{id}', [UserController::class, 'change_status']);
    Route::GET('/users/permissions/{id}', [UserController::class, 'get_user_permissions']);

    // Permissions Index
    Route::GET('permissions/{id}', [PermissionController::class, 'index'])->name('permissions.index');

    /**
     * -------------------------------------------------------------------------
     * Api Resources Controllers & Routes
     * -------------------------------------------------------------------------
     *
     * Here you can see all of the api resource routes and controllers with their
     * methods that should controller applied
     */
    // Staff Routes Controller
    Route::apiResource('users', UserController::class)->except('show');
    Route::apiResource('roles', RoleController::class)->except('show');
    Route::apiResource('permissions', PermissionController::class)->only('update');

    /**
     * -------------------------------------------------------------------------
     * Api Independent Controllers & Routes
     * -------------------------------------------------------------------------
     *
     * Here you can see all of the api independent routes and controllers
     */
    // App Config Routes
    Route::GET('/approvals-config', [AppConfigController::class, 'get_all_approvals']);
    Route::PUT('/app-settings-update', [AppConfigController::class, 'app_settings_update']);
    Route::PUT('/approvals-config-update', [AppConfigController::class, 'approvals_update']);

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

    Route::GET('/app-config', function () {
        return response(
            [
                'success'           => true,
                'message'           => __('customValidations.authorize.successfull')
            ],
            200
        );
    });
});
