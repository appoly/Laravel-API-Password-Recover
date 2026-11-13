<?php

declare(strict_types=1);

namespace Appoly\LaravelApiPasswordHelper\Support;

/**
 * Bound in the container so tests can force collisions.
 */
class ResetCodeGenerator
{
    public function generate(int $length): string
    {
        return str_pad((string) random_int(0, 10 ** $length - 1), $length, '0', STR_PAD_LEFT);
    }
}
