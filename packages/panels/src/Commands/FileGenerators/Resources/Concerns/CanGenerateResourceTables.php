<?php

namespace Filament\Commands\FileGenerators\Resources\Concerns;

use Filament\Actions\Action;
use Filament\Actions\AssociateAction;
use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
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
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\BaseFilter;
use Filament\Tables\Filters\TrashedFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;
use Nette\PhpGenerator\Literal;

trait CanGenerateResourceTables
{
    /**
     * @param  ?class-string<Model>  $model
     */
    public function generateTableMethodBody(?string $model = null): string
    {
        $this->importUnlessPartial(BulkActionGroup::class);

        $recordTitleAttributeOutput = '';

        if (filled($recordTitleAttribute = $this->getRecordTitleAttribute())) {
            $recordTitleAttributeOutput = new Literal(<<<'PHP'

                ->recordTitleAttribute(?)
            PHP, [$recordTitleAttribute]);
        }

        if (filled($headerActionsOutput = $this->outputTableHeaderActions())) {
            $headerActionsOutput = <<<PHP

                ->headerActions([
                    {$headerActionsOutput}
                ])
            PHP;
        }

        $modifyQueryOutput = '';

        if ($this->isSoftDeletable() && $this->hasTableModifyQueryForSoftDeletes()) {
            $this->namespace->addUse(Builder::class);
            $this->namespace->addUse(SoftDeletingScope::class);

            $modifyQueryOutput = <<<PHP

                ->modifyQueryUsing(fn ({$this->simplifyFqn(Builder::class)} \$query) => \$query
                    ->withoutGlobalScopes([
                        {$this->simplifyFqn(SoftDeletingScope::class)}::class,
                    ]))
            PHP;
        }

        return <<<PHP
            return \$table{$recordTitleAttributeOutput}
                ->columns([
                    {$this->outputTableColumns($model)}
                ])
                ->filters([
                    {$this->outputTableFilters()}
                ]){$headerActionsOutput}
                ->actions([
                    {$this->outputTableActions()}
                ])
                ->bulkActions([
                    {$this->simplifyFqn(BulkActionGroup::class)}::make([
                        {$this->outputTableMethodBulkActions()}
                    ]),
                ]){$modifyQueryOutput};
            PHP;
    }

    /**
     * @param  ?class-string<Model>  $model
     * @return array<string>
     */
    public function getTableColumns(?string $model = null): array
    {
        if (! $this->isGenerated()) {
            return [];
        }

        if (blank($model)) {
            return [];
        }

        if (! class_exists($model)) {
            return [];
        }

        $schema = $this->getModelSchema($model);
        $table = $this->getModelTable($model);

        $columns = [];

        foreach ($schema->getColumns($table) as $column) {
            if ($column['auto_increment']) {
                continue;
            }

            $type = $this->parseColumnType($column);

            if (in_array($type['name'], [
                'json',
                'text',
            ])) {
                continue;
            }

            $columnName = $column['name'];

            if (str($columnName)->endsWith([
                '_token',
            ])) {
                continue;
            }

            if (str($columnName)->contains([
                'password',
            ])) {
                continue;
            }

            if (str($columnName)->endsWith('_id')) {
                $guessedRelationshipName = $this->guessBelongsToRelationshipName($columnName, $model);

                if (filled($guessedRelationshipName)) {
                    $guessedRelationshipTitleColumnName = $this->guessBelongsToRelationshipTitleColumnName($columnName, app($model)->{$guessedRelationshipName}()->getModel()::class);

                    $columnName = "{$guessedRelationshipName}.{$guessedRelationshipTitleColumnName}";
                }
            }

            $columnData = [];

            if (in_array($columnName, [
                'id',
                'sku',
                'uuid',
            ])) {
                $columnData['label'] = [Str::upper($columnName)];
            }

            if ($type['name'] === 'boolean') {
                $columnData['type'] = IconColumn::class;
                $columnData['boolean'] = [];
            } else {
                $columnData['type'] = match (true) {
                    $columnName === 'image', str($columnName)->startsWith('image_'), str($columnName)->contains('_image_'), str($columnName)->endsWith('_image') => ImageColumn::class,
                    default => TextColumn::class,
                };

                if (in_array($type['name'], [
                    'string',
                    'char',
                ]) && ($columnData['type'] === TextColumn::class)) {
                    $columnData['searchable'] = [];
                }

                if (in_array($type['name'], [
                    'date',
                ])) {
                    $columnData['date'] = [];
                    $columnData['sortable'] = [];
                }

                if (in_array($type['name'], [
                    'datetime',
                    'timestamp',
                ])) {
                    $columnData['dateTime'] = [];
                    $columnData['sortable'] = [];
                }

                if (in_array($type['name'], [
                    'integer',
                    'decimal',
                    'float',
                    'double',
                    'money',
                ])) {
                    $columnData[in_array($columnName, [
                        'cost',
                        'money',
                        'price',
                    ]) || $type['name'] === 'money' ? 'money' : 'numeric'] = [];
                    $columnData['sortable'] = [];
                }
            }

            if (in_array($columnName, [
                'created_at',
                'updated_at',
                'deleted_at',
            ])) {
                $columnData['toggleable'] = ['isToggledHiddenByDefault' => true];
            }

            $this->importUnlessPartial($columnData['type']);

            $columns[$columnName] = $columnData;
        }

        return array_map(
            function (array $columnData, string $columnName): string {
                $column = (string) new Literal("{$this->simplifyFqn($columnData['type'])}::make(?)", [$columnName]);

                unset($columnData['type']);

                foreach ($columnData as $methodName => $parameters) {
                    $column .= new Literal(PHP_EOL . "            ->{$methodName}(...?:)", [$parameters]);
                }

                return "{$column},";
            },
            $columns,
            array_keys($columns),
        );
    }

