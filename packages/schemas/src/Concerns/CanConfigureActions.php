<?php

namespace Filament\Schemas\Concerns;

use Closure;
use Filament\Actions\Action;

trait CanConfigureActions
{
    protected ?Closure $configureActionsUsing = null;

    public function configureActionsUsing(?Closure $callback): static
    {
        $this->configureActionsUsing = $callback;

        return $this;
    }

    public function configureAction(Action $action): Action
    {
        if (! $this->configureActionsUsing) {
            return $action;
        }

        return ($this->configureActionsUsing)($action) ?? $action;
    }
}
