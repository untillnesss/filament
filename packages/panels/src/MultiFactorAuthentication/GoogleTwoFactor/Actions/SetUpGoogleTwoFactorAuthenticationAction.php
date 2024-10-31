<?php

namespace Filament\MultiFactorAuthentication\GoogleTwoFactor\Actions;

use Closure;
use Filament\Actions\Action;
use Filament\Actions\Contracts\HasActions;
use Filament\Facades\Filament;
use Filament\Forms\Components\OneTimeCodeInput;
use Filament\MultiFactorAuthentication\GoogleTwoFactor\Contracts\HasGoogleTwoFactorAuthentication;
use Filament\MultiFactorAuthentication\GoogleTwoFactor\Contracts\HasGoogleTwoFactorAuthenticationRecovery;
use Filament\MultiFactorAuthentication\GoogleTwoFactor\GoogleTwoFactorAuthentication;
use Filament\Notifications\Notification;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class SetUpGoogleTwoFactorAuthenticationAction
{
    public static function make(GoogleTwoFactorAuthentication $googleTwoFactorAuthentication): Action
    {
        return Action::make('setUpGoogleTwoFactorAuthentication')
            ->label('Set up')
            ->color('primary')
            ->icon('heroicon-m-lock-closed')
            ->outlined()
            ->mountUsing(function (HasActions $livewire) use ($googleTwoFactorAuthentication) {
                $livewire->mergeMountedActionArguments([
                    'encrypted' => encrypt([
                        'secret' => $googleTwoFactorAuthentication->generateSecret(),
                        ...($googleTwoFactorAuthentication->isRecoverable()
                            ? ['recoveryCodes' => $googleTwoFactorAuthentication->generateRecoveryCodes()]
                            : []),
                        'userId' => Filament::auth()->id(),
                    ]),
                ]);
            })
            ->modalIcon('heroicon-o-lock-closed')
            ->modalIconColor('primary')
            ->modalHeading('Set up two-factor authentication app')
            ->modalDescription(new HtmlString(Blade::render(<<<'BLADE'
                        You'll need an app like Google Authenticator (<x-filament::link href="https://itunes.apple.com/us/app/google-authenticator/id388497605" target="_blank">iOS</x-filament::link>, <x-filament::link href="https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2" target="_blank">Android</x-filament::link>) to complete this process.
                        BLADE)))
            ->modalContent(fn (array $arguments): View => view(
                'filament-panels::multi-factor-authentication.google-two-factor.set-up-modal-content',
                [
                    ...$encrypted = decrypt($arguments['encrypted']),
                    'isRecoverable' => $googleTwoFactorAuthentication->isRecoverable(),
                    'qr' => $googleTwoFactorAuthentication->generateQRCodeDataUri($encrypted['secret']),
                ],
            ))
            ->form(fn (array $arguments): array => [
                OneTimeCodeInput::make('code')
                    ->label('Enter a code from the app')
                    ->belowContent('You\'ll need to put a code like this in each time you sign in.')
                    ->validationAttribute('code')
                    ->required()
                    ->rule(function () use ($arguments, $googleTwoFactorAuthentication): Closure {
                        return function (string $attribute, $value, Closure $fail) use ($arguments, $googleTwoFactorAuthentication): void {
                            if ($googleTwoFactorAuthentication->verifyCode($value, decrypt($arguments['encrypted'])['secret'])) {
                                return;
                            }

                            $fail('The code you entered is invalid.');
                        };
                    }),
            ])
            ->modalWidth(MaxWidth::Large)
            ->modalSubmitAction(fn (Action $action) => $action
                ->label('Enable two-factor authentication')
                ->color('danger'))
            ->action(function (array $arguments) use ($googleTwoFactorAuthentication) {
                /** @var Authenticatable&HasGoogleTwoFactorAuthentication&HasGoogleTwoFactorAuthenticationRecovery $user */
                $user = Filament::auth()->user();

                $encrypted = decrypt($arguments['encrypted']);

                if ($user->getAuthIdentifier() !== $encrypted['userId']) {
                    // Avoid encrypted arguments being passed between users by verifying that the authenticated
                    // user is the same as the user that the encrypted arguments were issued for.
                    return;
                }

                DB::transaction(function () use ($googleTwoFactorAuthentication, $encrypted, $user) {
                    $googleTwoFactorAuthentication->saveSecret($user, $encrypted['secret']);

                    if ($googleTwoFactorAuthentication->isRecoverable()) {
                        $googleTwoFactorAuthentication->saveRecoveryCodes($user, $encrypted['recoveryCodes']);
                    }
                });

                Notification::make()
                    ->title('Two-factor app authentication has been enabled')
                    ->success()
                    ->send();
            });
    }
}
