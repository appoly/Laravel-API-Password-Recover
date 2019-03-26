<?php

namespace Appoly\LaravelApiPasswordHelper;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Appoly\LaravelApiPasswordHelper\Http\Controllers\PasswordController;

class ApiPasswordServiceProvider extends ServiceProvider
{

    public function boot()
    {
        $this->loadRoutesFrom(__DIR__.'/routes.php');
        $this->loadMigrationsFrom(__DIR__.'/Migrations');
    }

    public function register()
    {

    }
}
