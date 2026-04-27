<?php

return [
    'otp' => [
        'subject'           => 'Account Verification',
        'preheader'         => "Your one-time verification code is ready. It expires in a few minutes — don't share it with anyone.",
        'header_tag'        => 'Account Verification',
        'greeting'          => "Hi :name, verify it's you",
        'intro'             => "Use the one-time verification code below to confirm your account. This code is unique to this request — please don't share it with anyone, including :company staff.",
        'expiry_label'      => 'Heads up —',
        'expiry_text'       => 'this code expires at :time. Request a new one if it lapses.',
        'security_note'     => "Didn't request this? You can safely ignore this email — your account stays secure. If you keep getting these messages, please contact our support team.",
        'rights_reserved'   => 'All rights reserved.',
        'automated_notice'  => "This is an automated message — please don't reply to this email.",
    ],
];
