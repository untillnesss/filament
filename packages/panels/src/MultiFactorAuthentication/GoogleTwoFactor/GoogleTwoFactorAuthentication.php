<?php

namespace Filament\MultiFactorAuthentication\GoogleTwoFactor;

use Closure;
use Exception;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\OneTimeCodeInput;
use Filament\Forms\Components\TextInput;
use Filament\MultiFactorAuthentication\GoogleTwoFactor\Actions\RegenerateGoogleTwoFactorAuthenticationRecoveryCodesAction;
use Filament\MultiFactorAuthentication\GoogleTwoFactor\Actions\RemoveGoogleTwoFactorAuthenticationAction;
use Filament\MultiFactorAuthentication\GoogleTwoFactor\Actions\SetUpGoogleTwoFactorAuthenticationAction;
use Filament\MultiFactorAuthentication\GoogleTwoFactor\Contracts\HasGoogleTwoFactorAuthentication;
use Filament\MultiFactorAuthentication\GoogleTwoFactor\Contracts\HasGoogleTwoFactorAuthenticationRecovery;
use Filament\MultiFactorAuthentication\Providers\Contracts\MultiFactorAuthenticationProvider;
use Filament\Schema\Components\Actions;
use Filament\Schema\Components\Component;
use Filament\Schema\Components\Utilities\Get;
use Filament\Schema\Components\Utilities\Set;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use PragmaRX\Google2FAQRCode\Google2FA;

class GoogleTwoFactorAuthentication implements MultiFactorAuthenticationProvider
{
    protected bool $isRecoverable = false;

    protected bool $canRegenerateRecoveryCodes = true;

    protected int $recoveryCodeCount = 8;

    protected ?string $brandName = null;

    /**
     * 8 keys (respectively 4 minutes) past and future
     */
    protected int $codeWindow = 8;

