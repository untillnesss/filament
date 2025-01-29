<?php

namespace Filament\Support\View\Components;

use Filament\Support\View\Components\Contracts\HasColor;
use Filament\Support\View\Components\Contracts\HasDefaultGrayColor;

class Badge implements HasColor, HasDefaultGrayColor
{
    /**
     * @param  array<int | string, string | int>  $color
     * @return array<string>
     */
    public function getColorClasses(array $color): array
    {
        return [];
    }
}
