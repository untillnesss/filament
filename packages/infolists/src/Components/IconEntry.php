<?php

namespace Filament\Infolists\Components;

use BackedEnum;
use Closure;
use Filament\Infolists\Components\IconEntry\Enums\IconEntrySize;
use Filament\Support\Facades\FilamentIcon;

class IconEntry extends Entry
{
    use Concerns\HasColor {
        getColor as getBaseColor;
    }
    use Concerns\HasIcon {
        getIcon as getBaseIcon;
    }

    /**
     * @var view-string
     */
    protected string $view = 'filament-infolists::components.icon-entry';

    protected bool | Closure | null $isBoolean = null;

    /**
     * @var string | array<int | string, string | int> | Closure | null
     */
    protected string | array | Closure | null $falseColor = null;

    protected string | BackedEnum | Closure | null $falseIcon = null;

    /**
     * @var string | array<int | string, string | int> | Closure | null
     */
    protected string | array | Closure | null $trueColor = null;

    protected string | BackedEnum | Closure | null $trueIcon = null;

    protected IconEntrySize | string | Closure | null $size = null;

    public function boolean(bool | Closure $condition = true): static
    {
        $this->isBoolean = $condition;

        return $this;
    }

    /**
     * @param  string | array<int | string, string | int> | Closure | null  $color
     */
    public function false(string | BackedEnum | Closure | null $icon = null, string | array | Closure | null $color = null): static
    {
        $this->falseIcon($icon);
        $this->falseColor($color);

        return $this;
    }

    /**
     * @param  string | array<int | string, string | int> | Closure | null  $color
     */
    public function falseColor(string | array | Closure | null $color): static
    {
        $this->boolean();
        $this->falseColor = $color;

        return $this;
    }

    public function falseIcon(string | BackedEnum | Closure | null $icon): static
    {
        $this->boolean();
        $this->falseIcon = $icon;

        return $this;
    }

    /**
     * @param  string | array<int | string, string | int> | Closure | null  $color
     */
    public function true(string | BackedEnum | Closure | null $icon = null, string | array | Closure | null $color = null): static
    {
        $this->trueIcon($icon);
        $this->trueColor($color);

        return $this;
    }

    /**
     * @param  string | array<int | string, string | int> | Closure | null  $color
     */
    public function trueColor(string | array | Closure | null $color): static
    {
        $this->boolean();
        $this->trueColor = $color;

        return $this;
    }

    public function trueIcon(string | BackedEnum | Closure | null $icon): static
    {
        $this->boolean();
        $this->trueIcon = $icon;

        return $this;
    }

    public function size(IconEntrySize | string | Closure | null $size): static
    {
        $this->size = $size;

        return $this;
    }

    public function getSize(mixed $state): IconEntrySize | string | null
    {
        return $this->evaluate($this->size, [
            'state' => $state,
        ]);
    }

    public function getIcon(mixed $state): string | BackedEnum | null
    {
        if (filled($icon = $this->getBaseIcon($state))) {
            return $icon;
        }

        if (! $this->isBoolean()) {
            return null;
        }

        if ($state === null) {
            return null;
        }

        return $state ? $this->getTrueIcon() : $this->getFalseIcon();
    }

    /**
     * @return string | array<int | string, string | int> | null
     */
    public function getColor(mixed $state): string | array | null
    {
        if (filled($color = $this->getBaseColor($state))) {
            return $color;
        }

        if (! $this->isBoolean()) {
            return null;
        }

        if ($state === null) {
            return null;
        }

        return $state ? $this->getTrueColor() : $this->getFalseColor();
    }

    /**
     * @return string | array<int | string, string | int>
     */
    public function getFalseColor(): string | array
    {
        return $this->evaluate($this->falseColor) ?? 'danger';
    }

    public function getFalseIcon(): string | BackedEnum
    {
        return $this->evaluate($this->falseIcon)
            ?? FilamentIcon::resolve('infolists::components.icon-entry.false')
            ?? 'heroicon-o-x-circle';
    }

    /**
     * @return string | array<int | string, string | int>
     */
    public function getTrueColor(): string | array
    {
        return $this->evaluate($this->trueColor) ?? 'success';
    }

    public function getTrueIcon(): string | BackedEnum
    {
        return $this->evaluate($this->trueIcon)
            ?? FilamentIcon::resolve('infolists::components.icon-entry.true')
            ?? 'heroicon-o-check-circle';
    }

    public function isBoolean(): bool
    {
        if (blank($this->isBoolean)) {
            $this->isBoolean = $this->getRecord()?->hasCast($this->getName(), ['bool', 'boolean']);
        }

        return (bool) $this->evaluate($this->isBoolean);
    }
}
