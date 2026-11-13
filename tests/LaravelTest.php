<?php

namespace Appoly\LaravelApiPasswordHelper\Tests;

use PHPUnit\Framework\Attributes\Test;

class LaravelTest extends TestCase
{
    #[Test]
    public function forgot_password_route_can_be_accessed()
    {
        $this->get('/api/forgot-password?email="calum@example.com"')
            ->assertJson(['message' => 'No user found'])
            ->assertStatus(400);
    }

    #[Test]
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
