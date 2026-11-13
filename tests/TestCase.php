<?php

declare(strict_types=1);

namespace Appoly\LaravelApiPasswordHelper\Tests;

use Appoly\LaravelApiPasswordHelper\ApiPasswordServiceProvider;
use Appoly\LaravelApiPasswordHelper\Http\Notifications\ResetPassword;
use Appoly\LaravelApiPasswordHelper\Tests\Fixtures\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Orchestra\Testbench\Attributes\WithMigration;
use Orchestra\Testbench\TestCase as Orchestra;

#[WithMigration]
abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function getPackageProviders($app)
    {
        return [ApiPasswordServiceProvider::class];
    }

    protected function defineEnvironment($app)
    {
        $app['config']->set('auth.providers.users.model', User::class);
        $app['config']->set('database.default', 'testing');
    }

    protected function makeUser(string $email = 'jane@example.com', array $attributes = []): User
    {
        return User::create([
            'name' => 'Jane',
            'email' => $email,
            'password' => Hash::make('old-password'),
            ...$attributes,
        ]);
    }

    /**
     * Requests a code over the API and returns the plain code from the sent notification.
     */
    protected function requestCode(User $user): string
    {
        Notification::fake();

        $this->getJson('/api/forgot-password?email='.urlencode($user->email))->assertOk();

        $code = null;

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use (&$code) {
            $code = $notification->code;

            return true;
        });

        return $code;
    }

    protected function setPackageConfig(string $key, mixed $value): void
    {
        config()->set('LaravelApiPasswordHelper.'.$key, $value);
    }
}
