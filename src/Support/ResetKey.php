<?php

declare(strict_types=1);

namespace Appoly\LaravelApiPasswordHelper\Support;

use RuntimeException;

/**
 * The value stored in users.password_helper_key: "{hmac}:{expiresAtUnix}".
 *
 * The HMAC is keyed on APP_KEY so a leaked database dump can't be reversed
 * without the key. Rotating APP_KEY cancels every outstanding code.
 */
final readonly class ResetKey
{
    private const string PATTERN = '/^([0-9a-f]{64}):(\d{1,12})$/';

    public function __construct(
        public string $hmac,
        public int $expiresAt,
    ) {}

    public static function issue(string $code, int $expiresAt): self
    {
        return new self(self::hmac($code), $expiresAt);
    }

    /**
     * Returns null for anything not in the current format, including pre-3.0 plain-text codes.
     */
    public static function parse(mixed $value): ?self
    {
        if (! is_string($value) || ! preg_match(self::PATTERN, $value, $matches)) {
            return null;
        }

        return new self($matches[1], (int) $matches[2]);
    }

    public static function hmac(string $code): string
    {
        return hash_hmac('sha256', $code, self::appKey());
    }

    /**
     * Prefix for an indexable `LIKE` lookup of all rows holding this code.
     */
    public static function lookupPrefix(string $code): string
    {
        return self::hmac($code).':';
    }

    public function matches(string $code): bool
    {
        return hash_equals($this->hmac, self::hmac($code));
    }

    public function isExpired(): bool
    {
        return $this->expiresAt <= now()->getTimestamp();
    }

    public function toString(): string
    {
        return $this->hmac.':'.$this->expiresAt;
    }

    private static function appKey(): string
    {
        $key = (string) config('app.key');

        if (str_starts_with($key, 'base64:')) {
            $key = (string) base64_decode(substr($key, 7), true);
        }

        if ($key === '') {
            throw new RuntimeException('APP_KEY must be set to issue password reset codes.');
        }

        return $key;
    }
}
