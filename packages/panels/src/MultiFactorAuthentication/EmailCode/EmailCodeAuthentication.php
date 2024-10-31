<?php

namespace Filament\MultiFactorAuthentication\EmailCode;

use Closure;
use Exception;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\OneTimeCodeInput;
use Filament\MultiFactorAuthentication\EmailCode\Actions\RemoveEmailCodeAuthenticationAction;
use Filament\MultiFactorAuthentication\EmailCode\Actions\SetUpEmailCodeAuthenticationAction;
use Filament\MultiFactorAuthentication\EmailCode\Contracts\HasEmailCodeAuthentication;
use Filament\MultiFactorAuthentication\EmailCode\Notifications\VerifyEmailCodeAuthentication;
use Filament\MultiFactorAuthentication\Providers\Contracts\HasAfterLoginHook;
use Filament\MultiFactorAuthentication\Providers\Contracts\MultiFactorAuthenticationProvider;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Auth\Authenticatable;
use PragmaRX\Google2FAQRCode\Google2FA;

class EmailCodeAuthentication implements HasAfterLoginHook, MultiFactorAuthenticationProvider
{
    /**
     * 8 keys (respectively 4 minutes) past and future
     */
    protected int $codeWindow = 8;

    protected string $codeNotification = VerifyEmailCodeAuthentication::class;

    public function __construct(
        protected Google2FA $google2FA,
    ) {}

    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'email_code';
    }

    public function getLabel(): string
    {
        return 'Email code authentication';
    }

    public function isEnabled(Authenticatable $user): bool
    {
        if (! ($user instanceof HasEmailCodeAuthentication)) {
            throw new Exception('The user model must implement the [' . HasEmailCodeAuthentication::class . '] interface to use email code authentication.');
        }

        return $user->hasEmailCodeAuthentication();
    }

    public function sendCode(HasEmailCodeAuthentication $user, ?string $secret = null): void
    {
        if (! method_exists($user, 'notify')) {
            $userClass = $user::class;

            throw new Exception("Model [{$userClass}] does not have a [notify()] method.");
        }

        $user->notify(app($this->getCodeNotification(), [
            'code' => $this->google2FA->getCurrentOtp($secret ?? $this->getSecret($user)),
            'codeWindow' => $this->getCodeWindow(),
        ]));
    }

    public function getSecret(HasEmailCodeAuthentication $user): string
    {
        return $user->getEmailCodeAuthenticationSecret();
    }

    public function saveSecret(HasEmailCodeAuthentication $user, ?string $secret): void
    {
        $user->saveEmailCodeAuthenticationSecret($secret);
    }

    public function generateSecret(): string
    {
        return $this->google2FA->generateSecretKey();
    }

    public function verifyCode(string $code, ?string $secret = null): bool
    {
        /** @var HasEmailCodeAuthentication $user */
        $user = Filament::auth()->user();

        return $this->google2FA->verifyKey($secret ?? $this->getSecret($user), $code, $this->getCodeWindow());
    }

    /**
     * @return array<Action>
     */
    public function getActions(): array
    {
        $user = Filament::auth()->user();

        return [
            SetUpEmailCodeAuthenticationAction::make($this)
                ->hidden(fn (): bool => $this->isEnabled($user)),
            RemoveEmailCodeAuthenticationAction::make($this)
                ->visible(fn (): bool => $this->isEnabled($user)),
        ];
    }

    public function codeWindow(int $window): static
    {
        $this->codeWindow = $window;

        return $this;
    }

    public function getCodeWindow(): int
    {
        return $this->codeWindow;
    }

    public function afterLogin(Authenticatable $user): void
    {
        if (! ($user instanceof HasEmailCodeAuthentication)) {
            throw new Exception('The user model must implement the [' . HasEmailCodeAuthentication::class . '] interface to use email code authentication.');
        }

        $this->sendCode($user);
    }

    /**
     * @param  Authenticatable&HasEmailCodeAuthentication  $user
     */
    public function getLoginFormComponents(Authenticatable $user): array
    {
        return [
            OneTimeCodeInput::make('code')
                ->label('Enter the code we sent you by email')
                ->validationAttribute('code')
                ->belowContent(Action::make('resendEmailCode')
                    ->label('Send a new code by email')
                    ->link()
                    ->action(function () use ($user) {
                        $this->sendCode($user);

                        Notification::make()
                            ->title('We\'ve sent you a new code by email')
                            ->success()
                            ->send();
                    }))
                ->required()
                ->rule(function () use ($user): Closure {
                    return function (string $attribute, $value, Closure $fail) use ($user): void {
                        if ($this->verifyCode($value, $this->getSecret($user))) {
                            return;
                        }

                        $fail('The code you entered is invalid.');
                    };
                }),
        ];
    }

    public function codeNotification(string $notification): static
    {
        $this->codeNotification = $notification;

        return $this;
    }

    public function getCodeNotification(): string
    {
        return $this->codeNotification;
    }
}
