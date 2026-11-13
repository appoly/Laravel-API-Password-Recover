<?php

namespace Appoly\LaravelApiPasswordHelper;

use Appoly\LaravelApiPasswordHelper\Support\Envelope;
use Appoly\LaravelApiPasswordHelper\Support\Settings;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class ApiPasswordServiceProvider extends ServiceProvider
{
    public const FORGOT_LIMITER = 'appoly-password-forgot';
    public const RESET_LIMITER = 'appoly-password-reset';

    public function boot()
    {
        $this->registerRateLimiters();

        $this->loadRoutesFrom(__DIR__.'/routes.php');
        $this->loadMigrationsFrom(__DIR__.'/Migrations');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/config.php' => config_path('LaravelApiPasswordHelper.php'),
            ], 'api-password-helper-config');

            $this->publishes([
                __DIR__.'/../database/optional-migrations' => database_path('migrations'),
            ], 'api-password-helper-index-migration');
        }
    }

    public function register()
    {
        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'LaravelApiPasswordHelper');
    }

    private function registerRateLimiters(): void
    {
        $tooMany = fn (Request $request, array $headers) => Envelope::tooManyAttempts($headers);

        RateLimiter::for(self::FORGOT_LIMITER, function (Request $request) use ($tooMany) {
            $max = Settings::rateLimit('forgot');

            if ($max === null) {
                return Limit::none();
            }

            $limits = [Limit::perMinute($max)->by('ip:'.$request->ip())->response($tooMany)];

            $email = $request->input('email');

            if (is_string($email) && $email !== '') {
                $normalised = mb_strtolower(urldecode(str_replace(' ', '+', $email)));
                $limits[] = Limit::perMinute($max)->by('email:'.$normalised)->response($tooMany);
            }

            return $limits;
        });

        RateLimiter::for(self::RESET_LIMITER, function (Request $request) use ($tooMany) {
            $max = Settings::rateLimit('reset');

            return $max === null
                ? Limit::none()
                : Limit::perMinute($max)->by('ip:'.$request->ip())->response($tooMany);
        });
    }
}
