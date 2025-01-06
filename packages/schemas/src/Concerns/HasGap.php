<?php

namespace Filament\Schemas\Concerns;

use Closure;

trait HasGap
{
    protected bool | Closure | null $hasGap = true;

    public function gap(bool | Closure | null $condition = true): static
    {
        $this->hasGap = $condition;

        return $this;
    }

    public function hasGap(): bool
    {
        return (bool) $this->evaluate($this->hasGap);
    }
}
