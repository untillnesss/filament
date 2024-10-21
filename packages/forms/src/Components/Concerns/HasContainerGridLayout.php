<?php

namespace Filament\Forms\Components\Concerns;

use Closure;

trait HasContainerGridLayout
{
    /**
     * @var array<string, int | string | null> | int | string | null
     */
    protected array | int | string | null $gridColumns = null;

    protected ?Closure $gridClosure = null;

    /**
     * @param  array<string, int | string | null> | int | string | Closure | null  $columns
     */
    public function grid(array | int | string | Closure | null $columns = 2): static
    {
        if ($columns instanceof Closure) {
            $this->gridClosure = $columns;

            return $this;
        }

        if (! is_array($columns)) {
            $columns = [
                'lg' => $columns,
            ];
        }

        $this->gridColumns = [
            ...($this->gridColumns ?? []),
            ...$columns,
        ];

        return $this;
    }

    /**
     * @return array<string, int | string | null> | int | string | null
     */
    public function getGridColumns(?string $breakpoint = null): array | int | string | null
    {
        $columns = $this->gridColumns ?? [
            'default' => 1,
            'sm' => null,
            'md' => null,
            'lg' => null,
            'xl' => null,
            '2xl' => null,
        ];

        if (isset($this->gridClosure)) {
            $result = $this->evaluate($this->gridClosure) ?? [];

            if (! is_array($result)) {
                $result = [
                    'lg' => $result,
                ];
            }

            $columns = [
                ...$columns,
                ...$result,
            ];
        }

        if ($breakpoint !== null) {
            return $columns[$breakpoint] ?? null;
        }

        return $columns;
    }
}
