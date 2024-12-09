<?php

namespace Filament\Resources\RelationManagers;

use Closure;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Actions\AssociateAction;
use Filament\Actions\AttachAction;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\ReplicateAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Infolists;
use Filament\Pages\Page;
use Filament\Resources\Concerns\InteractsWithRelationshipTable;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\RenderHook;
use Filament\Schemas\Components\TableBuilder;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Concerns\CanBeLazy;
use Filament\Support\Enums\IconPosition;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\View\PanelsRenderHook;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;
use Livewire\Component;

use function Filament\authorize;

class RelationManager extends Component implements Actions\Contracts\HasActions, Forms\Contracts\HasForms, Infolists\Contracts\HasInfolists, Tables\Contracts\HasTable
{
    use Actions\Concerns\InteractsWithActions;
    use CanBeLazy;
    use Forms\Concerns\InteractsWithForms;
    use Infolists\Concerns\InteractsWithInfolists;
    use InteractsWithRelationshipTable {
        InteractsWithRelationshipTable::makeTable as makeBaseRelationshipTable;
    }

    /**
     * @var view-string
     */
    protected static string $view = 'filament-panels::resources.relation-manager';

    #[Locked]
    public Model $ownerRecord;

    #[Locked]
    public ?string $pageClass = null;

    /**
     * @deprecated Override the `table()` method to configure the table.
     */
    protected static ?string $recordTitleAttribute = null;

    /**
     * @deprecated Override the `table()` method to configure the table.
     */
    protected static ?string $inverseRelationship = null;

    /**
     * @deprecated Override the `table()` method to configure the table.
     */
    protected static ?string $label = null;

    /**
     * @deprecated Override the `table()` method to configure the table.
     */
    protected static ?string $pluralLabel = null;

    /**
     * @deprecated Override the `table()` method to configure the table.
     */
    protected static ?string $modelLabel = null;

    /**
     * @deprecated Override the `table()` method to configure the table.
     */
    protected static ?string $pluralModelLabel = null;

    protected static ?string $title = null;

    protected static ?string $icon = null;

    protected static IconPosition $iconPosition = IconPosition::Before;

    protected static ?string $badge = null;

    protected static ?string $badgeColor = null;

    protected static ?string $badgeTooltip = null;

    public function mount(): void
    {
        $this->loadDefaultActiveTab();
    }

    /**
     * @return array<int | string, string | Schema>
     */
    protected function getForms(): array
    {
        return [];
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    public static function make(array $properties = []): RelationManagerConfiguration
    {
        return app(RelationManagerConfiguration::class, ['relationManager' => static::class, 'properties' => $properties]);
    }

    /**
     * @return array<string>
     */
    public function getRenderHookScopes(): array
    {
        return [
            static::class,
            $this->getPageClass(),
        ];
    }

    public function render(): View
    {
        return view(static::$view, $this->getViewData());
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return [];
    }

    public static function getTabComponent(Model $ownerRecord, string $pageClass): Tab
    {
        return Tab::make(static::class::getTitle($ownerRecord, $pageClass))
            ->badge(static::class::getBadge($ownerRecord, $pageClass))
            ->badgeColor(static::class::getBadgeColor($ownerRecord, $pageClass))
            ->badgeTooltip(static::class::getBadgeTooltip($ownerRecord, $pageClass))
            ->icon(static::class::getIcon($ownerRecord, $pageClass))
            ->iconPosition(static::class::getIconPosition($ownerRecord, $pageClass));
    }

    public static function getIcon(Model $ownerRecord, string $pageClass): ?string
    {
        return static::$icon;
    }

    public static function getIconPosition(Model $ownerRecord, string $pageClass): IconPosition
    {
        return static::$iconPosition;
    }

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        return static::$badge;
    }

    public static function getBadgeColor(Model $ownerRecord, string $pageClass): ?string
    {
        return static::$badgeColor;
    }

    public static function getBadgeTooltip(Model $ownerRecord, string $pageClass): ?string
    {
        return static::$badgeTooltip;
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return static::$title ?? (string) str(static::getRelationshipName())
            ->kebab()
            ->replace('-', ' ')
            ->headline();
    }

