<?php

declare(strict_types=1);

namespace Appoly\LaravelApiPasswordHelper\Tests;

use Appoly\LaravelApiPasswordHelper\Http\Notifications\ResetPassword;
use Appoly\LaravelApiPasswordHelper\Support\ResetCodeGenerator;
use Appoly\LaravelApiPasswordHelper\Support\ResetKey;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;

class PasswordResetTest extends TestCase
{
    private const array SENT = ['success' => true, 'message' => 'An email has been sent to your account'];
    private const array UPDATED = ['success' => true, 'message' => 'Your password has been updated'];
    private const array NO_USER = ['success' => false, 'message' => 'No user found'];
    private const array EMAIL_REQUIRED = ['success' => false, 'message' => 'Email is required'];
    private const array KEY_REQUIRED = ['success' => false, 'message' => 'Key and Password are required'];
    private const array TOO_MANY = ['success' => false, 'message' => 'Too many attempts, please try again later'];

    // ---- GET -------------------------------------------------------------

    #[Test]
    public function known_email_gets_exact_success_json(): void
    {
        Notification::fake();
        $this->makeUser();

        $this->getJson('/api/forgot-password?email=jane@example.com')
            ->assertStatus(200)
            ->assertExactJson(self::SENT);
    }

    #[Test]
    public function unknown_and_missing_email_errors_are_unchanged(): void
    {
        $this->getJson('/api/forgot-password?email=nobody@example.com')->assertStatus(400)->assertExactJson(self::NO_USER);
        $this->getJson('/api/forgot-password')->assertStatus(400)->assertExactJson(self::EMAIL_REQUIRED);
    }

    #[Test]
    public function email_with_plus_sent_unencoded_is_found(): void
    {
        Notification::fake();
        $this->makeUser('jane+test@example.com');

        // "+" in an unencoded query string arrives as a space.
        $this->getJson('/api/forgot-password?email=jane+test@example.com')->assertOk()->assertExactJson(self::SENT);
    }

    #[Test]
    public function notification_code_uses_configured_length(): void
    {
        $this->setPackageConfig('PASSWORD_RESET_CODE_LENGTH', 8);

        $this->assertMatchesRegularExpression('/^\d{8}$/', $this->requestCode($this->makeUser()));
    }