    /**
     * @param  ?class-string<Model>  $model
     */
    public function outputTableColumns(?string $model = null): string
    {
        $columns = $this->getTableColumns($model);

        if (empty($columns)) {
            $recordTitleAttribute = $this->getRecordTitleAttribute();

            if (blank($recordTitleAttribute)) {
                return '//';
            }

            $this->importUnlessPartial(TextColumn::class);

            return new Literal(<<<PHP
                {$this->simplifyFqn(TextColumn::class)}::make(?)
                            ->searchable(),
                PHP, [$recordTitleAttribute]);
        }

        return implode(PHP_EOL . '        ', $columns);
    }

    /**
     * @return array<class-string<BaseFilter>>
     */
    public function getTableFilters(): array
    {
        $filters = [];

        if ($this->isSoftDeletable()) {
            $filters[] = TrashedFilter::class;
        }

        foreach ($filters as $filter) {
            $this->importUnlessPartial($filter);
        }

        return $filters;
    }

    public function outputTableFilters(): string
    {
        $filters = $this->getTableFilters();

        if (empty($filters)) {
            return '//';
        }

        return implode(PHP_EOL . '        ', array_map(
            fn (string $filter) => "{$this->simplifyFqn($filter)}::make(),",
            $filters,
        ));
    }

    /**
     * @return array<class-string<Action>>
     */
    public function getTableHeaderActions(): array
    {
        $actions = [];

        if ($this->hasCreateTableAction()) {
            $actions[] = CreateAction::class;
        }

        if ($this->hasAttachTableActions()) {
            $actions[] = AttachAction::class;
        }

        if ($this->hasAssociateTableActions()) {
            $actions[] = AssociateAction::class;
        }

        foreach ($actions as $action) {
            $this->importUnlessPartial($action);
        }

        return $actions;
    }

    public function outputTableHeaderActions(): ?string
    {
        $actions = $this->getTableHeaderActions();

        if (empty($actions)) {
            return null;
        }

        return implode(PHP_EOL . '        ', array_map(
            fn (string $action) => "{$this->simplifyFqn($action)}::make(),",
            $actions,
        ));
    }

    /**
     * @return array<class-string<Action>>
     */
    public function getTableActions(): array
    {
        $actions = [];

        if ($this->hasViewOperation()) {
            $actions[] = ViewAction::class;
        }

        $actions[] = EditAction::class;

        if ($this->hasAssociateTableActions()) {
            $actions[] = DissociateAction::class;
        }

        if ($this->hasAttachTableActions()) {
            $actions[] = DetachAction::class;
        }

        if ($this->hasDeleteTableActions()) {
            $actions[] = DeleteAction::class;

            if ($this->isSoftDeletable()) {
                $actions[] = ForceDeleteAction::class;
                $actions[] = RestoreAction::class;
            }
        }

        foreach ($actions as $action) {
            $this->importUnlessPartial($action);
        }

        return $actions;
    }

    public function hasCreateTableAction(): bool
    {
        return false;
    }

    public function hasAssociateTableActions(): bool
    {
        return false;
    }

    public function hasAttachTableActions(): bool
    {
        return false;
    }

    public function hasDeleteTableActions(): bool
    {
        return $this->isSimple();
    }

    public function outputTableActions(): string
    {
        return implode(PHP_EOL . '        ', array_map(
            fn (string $action) => "{$this->simplifyFqn($action)}::make(),",
            $this->getTableActions(),
        ));
    }

    /**
     * @return array<class-string<Action>>
     */
    public function getTableBulkActions(): array
    {
        $actions = [];

        if ($this->hasAssociateTableActions()) {
            $actions[] = DissociateBulkAction::class;
        }

        if ($this->hasAttachTableActions()) {
            $actions[] = DetachBulkAction::class;
        }

        $actions[] = DeleteBulkAction::class;

        if ($this->isSoftDeletable()) {
            $actions[] = ForceDeleteBulkAction::class;
            $actions[] = RestoreBulkAction::class;
        }

        foreach ($actions as $action) {
            $this->importUnlessPartial($action);
        }

        return $actions;
    }

    public function outputTableMethodBulkActions(): string
    {
        return implode(PHP_EOL . '            ', array_map(
            fn (string $action) => "{$this->simplifyFqn($action)}::make(),",
            $this->getTableBulkActions(),
        ));
    }

    public function hasTableModifyQueryForSoftDeletes(): bool
    {
        return false;
    }
}
