<?php

namespace Filament\Auth\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NoticeOfEmailChangeRequest extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $newEmail,
        public string $blockVerificationUrl,
    ) {}

    /**
     * @return array<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Notice of Email Change Request')
            ->line('We received a request to change the email address associated with your account. Your password was used to confirm this change.')
            ->line('Once verified, the new email address on your account will be: ' . $this->newEmail)
            ->line('If you did not make this request, please contact us immediately.');
    }
}
