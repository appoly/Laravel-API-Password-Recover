<?php

use Appoly\LaravelApiPasswordHelper\ApiPasswordServiceProvider;
use Appoly\LaravelApiPasswordHelper\Http\Controllers\PasswordController;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Route;

/*
 * Apps can replace these routes by:
 *  - registering a route with the same name before this provider boots, or
 *  - registering the same method + URI later (e.g. in routes/api.php); the later route wins.
 */

// Names given with ->name() aren't indexed until the lookups are refreshed.
Route::getRoutes()->refreshNameLookups();

if (! Route::has('appoly.api.forgot-password')) {
    Route::get('api/forgot-password', [PasswordController::class, 'forgot'])
        ->middleware(ThrottleRequests::class.':'.ApiPasswordServiceProvider::FORGOT_LIMITER)
        ->name('appoly.api.forgot-password');
}

if (! Route::has('appoly.api.submit-forgot-password')) {
    Route::post('api/forgot-password', [PasswordController::class, 'reset'])
        ->middleware(ThrottleRequests::class.':'.ApiPasswordServiceProvider::RESET_LIMITER)
        ->name('appoly.api.submit-forgot-password');
}
