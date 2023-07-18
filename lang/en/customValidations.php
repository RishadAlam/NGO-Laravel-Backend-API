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
        'otpSent' => 'আপনাকে আপনার ইমেল ঠিকানা যাচাই করতে হবে। আমরা ইতিমধ্যেই আপনার ইমেল ঠিকানায় একটি OTP পাঠিয়েছি। আপনার ইমেল ইনবক্স বা স্প্যাম ফোল্ডার চেক করুন।',
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
    ]

];
