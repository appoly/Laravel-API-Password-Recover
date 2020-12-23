<?php

namespace Appoly\LaravelApiPasswordHelper;

use Appoly\LaravelApiPasswordHelper\Http\Controllers\PasswordController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class ApiPasswordServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__.'/routes.php');
        $this->loadMigrationsFrom(__DIR__.'/Migrations');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/config.php' => config_path('LaravelApiPasswordHelper.php'),
            ], 'api-password-helper-config');
        }
    }

    public function register()
    {
        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'LaravelApiPasswordHelper');

    }
}
