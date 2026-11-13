<?php

declare(strict_types=1);

namespace Appoly\LaravelApiPasswordHelper\Http\Controllers;

use Appoly\LaravelApiPasswordHelper\Http\Notifications\ResetPassword;
use Appoly\LaravelApiPasswordHelper\Support\Envelope;
use Appoly\LaravelApiPasswordHelper\Support\ResetCodeGenerator;
use Appoly\LaravelApiPasswordHelper\Support\ResetKey;
use Appoly\LaravelApiPasswordHelper\Support\Settings;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

class PasswordController
{
    public const string GLOBAL_FAILURES_KEY = 'appoly-password-reset:global-failures';

    private const int MAX_GENERATION_ATTEMPTS = 10;

    public function forgot(Request $request, ResetCodeGenerator $generator)
    {
        if (! $request->has('email')) {
            return Envelope::error(Envelope::EMAIL_REQUIRED);
        }

        $user = $this->findUserByEmail($request->input('email'));

        if (! $user) {
            return Envelope::error(Envelope::NO_USER);
        }

        $code = $this->uniqueCode($generator);

        $user->forceFill([
            'password_helper_key' => ResetKey::issue(
                $code,
                now()->addMinutes(Settings::expiryMinutes())->getTimestamp(),
            )->toString(),
        ])->save();

        $user->notify(new ResetPassword($user, $code));

        return Envelope::success(Envelope::EMAIL_SENT);
    }

    public function reset(Request $request)
    {
        if (! $request->has('key') || ! $request->has('password')) {
            return Envelope::error(Envelope::KEY_AND_PASSWORD_REQUIRED);
        }

        if (Settings::requireEmail() && ! $request->has('email')) {
            return Envelope::error(Envelope::KEY_AND_PASSWORD_REQUIRED);
        }

        $password = $request->input('password');

        if (! is_scalar($password)) {
            return Envelope::error(Envelope::KEY_AND_PASSWORD_REQUIRED);
        }

        $globalCap = Settings::rateLimit('reset_global_failures');

        if ($globalCap !== null && RateLimiter::tooManyAttempts(self::GLOBAL_FAILURES_KEY, $globalCap)) {
            return Envelope::tooManyAttempts([
                'Retry-After' => RateLimiter::availableIn(self::GLOBAL_FAILURES_KEY),
            ]);
        }

        if ($rules = Settings::rules()) {
            $validator = Validator::make(['password' => (string) $password], ['password' => $rules]);

            if ($validator->fails()) {
                return Envelope::error($validator->errors()->first('password'));
            }
        }

        $emailUser = null;

        if ($request->has('email')) {
            $emailUser = $this->findUserByEmail($request->input('email'));

            if (! $emailUser) {
                return $this->miss(null);
            }
        }

        $code = $this->normaliseCode($request->input('key'));
        $user = $code === null ? null : $this->findUserByCode($code, $emailUser);

        if (! $user) {
            return $this->miss($emailUser);
        }

        $user->forceFill([
            'password' => Hash::make((string) $password),
            'password_helper_key' => null,
        ])->save();

        RateLimiter::clear($this->userAttemptsKey($user));

        event(new PasswordReset($user));

        return Envelope::success(Envelope::PASSWORD_UPDATED);
    }

    private function miss(?Model $emailUser): JsonResponse
    {
        if (Settings::rateLimit('reset_global_failures') !== null) {
            RateLimiter::hit(self::GLOBAL_FAILURES_KEY, 3600);
        }

        if ($emailUser) {
            $key = $this->userAttemptsKey($emailUser);

            RateLimiter::hit($key, Settings::expiryMinutes() * 60);

            if (RateLimiter::attempts($key) >= Settings::maxAttempts()) {
                $emailUser->forceFill(['password_helper_key' => null])->save();
                RateLimiter::clear($key);
            }
        }

        return Envelope::error(Envelope::NO_USER);
    }

    private function findUserByCode(string $code, ?Model $emailUser): ?Model
    {
        $matches = $this->query()
            ->where('password_helper_key', 'like', ResetKey::lookupPrefix($code).'%')
            ->when($emailUser, fn (Builder $query) => $query->whereKey($emailUser->getKey()))
            ->get()
            ->filter(function (Model $user) use ($code): bool {
                $key = ResetKey::parse($user->getAttribute('password_helper_key'));

                return $key !== null && $key->matches($code) && ! $key->isExpired();
            });

        // More than one match should be impossible (codes are unique on issue); refuse rather than guess.
        return $matches->count() === 1 ? $matches->first() : null;
    }

    private function findUserByEmail(mixed $email): ?Model
    {
        if (! is_string($email) || $email === '') {
            return null;
        }

        return $this->query()->whereIn('email', Envelope::emailCandidates($email))->first();
    }

    private function uniqueCode(ResetCodeGenerator $generator): string
    {
        $length = Settings::codeLength();

        for ($i = 0; $i < self::MAX_GENERATION_ATTEMPTS; $i++) {
            $code = $generator->generate($length);

            $inUse = $this->query()
                ->where('password_helper_key', 'like', ResetKey::lookupPrefix($code).'%')
                ->pluck('password_helper_key')
                ->contains(fn (?string $value): bool => ResetKey::parse($value)?->isExpired() === false);

            if (! $inUse) {
                return $code;
            }
        }

        throw new RuntimeException('Unable to generate a unique password reset code.');
    }

    /**
     * JSON clients may send the code as a number, which drops leading zeros.
     */
    private function normaliseCode(mixed $key): ?string
    {
        return match (true) {
            is_int($key) && $key >= 0 => str_pad((string) $key, Settings::codeLength(), '0', STR_PAD_LEFT),
            is_string($key) && $key !== '' => $key,
            default => null,
        };
    }

    private function userAttemptsKey(Model $user): string
    {
        return 'appoly-password-reset:user:'.$user->getKey();
    }

    private function query(): Builder
    {
        $model = config('auth.providers.users.model');

        return $model::query();
    }
}
