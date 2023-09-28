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
        'successful' => 'Authorization was successful.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Login Custom Message
    |--------------------------------------------------------------------------
    */
    'login' => [
        'successful' => 'login Successful',
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
        'successful' => 'Logout Successful',
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Change Custom Message
    |--------------------------------------------------------------------------
    */
    'passwordChange' => [
        'successful' => 'Password changed Successful',
        'notMatch' => 'Current password did not match.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Forgot Password Custom Message
    |--------------------------------------------------------------------------
    */
    'forgotPassword' => [
        'successful' => 'OTP Send into your Email Address. Please Check your Email inbox or spam folder.',
        'accountNotFound' => 'Account not found!',
    ],

    /*
    |--------------------------------------------------------------------------
    | OTP Verification Custom Message
    |--------------------------------------------------------------------------
    */
    'otp' => [
        'successful' => 'Account Verified Successfully',
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
        'successful' => 'Password reset Successfully',
    ],

    /*
    |--------------------------------------------------------------------------
    | Staff Custom Message
    |--------------------------------------------------------------------------
    */
    'staff' => [
        'successful'        => 'The staff has been successfully registered.',
        'delete'            => 'The staff has been successfully deleted.',
        'update'            => 'The staff has been successfully updated.',
        'status'            => 'The staff status has been successfully updated.',
        'profile_update'    => 'The Profile has been successfully updated.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Role Custom Message
    |--------------------------------------------------------------------------
    */
    'role' => [
        'successful'   => 'The role has been successfully registered.',
        'delete'        => 'The role has been successfully deleted.',
        'update'        => 'The role has been successfully updated.',
    ],
    /*
    |--------------------------------------------------------------------------
    | Permission Custom Message
    |--------------------------------------------------------------------------
    */
    'permission' => [
        'update'        => 'The staff permission has been successfully updated.',
    ],
    /*
    |--------------------------------------------------------------------------
    | App Config Custom Message
    |--------------------------------------------------------------------------
    */
    'app_config' => [
        'app_settings_update' => 'The app settings has been successfully updated.',
    ]
];
