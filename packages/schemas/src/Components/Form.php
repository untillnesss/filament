<?php

namespace Filament\Schemas\Components;

use Closure;
use Filament\Actions\Action;
use Filament\Schemas\Components\Concerns\EntanglesStateWithSingularRelationship;
use Filament\Schemas\Components\Concerns\HasHeaderActions;
use Filament\Schemas\Components\Contracts\CanEntangleWithSingularRelationships;
use Filament\Schemas\Components\Contracts\ExposesStateToActionData;
use Filament\Schemas\Components\Decorations\Layouts\AlignDecorations;
use Filament\Schemas\Components\Decorations\Layouts\DecorationsLayout;
use Filament\Schemas\Schema;

class Form extends Component implements CanEntangleWithSingularRelationships, Contracts\HasHeaderActions, ExposesStateToActionData
{
    use EntanglesStateWithSingularRelationship;
    use HasHeaderActions;

    /**
     * @var view-string
     */
    protected string $view = 'filament-schema::components.form';

    protected string | Closure | null $livewireSubmitHandler = null;

    const HEADER_DECORATIONS = 'header';

    /**
     * @var array<Component> | Closure
     */
    protected array | Closure $footerChildComponents = [];

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

    protected function setUp(): void
    {
        parent::setUp();

        $this->header(fn (Form $component): array => $component->getHeaderActions());
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
     * @param  array<Component | Action> | DecorationsLayout | Component | Action | string | Closure | null  $decorations
     */
    public function header(array | DecorationsLayout | Component | Action | string | Closure | null $decorations): static
    {
        $this->decorations(
            static::HEADER_DECORATIONS,
            $decorations,
            makeDefaultLayoutUsing: fn (array $decorations): AlignDecorations => AlignDecorations::end($decorations),
        );

        return $this;
    }

    /**
     * @param  array<Component> | Closure  $components
     */
    public function footer(array | Closure $components): static
    {
        $this->footerChildComponents = $components;

        return $this;
    }

    /**
     * @return array<Component>
     */
    public function getFooterChildComponents(): array
    {
        return $this->evaluate($this->footerChildComponents);
    }

    public function getFooterChildComponentContainer(): Schema
    {
        return Schema::make($this->getLivewire())
            ->parentComponent($this)
            ->components($this->getFooterChildComponents());
    }

    /**
     * @return array<Schema>
     */
    public function getChildComponentContainers(bool $withHidden = false): array
    {
        return [
            ...$this->hasChildComponentContainer($withHidden) ? [$this->getChildComponentContainer()] : [],
            ...$this->hasFooterChildComponentContainer($withHidden) ? [$this->getFooterChildComponentContainer()] : [],
        ];
    }

    public function hasFooterChildComponentContainer(bool $withHidden = false): bool
    {
        if ((! $withHidden) && $this->isHidden()) {
            return false;
        }

        if ($this->getFooterChildComponents() === []) {
            return false;
        }

        return true;
    }

    public function prepareDecorationAction(Action $action): Action
    {
        return $this->prepareAction($action);
    }
}
