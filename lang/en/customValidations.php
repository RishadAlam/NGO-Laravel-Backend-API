<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Custom Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used during custom for various
    | messages that we need to display to the user
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Common Custom Message
    |--------------------------------------------------------------------------
    */

    'common' => [
        'somethingWentWrong' => 'Something went wrong!',
        'activeUserMiddleware' => 'Your account is temporarily inactive and you have been logged out.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Authozation Custom Message
    |--------------------------------------------------------------------------
    */
    'authorize' => [
        'successfull' => 'Authorization was successful.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Login Custom Message
    |--------------------------------------------------------------------------
    */
    'login' => [
        'successfull' => 'login Successful',
        'incorrectEmail' => 'The email is incorrect.',
        'incorrectPassword' => 'The password is incorrect.',
        'accDeactivate' => 'Your Account is temporary inactive!',
    ],

    /*
    |--------------------------------------------------------------------------
    | Logout Custom Message
    |--------------------------------------------------------------------------
    */
    'logout' => [
        'successfull' => 'Logout Successful',
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Change Custom Message
    |--------------------------------------------------------------------------
    */
    'passwordChange' => [
        'successfull' => 'Password changed Successful',
        'notMatch' => 'Current password did not match.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Forgot Password Custom Message
    |--------------------------------------------------------------------------
    */
    'forgotPassword' => [
        'successfull' => 'OTP Send into your Email Address. Please Check your Email inbox or spam folder.',
        'accountNotFound' => 'Account not found!',
    ],

    /*
    |--------------------------------------------------------------------------
    | OTP Verification Custom Message
    |--------------------------------------------------------------------------
    */
    'otp' => [
        'successfull' => 'Account Verified Successfully',
        'otpSent' => 'You need to verify your email address. We have already sent an OTP to your email address. Please Check your email inbox or spam folder.',
        'otpResend' => 'Again sent an OTP to your email address. Check your email inbox or spam folder.',
        'otpIsInvalid' => 'OTP is Invalid!',
        'otpIsExpired' => 'OTP is Expired!',
    ],

    /*
    |--------------------------------------------------------------------------
    | Reset Password Custom Message
    |--------------------------------------------------------------------------
    */
    'resetPassword' => [
        'successfull' => 'Password reset Successfully',
    ],

    /*
    |--------------------------------------------------------------------------
    | Staff Custom Message
    |--------------------------------------------------------------------------
    */
    'staff' => [
        'successfull'   => 'The staff has been successfully registered.',
        'delete'        => 'The staff has been successfully deleted.',
        'update'        => 'The staff has been successfully updated.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Role Custom Message
    |--------------------------------------------------------------------------
    */
    'role' => [
        'successfull'   => 'The role has been successfully registered.',
        'delete'        => 'The role has been successfully deleted.',
        'update'        => 'The role has been successfully updated.',
    ]

];
