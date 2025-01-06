<?php

namespace Filament\Schemas\Components;

use Closure;
use Filament\Actions\Action;
use Filament\Schemas\Components\Concerns\HasLabel;
use Filament\Schemas\Components\Decorations\Layouts\AlignDecorations;
use Filament\Schemas\Components\Decorations\Layouts\DecorationsLayout;
use Filament\Support\Concerns\HasAlignment;
use Filament\Support\Concerns\HasVerticalAlignment;

class Actions extends Component
{
    use HasAlignment;
    use HasLabel;
    use HasVerticalAlignment;

    protected string $view = 'filament-schema::components.actions';

    protected bool | Closure $isFullWidth = false;

    const BEFORE_LABEL_DECORATIONS = 'before_label';

    const AFTER_LABEL_DECORATIONS = 'after_label';

    /**
     * @param  array<Action>  $actions
     */
    final public function __construct(array $actions)
    {
        $this->actions($actions);
    }

    /**
     * @param  array<Action>  $actions
     */
    public static function make(array $actions): static
    {
        $static = app(static::class, ['actions' => $actions]);
        $static->configure();

        return $static;
    }

    /**
     * @param  array<Action>  $actions
     */
    public function actions(array $actions): static
    {
        $this->components(array_map(
            fn (Action $action): Component => $action->toFormComponent(),
            $actions,
        ));

        return $this;
    }

    public function fullWidth(bool | Closure $isFullWidth = true): static
    {
        $this->isFullWidth = $isFullWidth;

        return $this;
    }

    public function isFullWidth(): bool
    {
        return (bool) $this->evaluate($this->isFullWidth);
    }

    /**
     * @param  array<Component | Action> | DecorationsLayout | Component | Action | string | Closure | null  $decorations
     */
    public function beforeLabel(array | DecorationsLayout | Component | Action | string | Closure | null $decorations): static
    {
        $this->decorations(static::BEFORE_LABEL_DECORATIONS, $decorations);

        return $this;
    }

    /**
     * @param  array<Component | Action> | DecorationsLayout | Component | Action | string | Closure | null  $decorations
     */
    public function afterLabel(array | DecorationsLayout | Component | Action | string | Closure | null $decorations): static
    {
        $this->decorations(
            static::AFTER_LABEL_DECORATIONS,
            $decorations,
            makeDefaultLayoutUsing: fn (array $decorations): AlignDecorations => AlignDecorations::end($decorations),
        );

        return $this;
    }
}
