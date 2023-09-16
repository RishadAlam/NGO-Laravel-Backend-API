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
        'somethingWentWrong' => 'কিছু ভুল হয়েছে!',
        'activeUserMiddleware' => 'আপনার অ্যাকাউন্ট সাময়িকভাবে নিষ্ক্রিয় রয়েছে এবং আপনি লগ আউট হয়ে গিয়েছেন।',
    ],

    /*
    |--------------------------------------------------------------------------
    | Authozation Custom Message
    |--------------------------------------------------------------------------
    */
    'authorize' => [
        'successfull' => 'অনুমোদন সফল হয়েছে।',
    ],

    /*
    |--------------------------------------------------------------------------
    | Login Custom Message
    |--------------------------------------------------------------------------
    */

    'login' => [
        'successfull' => 'লগইন সফল হয়েছে।',
        'incorrectEmail' => 'ইমেলটি ভুল।',
        'incorrectPassword' => 'পাসওয়ার্ডটি ভুল।',
        'accDeactivate' => 'আপনার অ্যাকাউন্ট সাময়িকভাবে নিষ্ক্রিয় রয়েছে।',
    ],

    /*
    |--------------------------------------------------------------------------
    | Logout Custom Message
    |--------------------------------------------------------------------------
    */
    'logout' => [
        'successfull' => 'লগআউট সফল হয়েছে।',
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Change Custom Message
    |--------------------------------------------------------------------------
    */
    'passwordChange' => [
        'successfull' => 'পাসওয়ার্ড পরিবর্তন করা হয়েছে',
        'notMatch' => 'বর্তমান পাসওয়ার্ড মেলেনি।',
    ],

    /*
    |--------------------------------------------------------------------------
    | Forgot Password Custom Message
    |--------------------------------------------------------------------------
    */
    'forgotPassword' => [
        'successfull' => 'আপনার ইমেল ঠিকানায় ওটিপি পাঠানো হয়েছে। আপনার ইমেল ইনবক্স বা স্প্যাম ফোল্ডার চেক করুন।',
        'accountNotFound' => 'অ্যাকাউন্ট পাওয়া যায়নি।',
    ],

    /*
    |--------------------------------------------------------------------------
    | OTP Verification Custom Message
    |--------------------------------------------------------------------------
    */
    'otp' => [
        'successfull' => 'অ্যাকাউন্ট সফলভাবে যাচাই করা হয়েছে।',
        'otpSent' => 'আপনাকে আপনার ইমেল ঠিকানা যাচাই করতে হবে। আমরা ইতিমধ্যেই আপনার ইমেল ঠিকানায় একটি OTP পাঠিয়েছি। আপনার ইমেল ইনবক্স বা স্প্যাম ফোল্ডার চেক করুন।',
        'otpResend' => 'পুনরায় আপনার ইমেল ঠিকানায় একটি OTP পাঠিয়েছি। আপনার ইমেল ইনবক্স বা স্প্যাম ফোল্ডার চেক করুন।',
        'otpIsInvalid' => 'ওটিপি অবৈধ!',
        'otpIsExpired' => 'ওটিপি মেয়াদ শেষ!',
    ],

    /*
    |--------------------------------------------------------------------------
    | Reset Password Custom Message
    |--------------------------------------------------------------------------
    */
    'resetPassword' => [
        'successfull' => 'পাসওয়ার্ড পুনরায় সেট করা হয়েছে৷।',
    ],

    /*
    |--------------------------------------------------------------------------
    | Staff Custom Message
    |--------------------------------------------------------------------------
    */
    'staff' => [
        'successful'        => 'কর্মী সফলভাবে নিবন্ধিত হয়েছে।',
        'delete'            => 'কর্মী সফলভাবে মুছে ফেলা হয়েছে।',
        'update'            => 'কর্মীর তথ্য সফলভাবে পরিবর্তন করা হয়েছে।',
        'status'            => 'কর্মীর স্ট্যাটাস সফলভাবে পরিবর্তন করা হয়েছে।',
        'profile_update'    => 'প্রোফাইল তথ্য সফলভাবে পরিবর্তন করা হয়েছে।',
    ],

    /*
    |--------------------------------------------------------------------------
    | Role Custom Message
    |--------------------------------------------------------------------------
    */
    'role' => [
        'successful'   => 'ভূমিকা সফলভাবে নিবন্ধিত হয়েছে।',
        'delete'        => 'ভূমিকা সফলভাবে মুছে ফেলা হয়েছে।',
        'update'        => 'ভূমিকা সফলভাবে আপডেট করা হয়েছে।',
    ]

];