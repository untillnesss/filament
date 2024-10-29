<?php

namespace Filament\Schema\Contracts;

interface HasRenderHookScopes
{
    /**
     * @return array<string>
     */
    public function getRenderHookScopes(): array;
}
