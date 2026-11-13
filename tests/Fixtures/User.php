<?php

declare(strict_types=1);

namespace Appoly\LaravelApiPasswordHelper\Tests\Fixtures;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $table = 'users';

    // Mirrors apps that don't list password_helper_key as fillable.
    protected $fillable = ['name', 'email', 'password'];
}
