<?php

namespace Filament\MultiFactorAuthentication\Providers\Contracts;

use Filament\Actions\Action;
use Filament\MultiFactorAuthentication\GoogleTwoFactor\Contracts\HasGoogleTwoFactorAuthentication;
use Filament\Schema\Components\Component;
use Illuminate\Contracts\Auth\Authenticatable;

interface MultiFactorAuthenticationProvider
{
    public function isEnabled(HasGoogleTwoFactorAuthentication $user): bool;

    public function getId(): string;

    public function getLabel(): string;

    /**
     * @return array<Action>
     */
    public function getActions(): array;

    /**
     * @return array<Component>
     */
    public function getLoginFormComponents(Authenticatable $user): array;
}
