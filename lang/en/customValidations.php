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
    | Field Custom Message
    |--------------------------------------------------------------------------
    */
    'field' => [
        'successful'        => 'The field has been successfully registered.',
        'delete'            => 'The field has been successfully deleted.',
        'update'            => 'The field has been successfully updated.',
        'status'            => 'The field status has been successfully updated.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Center Custom Message
    |--------------------------------------------------------------------------
    */
    'center' => [
        'successful'        => 'The center has been successfully registered.',
        'delete'            => 'The center has been successfully deleted.',
        'update'            => 'The center has been successfully updated.',
        'status'            => 'The center status has been successfully updated.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Category Custom Message
    |--------------------------------------------------------------------------
    */
    'category' => [
        'successful'        => 'The category has been successfully registered.',
        'delete'            => 'The category has been successfully deleted.',
        'update'            => 'The category has been successfully updated.',
        'status'            => 'The category status has been successfully updated.',
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
        'app_settings_update'               => 'The app settings has been successfully updated.',
        'approval_configuration_update'     => 'The Approvals has been successfully updated.',
        'categories_configuration_update'   => 'The Categories Configuration has been successfully updated.',
        'transfer_transaction_update'       => 'The Transfer Transaction Configuration has been successfully updated.',
    ],
    /*
    |--------------------------------------------------------------------------
    | Account Management Custom Message
    |--------------------------------------------------------------------------
    */
    'accounts' => [
        'successful'        => 'The account has been successfully registered.',
        'delete'            => 'The account has been successfully deleted.',
        'update'            => 'The account has been successfully updated.',
        'status'            => 'The account status has been successfully updated.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Income Categories Custom Message
    |--------------------------------------------------------------------------
    */
    'income_category' => [
        'successful'        => 'The income category has been successfully registered.',
        'delete'            => 'The income category has been successfully deleted.',
        'update'            => 'The income category has been successfully updated.',
        'status'            => 'The income category status has been successfully updated.',

        'default'           => [
            'registration_fee'                  => 'Registration Fee',
            'closing_fee'                       => 'Closing Fee',
            'withdrawal_fee'                    => 'Withdrawal Fee',
            'money_transfer_transaction_fee'    => 'Money Transfer Fee'
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Income Custom Message
    |--------------------------------------------------------------------------
    */
    'income' => [
        'successful'        => 'The income has been successfully registered.',
        'delete'            => 'The income has been successfully deleted.',
        'update'            => 'The income has been successfully updated.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Expense Categories Custom Message
    |--------------------------------------------------------------------------
    */
    'expense_category' => [
        'successful'        => 'The expense category has been successfully registered.',
        'delete'            => 'The expense category has been successfully deleted.',
        'update'            => 'The expense category has been successfully updated.',
        'status'            => 'The expense category status has been successfully updated.',

        'default'           => [
            'electricity_bill'  => 'Electricity Bill',
            'office_rent'       => 'Office Rent',
            'daily_expense'     => 'Daily Expense'
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Expense Custom Message
    |--------------------------------------------------------------------------
    */
    'expense' => [
        'successful'        => 'The expense has been successfully registered.',
        'delete'            => 'The expense has been successfully deleted.',
        'update'            => 'The expense has been successfully updated.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Account Withdrawal Custom Message
    |--------------------------------------------------------------------------
    */
    'account_withdrawal' => [
        'successful'        => 'The Withdrawal has been successfully registered.',
        'delete'            => 'The Withdrawal has been successfully deleted.',
        'update'            => 'The Withdrawal has been successfully updated.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Account Transfer Custom Message
    |--------------------------------------------------------------------------
    */
    'account_transfer' => [
        'successful'        => 'The Transfer has been successfully registered.',
        'delete'            => 'The Transfer has been successfully deleted.',
        'update'            => 'The Transfer has been successfully updated.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Client Custom Message
    |--------------------------------------------------------------------------
    */
    'client' => [
        /*
        |--------------------------------------------------------------------------
        | Client registration Custom Message
        |--------------------------------------------------------------------------
        */
        'registration' => [
            'successful'        => 'The Client Profile has been successfully registered.',
            'update'            => 'The Client Profile has been successfully updated.',
            'delete'            => 'The Client Profile has been successfully deleted.',
            'p_delete'          => 'The Client Profile has been permanently deleted.',
            'approved'          => 'The Client Profile has been successfully approved.',
        ],

        /*
        |--------------------------------------------------------------------------
        | Saving registration Custom Message
        |--------------------------------------------------------------------------
        */
        'saving' => [
            'successful'        => 'The Saving Account has been successfully registered.',
            'update'            => 'The Saving Account has been successfully updated.',
            'delete'            => 'The Saving Account has been successfully deleted.',
            'p_delete'          => 'The Saving Account has been permanently deleted.',
            'approved'          => 'The Saving Account has been successfully approved.',
        ],

        /*
        |--------------------------------------------------------------------------
        | Loan registration Custom Message
        |--------------------------------------------------------------------------
        */
        'loan' => [
            'successful'        => 'The Loan Account has been successfully registered.',
            'update'            => 'The Loan Account has been successfully updated.',
            'delete'            => 'The Loan Account has been successfully deleted.',
            'p_delete'          => 'The Loan Account has been permanently deleted.',
            'approved'          => 'The Loan Account has been successfully approved.',
        ],
    ],
];
