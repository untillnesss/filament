<?php

namespace Filament\Schemas\Components;

use Closure;
use Filament\Actions\Action;

class UnorderedList extends Component
{
    protected string $view = 'filament-schema::components.unordered-list';

    protected string | Closure | null $size = null;

    /**
     * @param  array<Component | Action> | Closure  $schema
     */
    final public function __construct(array | Closure $schema = [])
    {
        $this->schema($schema);
    }

    /**
     * @param  array<Component | Action> | Closure  $schema
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
