<?php

declare(strict_types=1);

namespace Appoly\LaravelApiPasswordHelper\Support;

use Illuminate\Http\JsonResponse;

/**
 * The package's response contract. Messages must stay byte-for-byte the same.
 */
final class Envelope
{
    public const string EMAIL_SENT = 'An email has been sent to your account';
    public const string PASSWORD_UPDATED = 'Your password has been updated';
    public const string NO_USER = 'No user found';
    public const string EMAIL_REQUIRED = 'Email is required';
    public const string KEY_AND_PASSWORD_REQUIRED = 'Key and Password are required';
    public const string TOO_MANY_ATTEMPTS = 'Too many attempts, please try again later';

    public static function success(string $message): JsonResponse
    {
        return response()->json(['success' => true, 'message' => $message], 200);
    }

    public static function error(string $message, int $status = 400, array $headers = []): JsonResponse
    {
        return response()->json(['success' => false, 'message' => $message], $status, $headers);
    }

    public static function tooManyAttempts(array $headers = []): JsonResponse
    {
        return self::error(self::TOO_MANY_ATTEMPTS, 429, $headers);
    }

    /**
     * Emails arrive with "+" decoded to a space when apps don't URL-encode them.
     *
     * @return list<string> candidate values to match against users.email
     */
    public static function emailCandidates(string $email): array
    {
        $email = str_replace(' ', '+', $email);

        return array_values(array_unique([$email, urldecode($email)]));
    }
}