    #[Test]
    public function database_holds_hmac_and_expiry_never_the_plain_code(): void
    {
        $user = $this->makeUser();
        $code = $this->requestCode($user);
        $stored = $user->fresh()->password_helper_key;

        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}:\d+$/', $stored);
        $this->assertStringNotContainsString($code, $stored);
        $this->assertSame(ResetKey::hmac($code), ResetKey::parse($stored)->hmac);
        $this->assertSame(now()->addMinutes(60)->getTimestamp(), ResetKey::parse($stored)->expiresAt);
    }

    #[Test]
    public function mail_body_contains_plain_code(): void
    {
        $user = $this->makeUser();
        $code = $this->requestCode($user);

        $mail = (new ResetPassword($user, $code))->toMail($user);

        $this->assertContains($code, $mail->introLines);
        $this->assertNull($mail->actionUrl);
    }

    #[Test]
    public function deeplink_ends_with_plain_code(): void
    {
        $this->setPackageConfig('PASSWORD_RESET_DEEPLINK', 'myapp://reset?token=');
        $user = $this->makeUser();
        $code = $this->requestCode($user);

        $mail = (new ResetPassword($user, $code))->toMail($user);

        $this->assertSame('myapp://reset?token='.$code, $mail->actionUrl);
    }

    #[Test]
    public function notification_exposes_code_as_public_property(): void
    {
        $notification = new ResetPassword($this->makeUser(), '123456');

        $this->assertSame('123456', $notification->code);
    }

    // ---- POST happy path ---------------------------------------------------

    #[Test]
    public function valid_code_resets_password_clears_key_and_fires_event(): void
    {
        $user = $this->makeUser();
        $code = $this->requestCode($user);
        Event::fake([PasswordReset::class]);

        $this->postJson('/api/forgot-password', ['key' => $code, 'password' => 'new-password'])
            ->assertStatus(200)
            ->assertExactJson(self::UPDATED);

        $user->refresh();
        $this->assertTrue(Hash::check('new-password', $user->password));
        $this->assertNull($user->password_helper_key);
        Event::assertDispatched(PasswordReset::class, fn (PasswordReset $event) => $event->user->is($user));

        $this->postJson('/api/forgot-password', ['key' => $code, 'password' => 'other'])
            ->assertStatus(400)
            ->assertExactJson(self::NO_USER);
    }

    #[Test]
    public function form_data_and_query_string_still_work(): void
    {
        $user = $this->makeUser();
        $code = $this->requestCode($user);

        $this->post('/api/forgot-password?key='.$code.'&password=from-query')->assertOk()->assertExactJson(self::UPDATED);
        $this->assertTrue(Hash::check('from-query', $user->fresh()->password));
    }

    #[Test]
    public function numeric_json_key_with_leading_zero_still_matches(): void
    {
        $this->app->instance(ResetCodeGenerator::class, $this->sequenceGenerator(['012345']));
        $user = $this->makeUser();
        $this->requestCode($user);

        $this->postJson('/api/forgot-password', ['key' => 12345, 'password' => 'new-password'])->assertOk();
    }

    #[Test]
    public function missing_key_or_password_error_is_unchanged(): void
    {
        $this->postJson('/api/forgot-password', ['key' => '123456'])->assertStatus(400)->assertExactJson(self::KEY_REQUIRED);
        $this->postJson('/api/forgot-password', ['password' => 'x'])->assertStatus(400)->assertExactJson(self::KEY_REQUIRED);
    }

    // ---- POST rejections ---------------------------------------------------

    #[Test]
    public function expired_code_is_rejected(): void
    {
        $user = $this->makeUser();
        $code = $this->requestCode($user);

        $this->travel(61)->minutes();

        $this->postJson('/api/forgot-password', ['key' => $code, 'password' => 'new-password'])
            ->assertStatus(400)
            ->assertExactJson(self::NO_USER);
    }

    #[Test]
    public function legacy_plain_text_key_is_rejected(): void
    {
        $user = $this->makeUser();
        $user->forceFill(['password_helper_key' => '123456'])->save();

        $this->postJson('/api/forgot-password', ['key' => '123456', 'password' => 'new-password'])
            ->assertStatus(400)
            ->assertExactJson(self::NO_USER);
        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
    }

    #[Test]
    public function wrong_code_is_rejected(): void
    {
        $this->app->instance(ResetCodeGenerator::class, $this->sequenceGenerator(['111111']));
        $user = $this->makeUser();
        $this->requestCode($user);

        $this->postJson('/api/forgot-password', ['key' => '222222', 'password' => 'new-password'])
            ->assertStatus(400)
            ->assertExactJson(self::NO_USER);
        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
    }

    #[Test]
    public function new_code_replaces_old_one(): void
    {
        $this->app->instance(ResetCodeGenerator::class, $this->sequenceGenerator(['111111', '222222']));
        $user = $this->makeUser();
        $this->requestCode($user);
        $this->requestCode($user);

        $this->postJson('/api/forgot-password', ['key' => '111111', 'password' => 'x'])->assertStatus(400);
        $this->postJson('/api/forgot-password', ['key' => '222222', 'password' => 'x'])->assertOk();
    }

    // ---- Rate limits -------------------------------------------------------

    #[Test]
    public function sixth_forgot_request_from_one_ip_is_throttled_with_package_envelope(): void
    {
        Notification::fake();

        for ($i = 1; $i <= 5; $i++) {
            $this->getJson("/api/forgot-password?email=user{$i}@example.com")->assertStatus(400);
        }

        $this->getJson('/api/forgot-password?email=user6@example.com')
            ->assertStatus(429)
            ->assertExactJson(self::TOO_MANY)
            ->assertHeader('Retry-After');
    }

    #[Test]
    public function forgot_is_throttled_per_email_across_ips(): void
    {
        Notification::fake();
        $this->makeUser();

        for ($i = 1; $i <= 5; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => "10.0.0.{$i}"])
                ->getJson('/api/forgot-password?email=jane@example.com')->assertOk();
        }

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.99'])
            ->getJson('/api/forgot-password?email=jane@example.com')
            ->assertStatus(429)
            ->assertExactJson(self::TOO_MANY);
    }

    #[Test]
    public function sixth_reset_request_from_one_ip_is_throttled_with_package_envelope(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->postJson('/api/forgot-password', ['key' => '000000', 'password' => 'x'])->assertStatus(400);
        }

        $this->postJson('/api/forgot-password', ['key' => '000000', 'password' => 'x'])
            ->assertStatus(429)
            ->assertExactJson(self::TOO_MANY);
    }

    #[Test]
    public function rate_limits_can_be_disabled_with_null(): void
    {
        $this->setPackageConfig('PASSWORD_RESET_RATE_LIMITS', ['forgot' => null, 'reset' => null, 'reset_global_failures' => null]);

        for ($i = 1; $i <= 20; $i++) {
            $this->getJson('/api/forgot-password?email=nobody@example.com')->assertStatus(400);
            $this->postJson('/api/forgot-password', ['key' => '000000', 'password' => 'x'])->assertStatus(400);
        }
    }

    #[Test]
    public function global_failure_cap_triggers_across_different_ips(): void
    {
        $this->setPackageConfig('PASSWORD_RESET_RATE_LIMITS', ['forgot' => 5, 'reset' => null, 'reset_global_failures' => 3]);
        $user = $this->makeUser();
        $code = $this->requestCode($user);

        for ($i = 1; $i <= 3; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => "10.0.1.{$i}"])
                ->postJson('/api/forgot-password', ['key' => 'wrong', 'password' => 'x'])->assertStatus(400);
        }

        // Blocks everyone, including a correct code, until the hour is up.
        $this->withServerVariables(['REMOTE_ADDR' => '10.0.1.50'])
            ->postJson('/api/forgot-password', ['key' => $code, 'password' => 'x'])
            ->assertStatus(429)
            ->assertExactJson(self::TOO_MANY);

        $this->travel(61)->minutes();
        $code = $this->requestCode($user);

        $this->postJson('/api/forgot-password', ['key' => $code, 'password' => 'x'])->assertOk();
    }

    #[Test]
    public function successful_resets_do_not_count_towards_global_cap(): void
    {
        $this->setPackageConfig('PASSWORD_RESET_RATE_LIMITS', ['forgot' => null, 'reset' => null, 'reset_global_failures' => 2]);

        for ($i = 1; $i <= 4; $i++) {
            $user = $this->makeUser("user{$i}@example.com");
            $code = $this->requestCode($user);
            $this->postJson('/api/forgot-password', ['key' => $code, 'password' => 'x'])->assertOk();
        }

        $this->postJson('/api/forgot-password', ['key' => 'wrong', 'password' => 'x'])->assertStatus(400);
    }

    // ---- Collisions --------------------------------------------------------

    #[Test]
    public function two_users_never_share_an_outstanding_code(): void
    {
        $this->app->instance(ResetCodeGenerator::class, $this->sequenceGenerator(['111111', '111111', '111111', '222222']));
        $jane = $this->makeUser('jane@example.com');
        $john = $this->makeUser('john@example.com');

        $this->assertSame('111111', $this->requestCode($jane));
        $this->assertSame('222222', $this->requestCode($john));
    }

    #[Test]
    public function expired_code_can_be_reissued_to_another_user(): void
    {
        $this->app->instance(ResetCodeGenerator::class, $this->sequenceGenerator(['111111', '111111']));
        $jane = $this->makeUser('jane@example.com');
        $john = $this->makeUser('john@example.com');

        $this->requestCode($jane);
        $this->travel(61)->minutes();

        $this->assertSame('111111', $this->requestCode($john));
    }

    // ---- Email binding -----------------------------------------------------

    #[Test]
    public function email_that_does_not_own_the_code_is_rejected(): void
    {
        $jane = $this->makeUser('jane@example.com');
        $this->makeUser('john@example.com');
        $code = $this->requestCode($jane);

        $this->postJson('/api/forgot-password', ['key' => $code, 'password' => 'x', 'email' => 'john@example.com'])
            ->assertStatus(400)
            ->assertExactJson(self::NO_USER);

        $this->postJson('/api/forgot-password', ['key' => $code, 'password' => 'x', 'email' => 'jane@example.com'])
            ->assertOk()
            ->assertExactJson(self::UPDATED);
    }

    #[Test]
    public function require_email_rejects_post_without_email(): void
    {
        $this->setPackageConfig('PASSWORD_RESET_REQUIRE_EMAIL', true);
        $user = $this->makeUser();
        $code = $this->requestCode($user);

        $this->postJson('/api/forgot-password', ['key' => $code, 'password' => 'x'])
            ->assertStatus(400)
            ->assertExactJson(self::KEY_REQUIRED);
    }

    #[Test]
    public function code_is_cleared_after_max_attempts_with_email(): void
    {
        $this->setPackageConfig('PASSWORD_RESET_RATE_LIMITS', ['forgot' => 5, 'reset' => null, 'reset_global_failures' => null]);
        $this->app->instance(ResetCodeGenerator::class, $this->sequenceGenerator(['111111']));
        $user = $this->makeUser();
        $this->requestCode($user);

        for ($i = 1; $i <= 5; $i++) {
            $this->postJson('/api/forgot-password', ['key' => '99999'.$i, 'password' => 'x', 'email' => 'jane@example.com'])
                ->assertStatus(400);
        }

        $this->assertNull($user->fresh()->password_helper_key);
        $this->postJson('/api/forgot-password', ['key' => '111111', 'password' => 'x', 'email' => 'jane@example.com'])
            ->assertStatus(400);
    }

    // ---- Password rules ----------------------------------------------------

    #[Test]
    public function password_rules_return_first_error_and_keep_the_code(): void
    {
        $this->setPackageConfig('PASSWORD_RESET_RULES', ['min:8']);
        $user = $this->makeUser();
        $code = $this->requestCode($user);

        $this->postJson('/api/forgot-password', ['key' => $code, 'password' => 'short'])
            ->assertStatus(400)
            ->assertExactJson(['success' => false, 'message' => 'The password field must be at least 8 characters.']);

        $this->postJson('/api/forgot-password', ['key' => $code, 'password' => 'long-enough'])->assertOk();
    }

    // ---- Compatibility -----------------------------------------------------

    #[Test]
    public function old_published_config_works_on_defaults(): void
    {
        config()->set('LaravelApiPasswordHelper', [
            'PASSWORD_RESET_CODE_LENGTH' => 6,
            'PASSWORD_RESET_SUBJECT' => 'Reset Password',
            'PASSWORD_RESET_GREETING' => 'Hello!',
            'PASSWORD_RESET_BEFORE_CODE' => ['Before'],
            'PASSWORD_RESET_AFTER_CODE' => ['After'],
            'PASSWORD_RESET_SIGN_OFF' => 'Regards',
            'PASSWORD_RESET_DEEPLINK' => '',
        ]);

        $user = $this->makeUser();
        $code = $this->requestCode($user);

        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);
        $this->assertSame(now()->addMinutes(60)->getTimestamp(), ResetKey::parse($user->fresh()->password_helper_key)->expiresAt);
        $this->postJson('/api/forgot-password', ['key' => $code, 'password' => 'new-password'])->assertOk();

        // The successful POST above was request 1 of the default 5 per minute.
        for ($i = 2; $i <= 5; $i++) {
            $this->postJson('/api/forgot-password', ['key' => 'wrong', 'password' => 'x'])->assertStatus(400);
        }
        $this->postJson('/api/forgot-password', ['key' => 'wrong', 'password' => 'x'])->assertStatus(429);
    }

    #[Test]
    public function rotating_app_key_cancels_outstanding_codes(): void
    {
        $user = $this->makeUser();
        $code = $this->requestCode($user);

        config()->set('app.key', 'base64:'.base64_encode(random_bytes(32)));

        $this->postJson('/api/forgot-password', ['key' => $code, 'password' => 'x'])->assertStatus(400);
    }

    /**
     * @param  list<string>  $codes
     */
    private function sequenceGenerator(array $codes): ResetCodeGenerator
    {
        return new class($codes) extends ResetCodeGenerator
        {
            public function __construct(private array $codes) {}

            public function generate(int $length): string
            {
                return array_shift($this->codes) ?? throw new \LogicException('Generator exhausted');
            }
        };
    }
}
