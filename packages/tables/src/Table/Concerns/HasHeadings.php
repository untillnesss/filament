<?php

namespace Filament\Tables\Table\Concerns;

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
        return $this->defaultHeadingLevel;
    }

    public function getHeadingLevel(int $index = 0): int
    {
        return $this->getDefaultHeadingLevel() + $index;
    }

    public function getHeadingTag(int $index = 0): string
    {
        $level = $this->getHeadingLevel($index);

        if ($level > 6) {
            return 'p';
        }

        return "h{$level}";
    }
}
