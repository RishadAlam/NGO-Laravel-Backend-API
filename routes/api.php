<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\staffs\RoleController;
use App\Http\Controllers\staffs\UserController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

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
 * Here is where you can hit Aithenticate routes. All of them are protected
 * by auth Sanctum middleware and email verified
 */
Route::group(['middleware' => ['auth:sanctum', 'verified', 'LangCheck', 'activeUser']], function () {
    /**
     * -------------------------------------------------------------------------
     * Authorzation Routes
     * -------------------------------------------------------------------------
     */
    Route::GET('/authorization', [AuthController::class, 'authorization']);
    Route::POST('/registration', [AuthController::class, 'registration']);
    Route::POST('/logout', [AuthController::class, 'logout']);
    Route::PUT('/change-password', [AuthController::class, 'change_password']);

    /**
     * -------------------------------------------------------------------------
     * Api Resources Additional Routes
     * -------------------------------------------------------------------------
     */
    Route::PUT('/users/change-status/{id}', [UserController::class, 'change_status']);
    Route::GET('/users/permissions/{id}', [UserController::class, 'get_user_permissions']);

    /**
     * -------------------------------------------------------------------------
     * Api Resources Controllers & Routes
     * -------------------------------------------------------------------------
     */
    Route::apiResources([
        'users' => UserController::class,
        'roles' => RoleController::class,
    ], ['except' => 'show']);

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
