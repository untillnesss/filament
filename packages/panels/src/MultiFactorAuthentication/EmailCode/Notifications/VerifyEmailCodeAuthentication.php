<?php

namespace Filament\MultiFactorAuthentication\EmailCode\Notifications;

use Exception;
use Filament\Facades\Filament;
use Filament\MultiFactorAuthentication\EmailCode\Contracts\HasEmailCodeAuthentication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VerifyEmailCodeAuthentication extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $code,
        public int $codeWindow,
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
        if (! ($notifiable instanceof HasEmailCodeAuthentication)) {
            throw new Exception('The user model must implement the [' . HasEmailCodeAuthentication::class . '] interface to use email code authentication.');
        }

        $brandName = strip_tags(Filament::getBrandName());

        $expiryMinutes = ceil($this->codeWindow / 2);

        return (new MailMessage)
            ->subject('Here\'s your sign-in code' . (filled($brandName) ? " for {$brandName}" : ''))
            ->line("Your sign-in code is: {$this->code}")
            ->line("This code will expire in {$expiryMinutes} minutes.");
    }
}
