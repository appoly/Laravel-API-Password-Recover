<?php

declare(strict_types=1);

namespace Appoly\LaravelApiPasswordHelper\Tests;

use Appoly\LaravelApiPasswordHelper\ApiPasswordServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use PHPUnit\Framework\Attributes\Test;

class RouteOverrideTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [AppRoutesProvider::class, ApiPasswordServiceProvider::class];
    }

    protected function defineRoutes($router)
    {
        // Like routes/api.php: loaded after every provider has booted.
        $router->post('api/forgot-password', fn () => 'APP RESET')->name('appoly.api.submit-forgot-password');
    }

    #[Test]
    public function package_skips_a_route_name_the_app_registered_first(): void
    {
        $this->get('/api/forgot-password?email=x')->assertSee('APP FORGOT');
        $this->assertCount(1, collect(Route::getRoutes()->getRoutes())->filter(
            fn ($route) => $route->getName() === 'appoly.api.forgot-password'
        ));
    }

    #[Test]
    public function app_route_registered_later_on_same_uri_wins(): void
    {
        $this->post('/api/forgot-password', ['key' => '1', 'password' => 'x'])->assertSee('APP RESET');
    }
}

class AppRoutesProvider extends ServiceProvider
{
    public function boot(): void
    {
        Route::get('api/forgot-password', fn () => 'APP FORGOT')->name('appoly.api.forgot-password');
    }
}
