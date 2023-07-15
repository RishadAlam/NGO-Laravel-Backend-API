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
        'otpSend' => 'You need to verified your Email Address! We already send an OTP into your Email Address. Please Check your Email inbox or spam folder.',
        'accDeactivate' => 'Your Account is temporary deactivate!',
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
        'otpIsInvalid' => 'OTP is Invalid!',
        'otpIsExpired' => 'OTP is Expired!',
    ]

];
