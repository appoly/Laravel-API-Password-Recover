<?php

namespace Appoly\LaravelApiPasswordHelper\Http\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPassword extends Notification
{
    use Queueable;

    /**
     * The plain reset code. Only ever held in memory; the database stores an HMAC.
     *
     * @var string
     */
    public $code;

    /**
     * Create a new notification instance.
     *
     * @param  mixed  $user
     */
    public function __construct(private $user, string $code)
    {
        $this->code = $code;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $mail = (new MailMessage);

        $mail->subject(config('LaravelApiPasswordHelper.PASSWORD_RESET_SUBJECT'));

        $mail->greeting(config('LaravelApiPasswordHelper.PASSWORD_RESET_GREETING'));

        if (is_array(config('LaravelApiPasswordHelper.PASSWORD_RESET_BEFORE_CODE'))) {
            foreach (config('LaravelApiPasswordHelper.PASSWORD_RESET_BEFORE_CODE') as $line) {
                $mail->line($line);
            }
        } else {
            $mail->line(config('LaravelApiPasswordHelper.PASSWORD_RESET_BEFORE_CODE'));
        }

        if (config('LaravelApiPasswordHelper.PASSWORD_RESET_DEEPLINK')) {
            $mail->action(
                'Reset password',
                config('LaravelApiPasswordHelper.PASSWORD_RESET_DEEPLINK').$this->code
            );
        } else {
            $mail->line($this->code);
        }

        if (is_array(config('LaravelApiPasswordHelper.PASSWORD_RESET_AFTER_CODE'))) {
            foreach (config('LaravelApiPasswordHelper.PASSWORD_RESET_AFTER_CODE') as $line) {
                $mail->line($line);
            }
        } else {
            $mail->line(config('LaravelApiPasswordHelper.PASSWORD_RESET_AFTER_CODE'));
        }

        $mail->salutation(config('LaravelApiPasswordHelper.PASSWORD_RESET_SIGN_OFF'));

        return $mail;
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
