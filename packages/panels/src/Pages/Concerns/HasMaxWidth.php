<?php

namespace Filament\Pages\Concerns;

use Filament\Support\Enums\Width;

trait HasMaxWidth
{
    protected ?string $maxWidth = null;

    public function getMaxWidth(): Width | string | null
    {
        return $this->maxWidth;
    }
}
