<?php

namespace Appoly\LaravelApiPasswordHelper\Facades;

use Illuminate\Support\Facades\Facade;

class ApiPassword extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'ApiPasswordHelper';
    }
}
