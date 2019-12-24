<?php

use Illuminate\Support\Facades\Route;

Route::get('api/forgot-password', 'Appoly\LaravelApiPasswordHelper\Http\Controllers\PasswordController@forgot');
    Route::post('api/forgot-password', 'Appoly\LaravelApiPasswordHelper\Http\Controllers\PasswordController@reset');
