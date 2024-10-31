<?php

namespace Filament\MultiFactorAuthentication\GoogleTwoFactor;

use Closure;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\OneTimeCodeInput;
use Filament\Forms\Components\TextInput;
use Filament\MultiFactorAuthentication\GoogleTwoFactor\Contracts\HasGoogleTwoFactorAuthentication;
use Filament\MultiFactorAuthentication\GoogleTwoFactor\Contracts\HasGoogleTwoFactorAuthenticationRecovery;
use Filament\Notifications\Notification;
use Filament\Schema\Components\Utilities\Get;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Support\Facades\DB;

class RemoveGoogleTwoFactorAuthenticationAction
{
    public static function make(GoogleTwoFactorAuthentication $googleTwoFactorAuthentication): Action
    {
        $isRecoverable = $googleTwoFactorAuthentication->isRecoverable();

        return Action::make('removeGoogleTwoFactorAuthentication')
            ->label('Remove')
            ->color('danger')
            ->icon('heroicon-m-lock-open')
            ->outlined()
            ->modalIcon('heroicon-o-lock-open')
            ->modalHeading('Remove two-factor authentication app')
            ->modalDescription('Are you sure you want to disable your two-factor authentication app?')
            ->form([
                OneTimeCodeInput::make('code')
                    ->label('Enter a code from the app')
                    ->validationAttribute('code')
                    ->required(fn (Get $get): bool => (! $isRecoverable) || blank($get('recoveryCode')))
                    ->rule(function () use ($googleTwoFactorAuthentication): Closure {
                        return function (string $attribute, mixed $value, Closure $fail) use ($googleTwoFactorAuthentication): void {
                            if (is_string($value) && $googleTwoFactorAuthentication->verifyCode($value)) {
                                return;
                            }

                            $fail('The code you entered is invalid.');
                        };
                    }),
                TextInput::make('recoveryCode')
                    ->label('Or, enter a recovery code')
                    ->password()
                    ->revealable(Filament::arePasswordsRevealable())
                    ->validationAttribute('recovery code')
                    ->belowContent('You can use a recovery code to disable two-factor authentication if you lose access to your app.')
                    ->rule(function () use ($googleTwoFactorAuthentication): Closure {
                        return function (string $attribute, mixed $value, Closure $fail) use ($googleTwoFactorAuthentication): void {
                            if (blank($value)) {
                                return;
                            }

                            if (is_string($value) && $googleTwoFactorAuthentication->verifyRecoveryCode($value)) {
                                return;
                            }

                            $fail('The recovery code you entered is invalid.');
                        };
                    })
                    ->visible($isRecoverable),
            ])
            ->modalWidth(MaxWidth::Medium)
            ->modalSubmitAction(fn (Action $action) => $action
                ->label('Remove two-factor authentication'))
            ->action(function () use ($googleTwoFactorAuthentication, $isRecoverable) {
                /** @var HasGoogleTwoFactorAuthentication&HasGoogleTwoFactorAuthenticationRecovery $user */
                $user = Filament::auth()->user();

                DB::transaction(function () use ($googleTwoFactorAuthentication, $isRecoverable, $user) {
                    $googleTwoFactorAuthentication->saveSecret($user, null);

                    if ($isRecoverable) {
                        $googleTwoFactorAuthentication->saveRecoveryCodes($user, null);
                    }
                });

                Notification::make()
                    ->title('Two-factor app authentication has been removed')
                    ->success()
                    ->send();
            });
    }
}