    /**
     * @return class-string<Page>
     */
    public function getPageClass(): string
    {
        return $this->pageClass;
    }

    public function getOwnerRecord(): Model
    {
        return $this->ownerRecord;
    }

    protected function configureAction(Action $action): void
    {
        match (true) {
            $action instanceof AssociateAction => $this->configureAssociateAction($action),
            $action instanceof AttachAction => $this->configureAttachAction($action),
            $action instanceof CreateAction => $this->configureCreateAction($action),
            $action instanceof DeleteAction => $this->configureDeleteAction($action),
            $action instanceof DetachAction => $this->configureDetachAction($action),
            $action instanceof DissociateAction => $this->configureDissociateAction($action),
            $action instanceof EditAction => $this->configureEditAction($action),
            $action instanceof ForceDeleteAction => $this->configureForceDeleteAction($action),
            $action instanceof ReplicateAction => $this->configureReplicateAction($action),
            $action instanceof RestoreAction => $this->configureRestoreAction($action),
            $action instanceof ViewAction => $this->configureViewAction($action),
            $action instanceof DeleteBulkAction => $this->configureDeleteBulkAction($action),
            $action instanceof DetachBulkAction => $this->configureDetachBulkAction($action),
            $action instanceof DissociateBulkAction => $this->configureDissociateBulkAction($action),
            $action instanceof ForceDeleteBulkAction => $this->configureForceDeleteBulkAction($action),
            $action instanceof RestoreBulkAction => $this->configureRestoreBulkAction($action),
            default => null,
        };
    }

    protected function configureAssociateAction(AssociateAction $action): void
    {
        $action
            ->authorize(static fn (RelationManager $livewire): bool => (! $livewire->isReadOnly()) && $livewire->canAssociate());
    }

    protected function configureAttachAction(AttachAction $action): void
    {
        $action
            ->authorize(static fn (RelationManager $livewire): bool => (! $livewire->isReadOnly()) && $livewire->canAttach());
    }

    protected function configureCreateAction(CreateAction $action): void
    {
        $action
            ->authorize(static fn (RelationManager $livewire): bool => (! $livewire->isReadOnly()) && $livewire->canCreate())
            ->form(function (Schema $form): Schema {
                $this->configureForm($form);

                return $form;
            });

        $relatedResource = static::getRelatedResource();

        if ($relatedResource && $relatedResource::hasPage('create')) {
            $action->url(fn (): string => $relatedResource::getUrl('create', shouldGuessMissingParameters: true));
        }
    }

    protected function configureDeleteAction(DeleteAction $action): void
    {
        $action
            ->authorize(static fn (RelationManager $livewire, Model $record): bool => (! $livewire->isReadOnly()) && $livewire->canDelete($record));
    }

    protected function configureDetachAction(DetachAction $action): void
    {
        $action
            ->authorize(static fn (RelationManager $livewire, Model $record): bool => (! $livewire->isReadOnly()) && $livewire->canDetach($record));
    }

    protected function configureDissociateAction(DissociateAction $action): void
    {
        $action
            ->authorize(static fn (RelationManager $livewire, Model $record): bool => (! $livewire->isReadOnly()) && $livewire->canDissociate($record));
    }

    protected function configureEditAction(EditAction $action): void
    {
        $action
            ->authorize(static fn (RelationManager $livewire, Model $record): bool => (! $livewire->isReadOnly()) && $livewire->canEdit($record))
            ->form(function (Schema $form): Schema {
                $this->configureForm($form);

                return $form;
            });

        $relatedResource = static::getRelatedResource();

        if ($relatedResource && $relatedResource::hasPage('edit')) {
            $action->url(fn (Model $record): string => $relatedResource::getUrl('edit', ['record' => $record], shouldGuessMissingParameters: true));
        }
    }

    protected function configureForceDeleteAction(ForceDeleteAction $action): void
    {
        $action
            ->authorize(static fn (RelationManager $livewire, Model $record): bool => (! $livewire->isReadOnly()) && $livewire->canForceDelete($record));
    }

