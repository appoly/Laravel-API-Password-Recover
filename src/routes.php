<?php

use Illuminate\Support\Facades\Route;

if (! Route::has('appoly.api.forgot-password')) {
    Route::get(
        'api/forgot-password',
        'Appoly\LaravelApiPasswordHelper\Http\Controllers\PasswordController@forgot'
    )->name('appoly.api.forgot-password');
}

if (! Route::has('appoly.api.submit-forgot-password')) {
    Route::post(
        'api/forgot-password',
        'Appoly\LaravelApiPasswordHelper\Http\Controllers\PasswordController@reset'
    )->name('appoly.api.submit-forgot-password');
}
