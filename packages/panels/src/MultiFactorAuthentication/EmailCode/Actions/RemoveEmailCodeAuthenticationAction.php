<?php

namespace Filament\MultiFactorAuthentication\EmailCode\Actions;

use Closure;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\OneTimeCodeInput;
use Filament\MultiFactorAuthentication\EmailCode\Contracts\HasEmailCodeAuthentication;
use Filament\MultiFactorAuthentication\EmailCode\EmailCodeAuthentication;
use Filament\Notifications\Notification;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Support\Facades\DB;

class RemoveEmailCodeAuthenticationAction
{
    public static function make(EmailCodeAuthentication $emailCodeAuthentication): Action
    {
        return Action::make('removeEmailCodeAuthentication')
            ->label('Remove')
            ->color('danger')
            ->icon('heroicon-m-lock-open')
            ->outlined()
            ->mountUsing(function () use ($emailCodeAuthentication) {
                /** @var HasEmailCodeAuthentication $user */
                $user = Filament::auth()->user();

                $emailCodeAuthentication->sendCode($user);
            })
            ->modalWidth(MaxWidth::Medium)
            ->modalIcon('heroicon-o-lock-open')
            ->modalHeading('Remove email code authentication')
            ->modalDescription('Are you sure you want to disable email code authentication?')
            ->form([
                OneTimeCodeInput::make('code')
                    ->label('Enter the code we sent you by email')
                    ->validationAttribute('code')
                    ->belowContent(Action::make('resendEmailCode')
                        ->label('Need a new code?')
                        ->link()
                        ->action(function () use ($emailCodeAuthentication) {
                            /** @var HasEmailCodeAuthentication $user */
                            $user = Filament::auth()->user();

                            $emailCodeAuthentication->sendCode($user);

                            Notification::make()
                                ->title('We\'ve sent you a new code by email')
                                ->success()
                                ->send();
                        }))
                    ->required()
                    ->rule(function () use ($emailCodeAuthentication): Closure {
                        return function (string $attribute, mixed $value, Closure $fail) use ($emailCodeAuthentication): void {
                            if (is_string($value) && $emailCodeAuthentication->verifyCode($value)) {
                                return;
                            }

                            $fail('The code you entered is invalid.');
                        };
                    }),
            ])
            ->modalSubmitAction(fn (Action $action) => $action
                ->label('Remove email code authentication'))
            ->action(function () use ($emailCodeAuthentication) {
                /** @var HasEmailCodeAuthentication $user */
                $user = Filament::auth()->user();

                DB::transaction(function () use ($emailCodeAuthentication, $user) {
                    $emailCodeAuthentication->saveSecret($user, null);
                });

                Notification::make()
                    ->title('Email code authentication has been removed')
                    ->success()
                    ->icon('heroicon-o-lock-open')
                    ->send();
            });
    }
}
