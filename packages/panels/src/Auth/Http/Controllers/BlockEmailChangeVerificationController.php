<?php

namespace Filament\Auth\Http\Controllers;

use Filament\Auth\Http\Requests\BlockEmailChangeVerificationRequest;
use Filament\Auth\Http\Responses\BlockEmailChangeVerificationResponse;
use Filament\Notifications\Notification;

class BlockEmailChangeVerificationController
{
    public function __invoke(BlockEmailChangeVerificationRequest $request): BlockEmailChangeVerificationResponse
    {
        $isSuccessful = $request->fulfill();

        if ($isSuccessful) {
            Notification::make()
                ->title('Email address change blocked')
                ->body('You have successfully blocked an email address change attempt to ' . decrypt($request->route('email')) . '. If you did not make the original request, please contact us immediately.')
                ->success()
                ->persistent()
                ->send();
        } else {
            Notification::make()
                ->title('Failed to block email address change')
                ->body('Unfortunately, you were unable to prevent the email address from being changed to ' . decrypt($request->route('email')) . ', since it was already verified before you blocked it. If you did not make the original request, please contact us immediately to regain access to your account.')
                ->danger()
                ->persistent()
                ->send();
        }

        return app(BlockEmailChangeVerificationResponse::class);
    }
}
