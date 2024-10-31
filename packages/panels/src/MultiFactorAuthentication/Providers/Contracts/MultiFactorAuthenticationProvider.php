<?php

namespace Filament\MultiFactorAuthentication\Providers\Contracts;

use Filament\Schema\Components\Component;
use Illuminate\Contracts\Auth\Authenticatable;

interface MultiFactorAuthenticationProvider
{
    public function isEnabled(Authenticatable $user): bool;

    public function getId(): string;

    /**
     * @return array<Component>
     */
    public function getManagementFormComponents(): array;

    /**
     * @return array<Component>
     */
    public function getLoginFormComponents(Authenticatable $user): array;
}
