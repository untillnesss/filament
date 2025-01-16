<?php

namespace Filament\Schemas\Components;

use Closure;
use Filament\Actions\Action;
use Filament\Schemas\Components\Concerns\EntanglesStateWithSingularRelationship;
use Filament\Schemas\Components\Contracts\CanEntangleWithSingularRelationships;
use Filament\Schemas\Components\Contracts\ExposesStateToActionData;
use Filament\Schemas\Schema;

class Form extends Component implements CanEntangleWithSingularRelationships, ExposesStateToActionData
{
    use EntanglesStateWithSingularRelationship;

    /**
     * @var view-string
     */
    protected string $view = 'filament-schema::components.form';

    protected string | Closure | null $livewireSubmitHandler = null;

    const HEADER_SLOT = 'header';

    const FOOTER_SLOT = 'footer';

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

    public function action(Action | Closure | null $action): static
    {
        if ($action instanceof Closure) {
            $action = Action::make('submit')->action($action);
        }

        parent::action($action);

        return $this;
    }

    public function livewireSubmitHandler(string | Closure | null $handler): static
    {
        $this->livewireSubmitHandler = $handler;

        return $this;
    }

    public function getLivewireSubmitHandler(): ?string
    {
        return $this->evaluate($this->livewireSubmitHandler) ?? $this->action?->getLivewireClickHandler();
    }

    /**
     * @param  array<Component | Action> | Schema | Component | Action | string | Closure | null  $components
     */
    public function header(array | Schema | Component | Action | string | Closure | null $components): static
    {
        $this->childComponents($components, static::HEADER_SLOT);

        return $this;
    }

    /**
     * @param  array<Component | Action> | Schema | Component | Action | string | Closure | null  $components
     */
    public function footer(array | Schema | Component | Action | string | Closure | null $components): static
    {
        $this->childComponents($components, static::FOOTER_SLOT);

        return $this;
    }

    public function prepareDecorationAction(Action $action): Action
    {
        return $this->prepareAction($action);
    }
}
