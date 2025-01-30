<?php

namespace Filament\Support\Contracts;

use Filament\Support\Enums\IconSize;

interface HasIconSize
{
    public function getIconSize(): ?IconSize;
}
