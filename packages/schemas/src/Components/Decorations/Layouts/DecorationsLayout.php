<?php

namespace Filament\Schemas\Components\Decorations\Layouts;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Concerns\BelongsToContainer;
use Filament\Support\Components\ViewComponent;

abstract class DecorationsLayout extends ViewComponent
{
    use BelongsToContainer;

    abstract public function hasDecorations(): bool;

    /**
     * @return array<string, Action>
     */
    abstract public function getActions(): array;

    /**
     * @param  array<Component | Action | array<Component | Action | ActionGroup>>  $decorations
     * @return array<string, Component | Action | array<Component | Action | ActionGroup>>
     */
    protected function extractActionsFromDecorations(array $decorations): array
    {
        $actions = [];

        foreach ($decorations as $decoration) {
            if (is_array($decoration)) {
                $actions = [
                    ...$actions,
                    ...$this->extractActionsFromDecorations($decoration),
                ];

                continue;
            }

            if (! ($decoration instanceof Action)) {
                continue;
            }

            $actions[$decoration->getName()] = $decoration;
        }

        return $actions;
    }

    /**
     * @param  array<Component | Action | array<Component | Action | ActionGroup>>  $decorations
     * @return array<Component | Action | array<Component | Action | ActionGroup>>
     */
    protected function prepareDecorations(array $decorations): array
    {
        return array_reduce(
            $decorations,
            function (array $carry, Component | Action | ActionGroup | array $decoration): array {
                if ((($decoration instanceof Action) || ($decoration instanceof ActionGroup)) && (! $decoration->isVisible())) {
                    return $carry;
                }

                if (is_array($decoration)) {
                    $carry[] = $this->prepareDecorations($decoration);
                } else {
                    if ($decoration instanceof Component) {
                        $decoration->container($this->getContainer());
                    }

                    $carry[] = $decoration;
                }

                return $carry;
            },
            initial: [],
        );
    }
}
