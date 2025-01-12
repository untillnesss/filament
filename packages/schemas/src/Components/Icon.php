<?php

namespace Filament\Schemas\Components;

use Closure;
use Filament\Support\Concerns\HasColor;
use Filament\Support\Concerns\HasTooltip;

class Icon extends Component
{
    use HasColor;
    use HasTooltip;

    protected string $view = 'filament-schema::components.icon';

    protected string | Closure $icon;

    final public function __construct(string | Closure $icon)
    {
        $this->icon($icon);
    }

    public static function make(string | Closure $icon): static
    {
        $static = app(static::class, ['icon' => $icon]);
        $static->configure();

        return $static;
    }

    public function icon(string | Closure $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    public function getIcon(): string
    {
        return $this->evaluate($this->icon);
    }
}