    public function __construct(
        protected Google2FA $google2FA,
    ) {}

    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'google_two_factor';
    }

    public function isEnabled(Authenticatable $user): bool
    {
        if (! ($user instanceof HasGoogleTwoFactorAuthentication)) {
            throw new Exception('The user model must implement the [' . HasGoogleTwoFactorAuthentication::class . '] interface to use email code authentication.');
        }

        return $user->hasGoogleTwoFactorAuthentication();
    }

    public function getHolderName(HasGoogleTwoFactorAuthentication $user): string
    {
        return $user->getGoogleTwoFactorAuthenticationHolderName();
    }

    public function getSecret(HasGoogleTwoFactorAuthentication $user): string
    {
        return $user->getGoogleTwoFactorAuthenticationSecret();
    }

    public function saveSecret(HasGoogleTwoFactorAuthentication $user, ?string $secret): void
    {
        $user->saveGoogleTwoFactorAuthenticationSecret($secret);
    }

    /**
     * @return array<string>
     */
    public function getRecoveryCodes(HasGoogleTwoFactorAuthenticationRecovery $user): array
    {
        return $user->getGoogleTwoFactorAuthenticationRecoveryCodes();
    }

    /**
     * @param  array<string> | null  $codes
     */
    public function saveRecoveryCodes(HasGoogleTwoFactorAuthenticationRecovery $user, ?array $codes): void
    {
        $user->saveGoogleTwoFactorAuthenticationRecoveryCodes($codes);
    }

    public function generateSecret(): string
    {
        return $this->google2FA->generateSecretKey();
    }

    public function generateQRCodeDataUri(string $secret): string
    {
        /** @var HasGoogleTwoFactorAuthentication $user */
        $user = Filament::auth()->user();

        return $this->google2FA->getQRCodeInline(
            $this->getBrandName(),
            $this->getHolderName($user),
            $secret,
        );
    }

    /**
     * @return array<string>
     */
    public function generateRecoveryCodes(): array
    {
        return Collection::times($this->getRecoveryCodeCount(), fn (): string => Str::random(10) . '-' . Str::random(10))->all();
    }

    public function verifyCode(string $code, ?string $secret = null): bool
    {
        /** @var HasGoogleTwoFactorAuthentication $user */
        $user = Filament::auth()->user();

        return $this->google2FA->verifyKey($secret ?? $this->getSecret($user), $code, $this->getCodeWindow());
    }

    public function verifyRecoveryCode(string $recoveryCode, ?HasGoogleTwoFactorAuthenticationRecovery $user = null): bool
    {
        $user ??= Filament::auth()->user();

        /** @var HasGoogleTwoFactorAuthenticationRecovery $user */

        return in_array($recoveryCode, $this->getRecoveryCodes($user));
    }

    /**
     * @return array<Component>
     */
    public function getManagementFormComponents(): array
    {
        return [
            Actions::make($this->getActions())
                ->label('Two factor authentication app'),
        ];
    }

    /**
     * @return array<Action>
     */
    public function getActions(): array
    {
        $user = Filament::auth()->user();

        return [
            SetUpGoogleTwoFactorAuthenticationAction::make($this)
                ->hidden(fn (): bool => $this->isEnabled($user)),
            RegenerateGoogleTwoFactorAuthenticationRecoveryCodesAction::make($this)
                ->visible(fn (): bool => $this->isEnabled($user) && $this->isRecoverable() && $this->canRegenerateRecoveryCodes()),
            RemoveGoogleTwoFactorAuthenticationAction::make($this)
                ->visible(fn (): bool => $this->isEnabled($user)),
        ];
    }

    public function recoverable(bool $condition = true): static
    {
        $this->isRecoverable = $condition;

        return $this;
    }

    public function isRecoverable(): bool
    {
        return $this->isRecoverable;
    }

    public function regenerableRecoveryCodes(bool $condition = true): static
    {
        $this->canRegenerateRecoveryCodes = $condition;

        return $this;
    }

    public function canRegenerateRecoveryCodes(): bool
    {
        return $this->canRegenerateRecoveryCodes;
    }

    public function brandName(?string $brandName): static
    {
        $this->brandName = $brandName;

        return $this;
    }

    public function getBrandName(): string
    {
        return $this->brandName ?? strip_tags(Filament::getBrandName());
    }

    public function recoveryCodeCount(int $count): static
    {
        $this->recoveryCodeCount = $count;

        return $this;
    }

    public function getRecoveryCodeCount(): int
    {
        return $this->recoveryCodeCount;
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

    /**
     * @param  Authenticatable&HasGoogleTwoFactorAuthentication&HasGoogleTwoFactorAuthenticationRecovery  $user
     */
    public function getLoginFormComponents(Authenticatable $user): array
    {
        $isRecoverable = $this->isRecoverable();

        return [
            OneTimeCodeInput::make('code')
                ->label('Enter the code from your authentication app')
                ->belowContent(fn (Get $get): Action => Action::make('useRecoveryCode')
                    ->label('Use a recovery code instead')
                    ->link()
                    ->action(fn (Set $set) => $set('useRecoveryCode', true))
                    ->visible(fn (): bool => $isRecoverable && (! $get('useRecoveryCode'))))
                ->validationAttribute('code')
                ->required(fn (Get $get): bool => (! $isRecoverable) || blank($get('recoveryCode')))
                ->rule(function () use ($user): Closure {
                    return function (string $attribute, $value, Closure $fail) use ($user): void {
                        if ($this->verifyCode($value, $this->getSecret($user))) {
                            return;
                        }

                        $fail('The code you entered is invalid.');
                    };
                }),
            TextInput::make('recoveryCode')
                ->label('Or, enter a recovery code')
                ->validationAttribute('recovery code')
                ->password()
                ->revealable(Filament::arePasswordsRevealable())
                ->rule(function () use ($user): Closure {
                    return function (string $attribute, mixed $value, Closure $fail) use ($user): void {
                        if (blank($value)) {
                            return;
                        }

                        if (is_string($value) && $this->verifyRecoveryCode($value, $user)) {
                            return;
                        }

                        $fail('The recovery code you entered is invalid.');
                    };
                })
                ->visible(fn (Get $get): bool => $isRecoverable && $get('useRecoveryCode'))
                ->live(onBlur: true),
        ];
    }
}
