<?php

namespace Filament\Commands\FileGenerators\Concerns;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Illuminate\Support\Str;
use Nette\PhpGenerator\Literal;

trait CanGenerateResourceForms
{
    public function getFormMethodBody(): string
    {
        return <<<PHP
            return \$schema
                ->components([
                    {$this->outputFormComponents()}
                ]);
            PHP;
    }

    /**
     * @return array<string>
     */
    public function getFormComponents(): array
    {
        if (! $this->isGenerated()) {
            return [];
        }

        $model = $this->getModelFqn();

        if (! class_exists($model)) {
            return [];
        }

        $schema = $this->getModelSchema($model);
        $table = $this->getModelTable($model);

        $components = [];

        foreach ($schema->getColumns($table) as $column) {
            if ($column['auto_increment']) {
                continue;
            }

            $componentName = $column['name'];

            if (str($componentName)->is([
                app($model)->getKeyName(),
                'created_at',
                'deleted_at',
                'updated_at',
                '*_token',
            ])) {
                continue;
            }

            $type = $this->parseColumnType($column);

            $componentData = [];

            $componentData['type'] = match (true) {
                $type['name'] === 'boolean' => Toggle::class,
                $type['name'] === 'date' => DatePicker::class,
                in_array($type['name'], ['datetime', 'timestamp']) => DateTimePicker::class,
                $type['name'] === 'text' => Textarea::class,
                $componentName === 'image', str($componentName)->startsWith('image_'), str($componentName)->contains('_image_'), str($componentName)->endsWith('_image') => FileUpload::class,
                default => TextInput::class,
            };

            if (str($componentName)->endsWith('_id')) {
                $guessedRelationshipName = $this->guessBelongsToRelationshipName($componentName, $model);

                if (filled($guessedRelationshipName)) {
                    $guessedRelationshipTitleColumnName = $this->guessBelongsToRelationshipTitleColumnName($componentName, app($model)->{$guessedRelationshipName}()->getModel()::class);

                    $componentData['type'] = Select::class;
                    $componentData['relationship'] = [$guessedRelationshipName, $guessedRelationshipTitleColumnName];
                }
            }

            if (in_array($componentName, [
                'id',
                'sku',
                'uuid',
            ])) {
                $componentData['label'] = [Str::upper($componentName)];
            }

            if ($componentData['type'] === TextInput::class) {
                if (str($componentName)->contains(['email'])) {
                    $componentData['email'] = [];
                }

                if (str($componentName)->contains(['password'])) {
                    $componentData['password'] = [];
                }

                if (str($componentName)->contains(['phone', 'tel'])) {
                    $componentData['tel'] = [];
                }
            }

            if ($componentData['type'] === FileUpload::class) {
                $componentData['image'] = [];
            }

            if (! $column['nullable']) {
                $componentData['required'] = [];
            }

            if (in_array($type['name'], [
                'integer',
                'decimal',
                'float',
                'double',
                'money',
            ])) {
                if ($componentData['type'] === TextInput::class) {
                    $componentData['numeric'] = [];
                }

                if (filled($column['default'])) {
                    $componentData['default'] = [$this->parseDefaultExpression($column, $model)];

                    if (is_numeric($componentData['default'][0])) {
                        $componentData['default'] = [$componentData['default'][0] + 0];
                    }
                }

                if (in_array($componentName, [
                    'cost',
                    'money',
                    'price',
                ]) || $type['name'] === 'money') {
                    $componentData['prefix'] = ['$'];
                }
            } elseif (in_array($componentData['type'], [
                TextInput::class,
                Textarea::class,
            ]) && isset($type['length'])) {
                $componentData['maxLength'] = [$type['length']];

                if (filled($column['default'])) {
                    $componentData['default'] = [$this->parseDefaultExpression($column, $model)];
                }
            }

            if ($componentData['type'] === Textarea::class) {
                $componentData['columnSpanFull'] = [];
            }

            $components[$componentName] = $componentData;
        }

        return array_map(
            function (array $componentData, string $componentName): string {
                $component = (string) new Literal("{$this->simplifyFqn($componentData['type'])}::make(?)", [$componentName]);

                unset($componentData['type']);

                foreach ($componentData as $methodName => $parameters) {
                    $component .= new Literal(PHP_EOL . "            ->{$methodName}(...?:)", [$parameters]);
                }

                return "{$component},";
            },
            $components,
            array_keys($components),
        );
    }

    public function outputFormComponents(): string
    {
        $components = $this->getFormComponents();

        if (empty($components)) {
            return '//';
        }

        return implode(PHP_EOL . '        ', $components);
    }
}
