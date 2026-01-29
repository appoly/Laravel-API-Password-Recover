<?php

namespace Appoly\LaravelApiPasswordHelper\Tests;

use Appoly\LaravelApiPasswordHelper\ApiPasswordServiceProvider;
use Appoly\LaravelApiPasswordHelper\Facades\ApiPassword;
use Orchestra\Testbench\TestCase;

class LaravelTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [
            ApiPasswordServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app)
    {
        return [
            'ApiPasswords' => ApiPassword::class,
        ];
    }

    /** @test */
    public function forgot_password_route_can_be_accessed()
    {
        $this->get('/api/forgot-password?email="calum@example.com"')
            ->assertJson(['message' => 'No user found'])
            ->assertStatus(400);
    }

    /** @test */
    public function reset_password_test()
    {
        $data = [
            'key' => '123456',
            'email' => 'johndoe@example.com',
            'password' => 'secret',
        ];

        $this->post('/api/forgot-password', $data)
            ->assertJson(['message' => 'No user found'])
            ->assertStatus(400);
    }
}
