<?php

namespace Filament\Auth\Http\Controllers;

use Filament\Auth\Http\Requests\EmailChangeVerificationRequest;
use Filament\Auth\Http\Responses\Contracts\EmailChangeVerificationResponse;
use Filament\Notifications\Notification;

class EmailChangeVerificationController
{
    public function __invoke(EmailChangeVerificationRequest $request): EmailChangeVerificationResponse
    {
        $request->fulfill();

        Notification::make()
            ->title('Email address changed')
            ->body('Your email address has been successfully changed to ' . decrypt($request->route('email')) . '.')
            ->success()
            ->send();

        return app(EmailChangeVerificationResponse::class);
    }
}
