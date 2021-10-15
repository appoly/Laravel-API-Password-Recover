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
    Email Template -- this should be a template based on 'notificationns::email'
    at the time of writing it is found at '/vendor/laravel/framework/src/illuminate/Notifcations/resources/views/email.blade.php'

    NOTE:   The base template might change in future laravel updates so if you are using this
            package feature please test the emails every update.
    */

    // use default template notifications::message
    'EMAIL_TEMPLATES' => '',

    // EXAMPLES
    // --- use a custom template for all
    // 'EMAIL_TEMPLATES' => 'mail.templates.consumer',

    // -- use a custom  template for each case
    // $user->role == 3 use consumer template
    // 'EMAIL_TEMPLATES' => ['role' => [3 => 'mail.templates.consumer']]

    // $user->role()->id == 3 use consumer template
    // 'EMAIL_TEMPLATES' => ['role().id' => [3 => 'mail.templates.consumer']]
];
