<?php
return [
    'email_enabled' => env('NOTICE_EMAIL_ENABLED', false),
    'sms_enabled' => env('NOTICE_SMS_ENABLED', false),
    'sms_url' => env('NOTICE_SMS_URL'),
    'sms_token' => env('NOTICE_SMS_TOKEN'),
    'sms_sender' => env('NOTICE_SMS_SENDER', 'NUBTK'),
];