# Laravel: API forgotten password helper

## Introduction

Adds two API routes for "forgot password" with an emailed numeric code, plus a `password_helper_key` column on `users`.

Requires PHP 8.3+ and Laravel 12 or 13. Upgrading from 2.x? See [Upgrading from 2.x](#upgrading-from-2x).

## Install

```
composer require appoly/api-password-helper
php artisan migrate
```

The user model must be _Notifiable_:

```php
class User extends Authenticatable
{
    use Notifiable;
```

`password_helper_key` does **not** need to be in `$fillable`.

## Routes

```
GET  /api/forgot-password?email=johndoe@example.com
POST /api/forgot-password   key=123456&password=new-password[&email=johndoe@example.com]
```

| Response | Status | Body |
|---|---|---|
| Code sent | 200 | `{"success":true,"message":"An email has been sent to your account"}` |
| Password updated | 200 | `{"success":true,"message":"Your password has been updated"}` |
| Unknown email, or wrong / expired / used code | 400 | `{"success":false,"message":"No user found"}` |
| GET without `email` | 400 | `{"success":false,"message":"Email is required"}` |
| POST without `key` or `password` | 400 | `{"success":false,"message":"Key and Password are required"}` |
| Rate limited | 429 | `{"success":false,"message":"Too many attempts, please try again later"}` |

To replace a route, register the same method and URI in your app (e.g. `routes/api.php`); the later route wins.

## Security behaviour

- Codes come from `random_int`, are unique among outstanding codes, and expire after `PASSWORD_RESET_EXPIRY_MINUTES` (default 60).
- Only an HMAC of the code is stored (`{hmac}:{expiresAt}`), keyed on `APP_KEY`. **Rotating `APP_KEY` cancels outstanding codes.**
- Requests are rate limited (named limiters `appoly-password-forgot` and `appoly-password-reset`), and failed resets are capped app-wide per hour. Limits use the default cache store, so it must be shared between servers.
- Per-IP limits use `$request->ip()`, like Laravel's own `throttle` middleware. If the app sits behind Cloudflare or a load balancer, configure trusted proxies, or every user shares the proxy's IP and one limit. With no proxy in front, leave trusted proxies off (the Laravel default).
- If the POST includes `email`, the code must belong to that user. Set `PASSWORD_RESET_REQUIRE_EMAIL` to `true` to make `email` mandatory; this is the strongest setting, because it caps guesses per account.
- On success the package fires `Illuminate\Auth\Events\PasswordReset`. It doesn't revoke existing tokens; listen for the event to do that:

```php
Event::listen(PasswordReset::class, fn (PasswordReset $event) => $event->user->tokens()->delete());
```

## Configuration

```bash
php artisan vendor:publish --tag=api-password-helper-config
```

| Key | Default | |
|---|---|---|
| `PASSWORD_RESET_CODE_LENGTH` | `6` | Digits in the code (1–18) |
| `PASSWORD_RESET_SUBJECT`, `_GREETING`, `_BEFORE_CODE`, `_AFTER_CODE`, `_SIGN_OFF` | | Email text |
| `PASSWORD_RESET_DEEPLINK` | `''` | If set, the email shows a button linking to this URL with the code appended |
| `PASSWORD_RESET_EXPIRY_MINUTES` | `60` | |
| `PASSWORD_RESET_REQUIRE_EMAIL` | `false` | |
| `PASSWORD_RESET_MAX_ATTEMPTS` | `5` | Wrong codes per user before their code is cleared (only counted when `email` is sent) |
| `PASSWORD_RESET_RATE_LIMITS.forgot` | `5` | Per minute, per IP and per email. `null` disables |
| `PASSWORD_RESET_RATE_LIMITS.reset` | `5` | Per minute, per IP. `null` disables |
| `PASSWORD_RESET_RATE_LIMITS.reset_global_failures` | `100` | Failed resets per hour, app-wide. `null` disables |
| `PASSWORD_RESET_RULES` | `null` | Validation rules for the new password, e.g. `['min:8']` |

Older published configs work; missing keys use the defaults above.

## Optional index

Lookups use a prefix match on `password_helper_key`. On large `users` tables, add an index:

```bash
php artisan vendor:publish --tag=api-password-helper-index-migration
php artisan migrate
```

It isn't run automatically because indexing a large table may be slow. On PostgreSQL a plain index won't serve `LIKE 'prefix%'` unless the column uses the `C` collation; use a `text_pattern_ops` index instead.

## Custom notification text

`ResetPassword` takes `($user, string $code)` and exposes the plain code as `$notification->code`.

## Upgrading from 2.x

The request and response JSON are unchanged, apart from a new `429` response. Mobile apps need no changes.

**Required**

1. Require PHP 8.3+ and Laravel 12 or 13.
2. Run `composer require appoly/api-password-helper:^3.0`. A `^2.x` constraint won't pick up 3.0.
3. Fix tests that seed `password_helper_key` with a plain code or read the code from the database. The column now stores `{hmac}:{expiresAt}`. Get the code from the notification instead:

   ```php
   Notification::fake();
   $this->getJson('/api/forgot-password?email='.$user->email);
   Notification::assertSentTo($user, ResetPassword::class, function ($n) use (&$code) {
       $code = $n->code;
       return true;
   });
   ```
4. If you construct `ResetPassword` yourself, pass the code: `new ResetPassword($user, $code)`.
5. Remove any use of the `ApiPasswords` facade alias. It was never bound, so it couldn't have worked.

**Check before going live**

- Codes requested before the deploy stop working. Users must request a new one.
- Codes expire after 60 minutes.
- Your app handles a `429` sensibly.
- Only if the app sits behind Cloudflare or a load balancer: configure trusted proxies. Otherwise every user shares the proxy's IP and one rate limit of 5 per minute. With no proxy in front, nothing to do.
- An existing `PasswordReset` listener in your app will now also run for API resets.
- If your app already sends `email` on the reset POST, the code must now belong to that email.

**Optional**

- Listen for `PasswordReset` to revoke tokens on other devices (see [Security behaviour](#security-behaviour)).
- Publish the [index migration](#optional-index).
- Set `PASSWORD_RESET_REQUIRE_EMAIL=true` once every client sends `email`.
- Remove `password_helper_key` from `$fillable`.
