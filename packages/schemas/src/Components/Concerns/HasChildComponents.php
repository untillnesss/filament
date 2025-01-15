<?php

namespace Filament\Schemas\Components\Concerns;

use Closure;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;

trait HasChildComponents
{
    /**
     * @var array<string, array<Component> | Closure>
     */
    protected array | Closure $childComponents = [];

    /**
     * @param  array<Component> | Closure  $components
     */
    public function components(array | Closure $components, string $slot = 'default'): static
    {
        $this->childComponents($components, $slot);

        return $this;
    }

    /**
     * @param  array<Component> | Closure  $components
     */
    public function childComponents(array | Closure $components, string $slot = 'default'): static
    {
        $this->childComponents[$slot] = $components;

        return $this;
    }

    /**
     * @param  array<Component> | Closure  $components
     */
    public function schema(array | Closure $components, string $slot = 'default'): static
    {
        $this->childComponents($components, $slot);

        return $this;
    }

    /**
     * @return array<Component>
     */
    public function getChildComponents(string $slot = 'default'): array
    {
        if ($slot === 'default') {
            return $this->getDefaultChildComponents();
        }

        return $this->evaluate($this->childComponents[$slot] ?? []);
    }

    /**
     * @return array<Component>
     */
    public function getDefaultChildComponents(): array
    {
        return $this->evaluate($this->childComponents['default'] ?? []);
    }

    /**
     * @param  array-key  $key
     */
    public function getChildComponentContainer($key = null): Schema
    {
        if (filled($key) && array_key_exists($key, $containers = $this->getChildComponentContainers())) {
            return $containers[$key];
        }

        return Schema::make($this->getLivewire())
            ->parentComponent($this)
            ->components($this->getChildComponents());
    }

    /**
     * @return array<Schema>
     */
    public function getChildComponentContainers(bool $withHidden = false): array
    {
        if (! $this->hasChildComponentContainer($withHidden)) {
            return [];
        }

        return $this->getDefaultChildComponentContainers();
    }

    /**
     * @return array<Schema>
     */
    public function getDefaultChildComponentContainers(): array
    {
        return [$this->getChildComponentContainer()];
    }

    public function hasChildComponentContainer(bool $withHidden = false): bool
    {
        if ((! $withHidden) && $this->isHidden()) {
            return false;
        }

        if ($this->getChildComponents() === []) {
            return false;
        }

        return true;
    }
}
