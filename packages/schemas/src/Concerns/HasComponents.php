<?php

namespace Filament\Schemas\Concerns;

use Closure;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Field;
use Filament\Schemas\Components\Component;
use Illuminate\Support\Collection;

trait HasComponents
{
    /**
     * @var array<Component | Action | ActionGroup> | Closure
     */
    protected array | Closure $components = [];

    /**
     * @var array<array<array<array<array<string, Component>>>>>
     */
    protected array $cachedFlatComponents = [];

    /**
     * @param  array<Component | Action | ActionGroup> | Closure  $components
     */
    public function components(array | Closure $components): static
    {
        $this->components = $components;

        return $this;
    }

    /**
     * @param  array<Component | Action | ActionGroup> | Closure  $components
     */
    public function schema(array | Closure $components): static
    {
        $this->components($components);

        return $this;
    }

    public function getAction(string $actionName, ?string $nestedContainerKey = null): ?Action
    {
        foreach ($this->getComponents() as $component) {
            if (blank($nestedContainerKey)) {
                if (
                    ($component instanceof Action) &&
                    ($component->getName() === $actionName)
                ) {
                    return $component;
                }

                if (
                    ($component instanceof ActionGroup) &&
                    ($action = ($component->getFlatActions()[$actionName] ?? null))
                ) {
                    return $action;
                }
            }

            if (($component instanceof Action) || ($component instanceof ActionGroup)) {
                continue;
            }

            $componentKey = $component->getKey(isAbsolute: false);

            if (filled($componentKey)) {
                if (blank($nestedContainerKey)) {
                    continue;
                }

                if (
                    ($nestedContainerKey !== $componentKey) &&
                    (! str($nestedContainerKey)->startsWith("{$componentKey}."))
                ) {
                    continue;
                }

                $componentNestedContainerKey = ($nestedContainerKey === $componentKey)
                    ? null
                    : (string) str($nestedContainerKey)->after("{$componentKey}.");
            } else {
                $componentNestedContainerKey = $nestedContainerKey;
            }

            foreach ($component->getChildComponentContainers() as $childComponentContainer) {
                $childComponentContainerKey = $childComponentContainer->getKey(isAbsolute: false);

                if (filled($childComponentContainerKey)) {
                    if (blank($componentNestedContainerKey)) {
                        continue;
                    }

                    if (
                        ($componentNestedContainerKey !== $childComponentContainerKey)
                        && (! str($componentNestedContainerKey)->startsWith("{$childComponentContainerKey}."))
                    ) {
                        continue;
                    }

                    $childComponentContainerNestedContainerKey = ($componentNestedContainerKey === $childComponentContainerKey)
                        ? null
                        : (string) str($componentNestedContainerKey)->after("{$childComponentContainerKey}.");
                } else {
                    $childComponentContainerNestedContainerKey = $componentNestedContainerKey;
                }

                if ($action = $childComponentContainer->getAction($actionName, $childComponentContainerNestedContainerKey)) {
                    return $action;
                }
            }
        }

        return null;
    }

    public function getComponent(string | Closure $findComponentUsing, bool $withActions = true, bool $withHidden = false, bool $isAbsoluteKey = false): Component | Action | ActionGroup | null
    {
        if (! is_string($findComponentUsing)) {
            return collect($this->getFlatComponents($withActions, $withHidden))->first($findComponentUsing);
        }

        if ((! $isAbsoluteKey) && filled($key = $this->getKey())) {
            $findComponentUsing = "{$key}.$findComponentUsing";
        }

        return $this->getFlatComponents($withActions, $withHidden, withAbsoluteKeys: true)[$findComponentUsing] ?? null;
    }

    /**
     * @return array<Field>
     */
    public function getFlatFields(bool $withHidden = false, bool $withAbsoluteKeys = false): array
    {
        return collect($this->getFlatComponents(withActions: false, withHidden: $withHidden, withAbsoluteKeys: $withAbsoluteKeys))
            ->whereInstanceOf(Field::class)
            ->all();
    }

    /**
     * @return array<Component | Action | ActionGroup>
     */
    public function getFlatComponents(bool $withActions = true, bool $withHidden = false, bool $withAbsoluteKeys = false, ?string $containerKey = null): array
    {
        $containerKey ??= $this->getKey();

        return $this->cachedFlatComponents[$withActions][$withHidden][$withAbsoluteKeys][$containerKey] ??= array_reduce(
            $this->getComponents($withActions, $withHidden),
            function (array $carry, Component | Action | ActionGroup $component) use ($containerKey, $withActions, $withHidden, $withAbsoluteKeys): array {
                if (($component instanceof Action) || ($component instanceof ActionGroup)) {
                    $carry[] = $component;

                    return $carry;
                }

                $componentKey = $component->getKey();

                if (blank($componentKey)) {
                    $carry[] = $component;
                } elseif ((! $withAbsoluteKeys) && filled($containerKey)) {
                    $carry[(string) str($componentKey)->after("{$containerKey}.")] = $component;
                } else {
                    $carry[$componentKey] = $component;
                }

                foreach ($component->getChildComponentContainers($withHidden) as $childComponentContainer) {
                    $carry = [
                        ...$carry,
                        ...$childComponentContainer->getFlatComponents($withActions, $withHidden, $withAbsoluteKeys, $containerKey),
                    ];
                }

                return $carry;
            },
            initial: [],
        );
    }

    /**
     * @return array<Component | Action | ActionGroup>
     */
    public function getComponents(bool $withActions = true, bool $withHidden = false, bool $withOriginalKeys = false): array
    {
        $components = array_map(function (Component | Action | ActionGroup $component): Component | Action | ActionGroup {
            if (($component instanceof Action) || ($component instanceof ActionGroup)) {
                return $component->schemaComponentContainer($this);
            }

            return $component->container($this);
        }, $this->evaluate($this->components));

        if ($withActions && $withHidden) {
            return $components;
        }

        return collect($components)
            ->filter(function (Component | Action | ActionGroup $component) use ($withActions, $withHidden) {
                if ((! $withActions) && (($component instanceof Action) || ($component instanceof ActionGroup))) {
                    return false;
                }

                if ((! $withHidden) && $component->isHidden()) {
                    return false;
                }

                return true;
            })
            ->when(
                ! $withOriginalKeys,
                fn (Collection $collection): Collection => $collection->values(),
            )
            ->all();
    }

    protected function cloneComponents(): static
    {
        if (is_array($this->components)) {
            $this->components = array_map(
                fn (Component | Action | ActionGroup $component): Component | Action | ActionGroup => match (true) {
                    $component instanceof Action, $component instanceof ActionGroup => (clone $component)
                        ->schemaComponentContainer($this),
                    $component instanceof Component => $component
                        ->container($this)
                        ->getClone(),
                },
                $this->components,
            );
        }

        return $this;
    }
}
