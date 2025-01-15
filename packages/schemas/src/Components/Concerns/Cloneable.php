<?php

namespace Filament\Schemas\Components\Concerns;

use Filament\Schemas\Components\Component;

trait Cloneable
{
    protected function cloneChildComponents(): static
    {
        foreach ($this->childComponents as $slot => $childComponents) {
            if (is_array($childComponents)) {
                $this->childComponents[$slot] = array_map(
                    fn (Component $component): Component => $component->getClone(),
                    $childComponents,
                );
            }
        }

        return $this;
    }

    public function getClone(): static
    {
        $clone = clone $this;
        $clone->flushCachedAbsoluteKey();
        $clone->flushCachedAbsoluteStatePath();
        $clone->flushCachedInheritanceKey();
        $clone->cloneChildComponents();

        return $clone;
    }
}
