<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

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
    Route::POST('/registration', [AuthController::class, 'registration']);
    Route::POST('/logout', [AuthController::class, 'logout']);
    Route::PUT('/change-password', [AuthController::class, 'change_password']);
});
