<?php

namespace App\Notifications\Auth;

use App\Models\MagicLoginChallenge;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MagicLoginNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public MagicLoginChallenge $challenge,
        public string $token,
        public string $code,
    ) {
        //
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        [$subject, $intro, $action] = match ($this->challenge->purpose) {
            MagicLoginChallenge::PURPOSE_CONFIRM_ACCESS => [
                __('Confirm access to your account'),
                __('Use this link or code to confirm access to your account.'),
                __('Confirm access'),
            ],
            MagicLoginChallenge::PURPOSE_EMAIL_CHANGE => [
                __('Confirm your new email address'),
                __('Use this link or code to confirm this email address for your account.'),
                __('Confirm email'),
            ],
            default => [
                __('Sign in to Koncat'),
                __('Use this link or code to sign in to Koncat.'),
                __('Continue'),
            ],
        };

        return (new MailMessage)
            ->subject($subject)
            ->line($intro)
            ->action($action, route('login.magic.link', [
                'challenge' => $this->challenge,
                'token' => $this->token,
            ]))
            ->line(__('Your code is: :code', ['code' => $this->code]))
            ->line(__('This link and code expire in :minutes minutes.', [
                'minutes' => MagicLoginChallenge::EXPIRES_MINUTES,
            ]));
    }
}
