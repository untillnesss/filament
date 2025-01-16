<?php

namespace Filament\Schemas\Components\Concerns;

use Closure;
use Filament\Actions\Action;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Illuminate\Support\Arr;

trait HasChildComponents
{
    /**
     * @var array<string, array<Component | Action> | Schema | Component | Action | string | Closure | null>
     */
    protected array $childComponents = [];

    /**
     * @param  array<Component | Action> | Closure  $components
     */
    public function components(array | Closure $components): static
    {
        $this->childComponents($components);

        return $this;
    }

    /**
     * @param  array<Component | Action> | Schema | Component | Action | string | Closure | null  $components
     */
    public function childComponents(array | Schema | Component | Action | string | Closure | null $components, string $slot = 'default'): static
    {
        $this->childComponents[$slot] = $components;

        return $this;
    }

    /**
     * @param  array<Component | Action> | Closure  $components
     */
    public function schema(array | Closure $components): static
    {
        $this->childComponents($components);

        return $this;
    }

    /**
     * @return array<Component | Action>
     */
    public function getChildComponents(?string $slot = null): array
    {
        return $this->getChildComponentContainer($slot)->getComponents();
    }

    /**
     * @return array<Component | Action>
     */
    public function getDefaultChildComponents(): array
    {
        return $this->evaluate($this->childComponents['default'] ?? []) ?? [];
    }

    /**
     * @param  array-key  $key
     */
    public function getChildComponentContainer($key = null): ?Schema
    {
        if (filled($key) && array_key_exists($key, $containers = $this->getDefaultChildComponentContainers())) {
            return $containers[$key];
        }

        $slot = $key ?? 'default';

        $components = ($slot === 'default')
            ? $this->getDefaultChildComponents()
            : $this->evaluate($this->childComponents[$slot] ?? []) ?? [];

        if (blank($components)) {
            return ($slot === 'default')
                ? $this->makeSchemaForSlot($slot)
                : null;
        }

        if ($components instanceof Schema) {
            return $components
                ->key(($slot === 'default' ? null : $slot))
                ->livewire($this->getLivewire())
                ->parentComponent($this);
        }

        return $this->makeSchemaForSlot($slot)
            ->components($this->normalizeChildComponents($components));
    }

    /**
     * @param  array<Component | Action> | Component | Action | string  $components
     * @return array<Component | Action>
     */
    protected function normalizeChildComponents(array | Component | Action | string $components): array
    {
        if (is_string($components)) {
            return [Text::make($components)];
        }

        return Arr::wrap($components);
    }

    protected function makeSchemaForSlot(string $slot): Schema
    {
        return Schema::make($this->getLivewire())
            ->key(($slot === 'default' ? null : $slot))
            ->parentComponent($this);
    }

    /**
     * @return array<Schema>
     */
    public function getChildComponentContainers(bool $withHidden = false): array
    {
        if ((! $withHidden) && $this->isHidden()) {
            return [];
        }

        return [
            ...(array_key_exists('default', $this->childComponents) ? $this->getDefaultChildComponentContainers() : []),
            ...array_reduce(
                array_keys($this->childComponents),
                function (array $carry, string $slot): array {
                    if ($slot === 'default') {
                        return $carry;
                    }

                    if ($container = $this->getChildComponentContainer($slot)) {
                        $carry[$slot] = $container;
                    }

                    return $carry;
                },
                initial: [],
            ),
        ];
    }

    /**
     * @return array<Schema>
     */
    public function getDefaultChildComponentContainers(): array
    {
        return ['default' => $this->getChildComponentContainer()];
    }

    protected function cloneChildComponents(): static
    {
        foreach ($this->childComponents as $slot => $childComponents) {
            if (is_array($childComponents)) {
                $this->childComponents[$slot] = array_map(
                    fn (Component | Action $component): Component | Action => match (true) {
                        $component instanceof Component => $component->getClone(),
                        default => clone $component,
                    },
                    $childComponents,
                );
            }

            if ($childComponents instanceof Schema) {
                $this->childComponents[$slot] = $childComponents->getClone();
            }

            if ($childComponents instanceof Component) {
                $this->childComponents[$slot] = $childComponents->getClone();
            }

            if ($childComponents instanceof Action) {
                $this->childComponents[$slot] = clone $childComponents;
            }
        }

        return $this;
    }
}
