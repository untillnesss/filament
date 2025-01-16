<?php

namespace Filament\Actions\Concerns;

use Closure;
use Filament\Actions\Action;
use Filament\Schemas\Components\Component;

trait HasInfolist
{
    /**
     * @param  array<Component | Action> | Closure | null  $infolist
     */
    public function infolist(array | Closure | null $infolist): static
    {
        $this->schema($infolist);

        return $this;
    }
}
