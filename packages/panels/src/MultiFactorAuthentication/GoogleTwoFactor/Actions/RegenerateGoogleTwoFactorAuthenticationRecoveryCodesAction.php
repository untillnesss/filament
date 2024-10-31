<?php

namespace Filament\MultiFactorAuthentication\GoogleTwoFactor\Actions;

use Closure;
use Filament\Actions\Action;
use Filament\Actions\Contracts\HasActions;
use Filament\Facades\Filament;
use Filament\Forms\Components\OneTimeCodeInput;
use Filament\Forms\Components\TextInput;
use Filament\MultiFactorAuthentication\GoogleTwoFactor\Contracts\HasGoogleTwoFactorAuthenticationRecovery;
use Filament\MultiFactorAuthentication\GoogleTwoFactor\GoogleTwoFactorAuthentication;
use Filament\Notifications\Notification;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Contracts\View\View;

class RegenerateGoogleTwoFactorAuthenticationRecoveryCodesAction
{
    public static function make(GoogleTwoFactorAuthentication $googleTwoFactorAuthentication): Action
    {
        return Action::make('regenerateGoogleTwoFactorAuthenticationRecoveryCodes')
            ->label('Regenerate recovery codes')
            ->color('gray')
            ->icon('heroicon-m-arrow-path')
            ->outlined()
            ->modalIcon('heroicon-o-arrow-path')
            ->modalIconColor('primary')
            ->modalHeading('Regenerate two-factor authentication app recovery codes')
            ->modalDescription('If you lose your recovery codes, you can regenerate them here. Your old recovery codes will be invalidated.')
            ->form([
                OneTimeCodeInput::make('code')
                    ->label('Enter a code from the app')
                    ->validationAttribute('code')
                    ->requiredWithout('password')
                    ->rule(function () use ($googleTwoFactorAuthentication): Closure {
                        return function (string $attribute, $value, Closure $fail) use ($googleTwoFactorAuthentication): void {
                            if ($googleTwoFactorAuthentication->verifyCode($value)) {
                                return;
                            }

                            $fail('The code you entered is invalid.');
                        };
                    }),
                TextInput::make('password')
                    ->label('Or, enter your current password')
                    ->validationAttribute('password')
                    ->currentPassword(guard: Filament::getAuthGuard())
                    ->password()
                    ->revealable(filament()->arePasswordsRevealable())
                    ->dehydrated(false),
            ])
            ->modalWidth(MaxWidth::Large)
            ->modalSubmitAction(fn (Action $action) => $action
                ->label('Replace old recovery codes with new')
                ->color('danger'))
            ->action(function (Action $action, HasActions $livewire) use ($googleTwoFactorAuthentication) {
                $recoveryCodes = $googleTwoFactorAuthentication->generateRecoveryCodes();

                /** @var HasGoogleTwoFactorAuthenticationRecovery $user */
                $user = Filament::auth()->user();

                $googleTwoFactorAuthentication->saveRecoveryCodes($user, $recoveryCodes);

                $livewire->mountAction('showNewRecoveryCodes', arguments: [
                    'recoveryCodes' => $recoveryCodes,
                ]);

                Notification::make()
                    ->title('New two-factor app authentication recovery codes have been generated')
                    ->success()
                    ->send();
            })
            ->registerModalActions([
                Action::make('showNewRecoveryCodes')
                    ->modalHeading('New recovery codes')
                    ->modalDescription('Please save the following recovery codes in a safe place. You won\'t see them again, but you\'ll need them if you lose access to your app:')
                    ->modalContent(fn (array $arguments): View => view(
                        'filament-panels::multi-factor-authentication.google-two-factor.recovery-codes-modal-content',
                        $arguments,
                    ))
                    ->modalWidth(MaxWidth::Large)
                    ->closeModalByClickingAway(false)
                    ->closeModalByEscaping(false)
                    ->modalCloseButton(false)
                    ->modalSubmitAction(fn (Action $action) => $action
                        ->label('Close')
                        ->color('danger'))
                    ->modalCancelAction(false)
                    ->cancelParentActions(),
            ]);
    }
}
