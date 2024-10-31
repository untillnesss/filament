<?php

namespace Filament\MultiFactorAuthentication\Providers\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;

interface HasAfterLoginHook
{
    public function afterLogin(Authenticatable $user): void;
}