    protected function configureReplicateAction(ReplicateAction $action): void
    {
        $action
            ->authorize(static fn (RelationManager $livewire, Model $record): bool => (! $livewire->isReadOnly()) && $livewire->canReplicate($record));
    }

    protected function configureRestoreAction(RestoreAction $action): void
    {
        $action
            ->authorize(static fn (RelationManager $livewire, Model $record): bool => (! $livewire->isReadOnly()) && $livewire->canRestore($record));
    }

    protected function configureViewAction(ViewAction $action): void
    {
        $action
            ->authorize(static fn (RelationManager $livewire, Model $record): bool => $livewire->canView($record))
            ->infolist(function (Schema $infolist): Schema {
                $this->configureInfolist($infolist);

                return $infolist;
            })
            ->form(function (Schema $form): Schema {
                $this->configureForm($form);

                return $form;
            });

        $relatedResource = static::getRelatedResource();

        if ($relatedResource && $relatedResource::hasPage('view')) {
            $action->url(fn (Model $record): string => $relatedResource::getUrl('view', ['record' => $record], shouldGuessMissingParameters: true));
        }
    }

    protected function configureDeleteBulkAction(DeleteBulkAction $action): void
    {
        $action
            ->authorize(static fn (RelationManager $livewire): bool => (! $livewire->isReadOnly()) && $livewire->canDeleteAny());
    }

    protected function configureDetachBulkAction(DetachBulkAction $action): void
    {
        $action
            ->authorize(static fn (RelationManager $livewire): bool => (! $livewire->isReadOnly()) && $livewire->canDetachAny());
    }

    protected function configureDissociateBulkAction(DissociateBulkAction $action): void
    {
        $action
            ->authorize(static fn (RelationManager $livewire): bool => (! $livewire->isReadOnly()) && $livewire->canDissociateAny());
    }

    protected function configureForceDeleteBulkAction(ForceDeleteBulkAction $action): void
    {
        $action
            ->authorize(static fn (RelationManager $livewire): bool => (! $livewire->isReadOnly()) && $livewire->canForceDeleteAny());
    }

    protected function configureRestoreBulkAction(RestoreBulkAction $action): void
    {
        $action
            ->authorize(static fn (RelationManager $livewire): bool => (! $livewire->isReadOnly()) && $livewire->canRestoreAny());
    }

    protected function can(string $action, ?Model $record = null): bool
    {
        if (static::shouldSkipAuthorization()) {
            return true;
        }

        if ($relatedResource = static::getRelatedResource()) {
            $method = 'can' . Str::lcfirst($action);

            return method_exists($relatedResource, $method)
                ? $relatedResource::{$method}($record)
                : $relatedResource::can($action, $record);
        }

        $model = $this->getTable()->getModel();

        try {
            return authorize($action, $record ?? $model, static::shouldCheckPolicyExistence())->allowed();
        } catch (AuthorizationException $exception) {
            return $exception->toResponse()->allowed();
        }
    }

    public function form(Schema $form): Schema
    {
        return $form;
    }

    public function infolist(Schema $infolist): Schema
    {
        return $infolist;
    }

    public function isReadOnly(): bool
    {
        if (blank($this->getPageClass())) {
            return false;
        }

        $panel = Filament::getCurrentPanel();

        if (! $panel) {
            return false;
        }

        if (! $panel->hasReadOnlyRelationManagersOnResourceViewPagesByDefault()) {
            return false;
        }

        return is_subclass_of($this->getPageClass(), ViewRecord::class);
    }

    protected function canAssociate(): bool
    {
        return $this->can('associate');
    }

    protected function canAttach(): bool
    {
        return $this->can('attach');
    }

    protected function canCreate(): bool
    {
        return $this->can('create');
    }

    protected function canDelete(Model $record): bool
    {
        return $this->can('delete', $record);
    }

    protected function canDeleteAny(): bool
    {
        return $this->can('deleteAny');
    }

    protected function canDetach(Model $record): bool
    {
        return $this->can('detach', $record);
    }

