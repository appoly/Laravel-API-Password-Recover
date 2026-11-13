<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Email Customization
    |--------------------------------------------------------------------------
    |
    | Here you can change the body for your reset email
    | SUBJECT - Email Subject
    | GREETING - Email Greeting
    | BEFORE_CODE - The text displayed before the code, use an array and each line to be a new entry
    | AFTER_CODE - The text displayed after the code, use an array and each line to be a new entry
    | SIGN_OFF - The text displayed before your apps name
    |
    */
    'PASSWORD_RESET_CODE_LENGTH' => 6,
    'PASSWORD_RESET_SUBJECT' => 'Reset Password',
    'PASSWORD_RESET_GREETING' => 'Hello!',
    'PASSWORD_RESET_BEFORE_CODE' => [
        'You recently requested to reset your password for your '.config('app.name').' account.',
    ],
    'PASSWORD_RESET_AFTER_CODE' => [
        'Please copy and paste the code below into the app',
    ],
    'PASSWORD_RESET_SIGN_OFF' => 'Regards',

    /*
    Token will be injected at the end. Example:
    'PASSWORD_RESET_DEEPLINK' => 'myapp://resetpassword?token=',
    */
    'PASSWORD_RESET_DEEPLINK' => '',

    /*
    |--------------------------------------------------------------------------
    | Security
    |--------------------------------------------------------------------------
    |
    | EXPIRY_MINUTES - How long a code stays valid.
    | REQUIRE_EMAIL - When true, the reset POST must include the user's email.
    | MAX_ATTEMPTS - Wrong codes allowed per user before their code is cleared.
    |                Only counted when the reset POST includes an email.
    | RATE_LIMITS - Set any limit to null to disable it.
    |     forgot - requests per minute, per IP and per email
    |     reset - requests per minute, per IP
    |     reset_global_failures - failed resets per hour across the whole app
    | RULES - Validation rules for the new password, e.g. ['min:8']. null skips validation.
    |
    | Codes are stored as an HMAC keyed on APP_KEY. Rotating APP_KEY cancels
    | every outstanding code.
    |
    */
    'PASSWORD_RESET_EXPIRY_MINUTES' => 60,
    'PASSWORD_RESET_REQUIRE_EMAIL' => false,
    'PASSWORD_RESET_MAX_ATTEMPTS' => 5,
    'PASSWORD_RESET_RATE_LIMITS' => [
        'forgot' => 5,
        'reset' => 5,
        'reset_global_failures' => 100,
    ],
    'PASSWORD_RESET_RULES' => null,

];
