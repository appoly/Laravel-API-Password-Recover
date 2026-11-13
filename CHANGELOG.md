# Changelog

## 3.0.0

Security hardening of the password reset flow: reset codes now expire, are stored hashed, and the endpoints are rate limited. All users should upgrade.

The request/response JSON is unchanged, apart from a new 429 response. No mobile app changes are needed.

See [Upgrading from 2.x](README.md#upgrading-from-2x).

### Breaking

- Requires PHP 8.3+ and Laravel 12 or 13.
- `ResetPassword::__construct($user, string $code)`: `$code` is required.
- `password_helper_key` now stores `{hmac}:{expiresAt}`, not the code.
- Removed the unused `ApiPasswords` facade alias and `Facades\ApiPassword` (nothing was bound to it).
- An app listener on `PasswordReset` now also runs for API resets.
- If the POST includes `email`, the code must belong to that user.

### Added

- Codes from `random_int`, unique among outstanding codes; `PASSWORD_RESET_CODE_LENGTH` now controls the number of digits.
- Expiry (`PASSWORD_RESET_EXPIRY_MINUTES`).
- Rate limits per IP/email, plus an app-wide cap on failed resets (`PASSWORD_RESET_RATE_LIMITS`).
- `PASSWORD_RESET_REQUIRE_EMAIL`, `PASSWORD_RESET_MAX_ATTEMPTS`, `PASSWORD_RESET_RULES`.
- `PasswordReset` event on success.
- Optional, publishable index migration.
- JSON numeric keys (e.g. `12345` for `012345`) are padded back to the code length.

### Fixed

- Route name guard now sees names registered with `->name()` earlier in boot.
