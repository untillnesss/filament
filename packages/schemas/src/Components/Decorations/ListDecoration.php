<?php

namespace Filament\Schemas\Components\Decorations;

use Closure;
use Filament\Schemas\Components\Component;

class ListDecoration extends Component
{
    protected string $view = 'filament-schema::components.decorations.list-decoration';

    protected string | Closure | null $size = null;

    /**
     * @param  array<Component> | Closure  $schema
     */
    final public function __construct(array | Closure $schema = [])
    {
        $this->schema($schema);
    }

    /**
     * @param  array<Component> | Closure  $schema
     */
    public static function make(array | Closure $schema = []): static
    {
        $static = app(static::class, ['schema' => $schema]);
        $static->configure();

        return $static;
    }

    public function size(string | Closure | null $size): static
    {
        $this->size = $size;

        return $this;
    }

    public function getSize(): ?string
    {
        return $this->evaluate($this->size);
    }
}