    protected function canDetachAny(): bool
    {
        return $this->can('detachAny');
    }

    protected function canDissociate(Model $record): bool
    {
        return $this->can('dissociate', $record);
    }

    protected function canDissociateAny(): bool
    {
        return $this->can('dissociateAny');
    }

    protected function canEdit(Model $record): bool
    {
        return $this->can('update', $record);
    }

    protected function canForceDelete(Model $record): bool
    {
        return $this->can('forceDelete', $record);
    }

    protected function canForceDeleteAny(): bool
    {
        return $this->can('forceDeleteAny');
    }

    protected function canReorder(): bool
    {
        return $this->can('reorder');
    }

    protected function canReplicate(Model $record): bool
    {
        return $this->can('replicate', $record);
    }

    protected function canRestore(Model $record): bool
    {
        return $this->can('restore', $record);
    }

    protected function canRestoreAny(): bool
    {
        return $this->can('restoreAny');
    }

    protected function canView(Model $record): bool
    {
        return $this->can('view', $record);
    }

    /**
     * @deprecated Override the `table()` method to configure the table.
     */
    public static function getRecordTitleAttribute(): ?string
    {
        return static::$recordTitleAttribute;
    }

    /**
     * @deprecated Override the `table()` method to configure the table.
     */
    protected static function getRecordLabel(): ?string
    {
        return static::$label;
    }

    /**
     * @deprecated Override the `table()` method to configure the table.
     */
    protected static function getModelLabel(): ?string
    {
        return static::$modelLabel ?? static::getRecordLabel();
    }

    /**
     * @deprecated Override the `table()` method to configure the table.
     */
    protected static function getPluralRecordLabel(): ?string
    {
        return static::$pluralLabel;
    }

    /**
     * @deprecated Override the `table()` method to configure the table.
     */
    protected static function getPluralModelLabel(): ?string
    {
        return static::$pluralModelLabel ?? static::getPluralRecordLabel();
    }

    /**
     * @deprecated Override the `table()` method to configure the table.
     */
    public function getInverseRelationshipName(): ?string
    {
        return static::$inverseRelationship;
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        if (static::shouldSkipAuthorization()) {
            return true;
        }

        if ($relatedResource = static::getRelatedResource()) {
            return $relatedResource::canAccess();
        }

        $model = $ownerRecord->{static::getRelationshipName()}()->getQuery()->getModel()::class;

        try {
            return authorize('viewAny', $model, static::shouldCheckPolicyExistence())->allowed();
        } catch (AuthorizationException $exception) {
            return $exception->toResponse()->allowed();
        }
    }

    protected function makeTable(): Table
    {
        return $this->makeBaseRelationshipTable()
            ->when(static::getInverseRelationshipName(), fn (Table $table, ?string $inverseRelationshipName): Table => $table->inverseRelationship($inverseRelationshipName))
            ->when(static::getModelLabel(), fn (Table $table, string $modelLabel): Table => $table->modelLabel($modelLabel))
            ->when(static::getPluralModelLabel(), fn (Table $table, string $pluralModelLabel): Table => $table->pluralModelLabel($pluralModelLabel))
            ->when(static::getRecordTitleAttribute(), fn (Table $table, string $recordTitleAttribute): Table => $table->recordTitleAttribute($recordTitleAttribute))
            ->heading($this->getTableHeading() ?? static::getTitle($this->getOwnerRecord(), $this->getPageClass()))
            ->when(
                $this->getTableRecordUrlUsing(),
                fn (Table $table, ?Closure $using) => $table->recordUrl($using),
            );
    }

    /**
     * @return array<string, mixed>
     */
    public static function getDefaultProperties(): array
    {
        $properties = [];

        if (static::isLazy()) {
            $properties['lazy'] = true;
        }

        return $properties;
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getTabsContentComponent(),
                RenderHook::make(PanelsRenderHook::RESOURCE_RELATION_MANAGER_BEFORE),
                TableBuilder::make(),
                RenderHook::make(PanelsRenderHook::RESOURCE_RELATION_MANAGER_AFTER),
            ]);
    }
}
