<?php

namespace Filament\Schemas\Concerns;

trait HasHeadings
{
    protected int $defaultHeadingLevel = 2;

    public function defaultHeadingLevel(int $level): static
    {
        $this->defaultHeadingLevel = $level;

        return $this;
    }

    public function getDefaultHeadingLevel(): int
    {
        if ($parentComponent = $this->getParentComponent()) {
            return $parentComponent->getChildComponentContainerDefaultHeadingLevel();
        }

        return $this->defaultHeadingLevel;
    }
}
