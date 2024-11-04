<?php

namespace Filament\MultiFactorAuthentication\Providers\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;

interface HasBeforeChallengeHook
{
    public function beforeChallenge(Authenticatable $user): void;
}
