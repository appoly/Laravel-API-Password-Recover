<?php

declare(strict_types=1);

namespace Appoly\LaravelApiPasswordHelper\Support;

/**
 * Reads package config with defaults, so apps with an older published config keep working.
 */
final class Settings
{
    private const string PREFIX = 'LaravelApiPasswordHelper.';

    public static function codeLength(): int
    {
        $length = (int) self::get('PASSWORD_RESET_CODE_LENGTH', 6);

        return $length >= 1 && $length <= 18 ? $length : 6;
    }

    public static function expiryMinutes(): int
    {
        return max(1, (int) self::get('PASSWORD_RESET_EXPIRY_MINUTES', 60));
    }

    public static function requireEmail(): bool
    {
        return (bool) self::get('PASSWORD_RESET_REQUIRE_EMAIL', false);
    }

    public static function maxAttempts(): int
    {
        return max(1, (int) self::get('PASSWORD_RESET_MAX_ATTEMPTS', 5));
    }

    /**
     * @param  'forgot'|'reset'|'reset_global_failures'  $name
     */
    public static function rateLimit(string $name): ?int
    {
        $defaults = ['forgot' => 5, 'reset' => 5, 'reset_global_failures' => 100];
        $limits = self::get('PASSWORD_RESET_RATE_LIMITS', $defaults);

        if (! is_array($limits) || ! array_key_exists($name, $limits)) {
            return $defaults[$name];
        }

        return $limits[$name] === null ? null : max(1, (int) $limits[$name]);
    }

    public static function rules(): ?array
    {
        $rules = self::get('PASSWORD_RESET_RULES');

        return match (true) {
            $rules === null, $rules === [], $rules === '' => null,
            is_array($rules) => $rules,
            default => [$rules],
        };
    }

    private static function get(string $key, mixed $default = null): mixed
    {
        return config(self::PREFIX.$key, $default);
    }
}
