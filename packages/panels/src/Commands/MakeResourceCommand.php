<?php

namespace Filament\Commands;

use Filament\Clusters\Cluster;
use Filament\Commands\FileGenerators\Resources\Pages\ResourceCreateRecordPageClassGenerator;
use Filament\Commands\FileGenerators\Resources\Pages\ResourceEditRecordPageClassGenerator;
use Filament\Commands\FileGenerators\Resources\Pages\ResourceListRecordsPageClassGenerator;
use Filament\Commands\FileGenerators\Resources\Pages\ResourceManageRecordsPageClassGenerator;
use Filament\Commands\FileGenerators\Resources\Pages\ResourceViewRecordPageClassGenerator;
use Filament\Commands\FileGenerators\Resources\ResourceClassGenerator;
use Filament\Commands\FileGenerators\Resources\Schemas\ResourceFormSchemaClassGenerator;
use Filament\Commands\FileGenerators\Resources\Schemas\ResourceInfolistSchemaClassGenerator;
use Filament\Commands\FileGenerators\Resources\Schemas\ResourceTableClassGenerator;
use Filament\Facades\Filament;
use Filament\Forms\Commands\Concerns\CanGenerateForms;
use Filament\Panel;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Support\Commands\Concerns\CanIndentStrings;
use Filament\Support\Commands\Concerns\CanManipulateFiles;
use Filament\Support\Commands\Concerns\CanReadModelSchemas;
use Filament\Support\Commands\Exceptions\InvalidCommandOutput;
use Filament\Support\Config\FileGenerationFlag;
use Filament\Tables\Commands\Concerns\CanGenerateTables;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use ReflectionClass;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

#[AsCommand(name: 'make:filament-resource', aliases: [
    'filament:make-resource',
    'filament:resource',
])]
class MakeResourceCommand extends Command
{
    use CanGenerateForms;
    use CanGenerateTables;
    use CanIndentStrings;
    use CanManipulateFiles;
    use CanReadModelSchemas;

    protected $description = 'Create a new Filament resource class and default page classes';

    protected $name = 'make:filament-resource';

    /**
     * @var array<string>
     */
    protected $aliases = [
        'filament:make-resource',
        'filament:resource',
    ];

    /**
     * @var class-string<Model>
     */
    protected string $modelFqn;

    protected string $modelFqnEnd;

    protected string $resourcesNamespace;

    protected string $resourcesDirectory;

    /**
     * @var ?class-string<Cluster>
     */
    protected ?string $clusterFqn = null;

    /**
     * @var ?class-string
     */
    protected ?string $parentResourceFqn = null;

    /**
     * @var class-string
     */
    protected string $fqn;

    protected string $fqnEnd;

    /**
     * @var array<string, array{
     *      class: class-string<Page>,
     *      path: string,
     * }>
     */
    protected array $pageRoutes;

    protected string $namespace;

    protected string $directory;

    protected ?string $formSchemaFqn = null;

    protected ?string $infolistSchemaFqn = null;

    protected ?string $tableFqn = null;

    protected bool $hasViewOperation;

    protected bool $isGenerated;

    protected bool $isSimple;

    protected bool $isSoftDeletable;

    protected bool $hasResourceClassesOutsideDirectories;

    protected ?Panel $panel;

    /**
     * @return array<InputArgument>
     */
    protected function getArguments(): array
    {
        return [
            new InputArgument(
                name: 'name',
                mode: InputArgument::OPTIONAL,
                description: 'The name of the model to generate the resource for, optionally prefixed with directories',
            ),
        ];
    }

    /**
     * @return array<InputOption>
     */
    protected function getOptions(): array
    {
        return [
            new InputOption(
                name: 'embed-schemas',
                shortcut: null,
                mode: InputOption::VALUE_NONE,
                description: 'Embed the form and infolist schemas in the resource class instead of creating separate files',
            ),
            new InputOption(
                name: 'embed-table',
                shortcut: null,
                mode: InputOption::VALUE_NONE,
                description: 'Embed the table in the resource class instead of creating a separate file',
            ),
            new InputOption(
                name: 'factory',
                shortcut: null,
                mode: InputOption::VALUE_NONE,
                description: 'Create a factory for the model',
            ),
            new InputOption(
                name: 'generate',
                shortcut: 'G',
                mode: InputOption::VALUE_NONE,
                description: 'Generate the form schema and table columns based on the attributes of the model',
            ),
            new InputOption(
                name: 'migration',
                shortcut: null,
                mode: InputOption::VALUE_NONE,
                description: 'Create a migration for the model',
            ),
            new InputOption(
                name: 'model',
                shortcut: null,
                mode: InputOption::VALUE_NONE,
                description: 'Create the model class if it does not exist',
            ),
            new InputOption(
                name: 'model-namespace',
                shortcut: null,
                mode: InputOption::VALUE_REQUIRED,
                description: 'The namespace of the model class, [App\\Models] by default',
            ),
            new InputOption(
                name: 'nested',
                shortcut: 'N',
                mode: InputOption::VALUE_OPTIONAL,
                description: 'Nest the resource inside another through a relationship',
            ),
            new InputOption(
                name: 'not-embedded',
                shortcut: null,
                mode: InputOption::VALUE_NONE,
                description: 'Even if the resource is simple, create separate files for the form and infolist schemas and table',
            ),
            new InputOption(
                name: 'panel',
                shortcut: null,
                mode: InputOption::VALUE_REQUIRED,
                description: 'The panel to create the resource in',
            ),
            new InputOption(
                name: 'simple',
                shortcut: 'S',
                mode: InputOption::VALUE_NONE,
                description: 'Generate a simple resource class with a single page, modals and embedded schemas and embedded table',
            ),
            new InputOption(
                name: 'soft-deletes',
                shortcut: null,
                mode: InputOption::VALUE_NONE,
                description: 'Indicate if the model uses soft deletes',
            ),
            new InputOption(
                name: 'view',
                shortcut: null,
                mode: InputOption::VALUE_NONE,
                description: 'Generate a view page for the resource',
            ),
            new InputOption(
                name: 'force',
                shortcut: 'F',
                mode: InputOption::VALUE_NONE,
                description: 'Overwrite the contents of the files if they already exist',
            ),
        ];
    }

    public function handle(): int
    {
        try {
            $this->configureModel();
            $this->configurePanel();
            $this->configureCluster();
            $this->configureResourcesLocation();
            $this->isSimple = $this->option('simple');
            $this->configureParentResource();
            $this->hasViewOperation = $this->option('view');
            $this->isGenerated = $this->option('generate');
            $this->isSoftDeletable = $this->option('soft-deletes');
            $this->hasResourceClassesOutsideDirectories = $this->hasFileGenerationFlag(FileGenerationFlag::PANEL_RESOURCE_CLASSES_OUTSIDE_DIRECTORIES);

            $this->configureLocation();
            $this->configurePageRoutes();

            $this->createFormSchema();
            $this->createInfolistSchema();
            $this->createTable();

            $this->createResourceClass();

            $this->createManagePage();
            $this->createListPage();
            $this->createCreatePage();
            $this->createEditPage();
            $this->createViewPage();
        } catch (InvalidCommandOutput) {
            return static::INVALID;
        }

        $this->components->info("Filament resource [{$this->fqn}] created successfully.");

        return static::SUCCESS;
    }

    protected function configureModel(): void
    {
        $this->modelFqnEnd = (string) str($this->argument('name') ?? text(
            label: 'What is the model name?',
            placeholder: 'BlogPost',
            required: true,
        ))
            ->studly()
            ->beforeLast('Resource')
            ->trim('/')
            ->trim('\\')
            ->trim(' ')
            ->studly()
            ->replace('/', '\\');

        if (blank($this->modelFqnEnd)) {
            $this->modelFqnEnd = 'Resource';
        }

        $this->modelFqn = ($this->option('model-namespace') ?? 'App\\Models') . '\\' . $this->modelFqnEnd;

        if ($this->option('model')) {
            $this->callSilently('make:model', [
                'name' => $this->modelFqn,
            ]);
        }

        if ($this->option('migration')) {
            $table = (string) str($this->modelFqn)
                ->classBasename()
                ->pluralStudly()
                ->snake();

            $this->call('make:migration', [
                'name' => "create_{$table}_table",
                '--create' => $table,
            ]);
        }

        if ($this->option('factory')) {
            $this->callSilently('make:factory', [
                'name' => $this->modelFqnEnd,
            ]);
        }
    }

    protected function configurePanel(): void
    {
        $panelName = $this->option('panel');

        $this->panel = filled($panelName) ? Filament::getPanel($panelName, isStrict: false) : null;

        if ($this->panel) {
            return;
        }

        $panels = Filament::getPanels();

        /** @var Panel $panel */
        $panel = (count($panels) > 1) ? $panels[select(
            label: 'Which panel would you like to create this in?',
            options: array_map(
                fn (Panel $panel): string => $panel->getId(),
                $panels,
            ),
            default: Filament::getDefaultPanel()->getId(),
        )] : Arr::first($panels);

        $this->panel = $panel;
    }

    protected function configureCluster(): void
    {
        $clusterFqns = $this->panel->getClusters();

        if (empty($clusterFqns)) {
            return;
        }

        $this->clusterFqn = select(
            label: 'Would you like to create this resource in a cluster?',
            options: $clusterFqns,
            required: false,
        );
    }

    protected function configureResourcesLocation(): void
    {
        if (filled($this->clusterFqn)) {
            $clusterBasenameBeforeCluster = (string) str($this->clusterFqn)
                ->classBasename()
                ->beforeLast('Cluster');

            $clusterNamespacePartBeforeBasename = (string) str($this->clusterFqn)
                ->beforeLast('\\')
                ->afterLast('\\');

            if ($clusterBasenameBeforeCluster === $clusterNamespacePartBeforeBasename) {
                $this->resourcesNamespace = (string) str($this->clusterFqn)
                    ->beforeLast('\\')
                    ->append('\\Resources');
                $this->resourcesDirectory = (string) str((new ReflectionClass($this->clusterFqn))->getFileName())
                    ->beforeLast(DIRECTORY_SEPARATOR)
                    ->append('/Resources');

                return;
            }

            $this->resourcesNamespace = (string) str($this->clusterFqn)->append('\\Resources');
            $this->resourcesDirectory = (string) str((new ReflectionClass($this->clusterFqn))->getFileName())
                ->beforeLast('.')
                ->append('/Resources');

            return;
        }

        $directories = $this->panel->getResourceDirectories();
        $namespaces = $this->panel->getResourceNamespaces();

        foreach ($directories as $index => $directory) {
            if (str($directory)->startsWith(base_path('vendor'))) {
                unset($directories[$index]);
                unset($namespaces[$index]);
            }
        }

        if (count($namespaces) < 2) {
            $this->resourcesNamespace = (Arr::first($namespaces) ?? 'App\\Filament\\Resources');
            $this->resourcesDirectory = (Arr::first($directories) ?? app_path('Filament/Resources/'));

            return;
        }

        $this->resourcesNamespace = select(
            label: 'Which namespace would you like to create this in?',
            options: $namespaces,
        );
        $this->resourcesDirectory = $directories[array_search($this->resourcesNamespace, $namespaces)];
    }

    protected function configureParentResource(): void
    {
        if (! $this->option('nested')) {
            return;
        }

        if ($this->isSimple) {
            $this->components->error('Nested resources cannot be simple, you can use the relation manager or relation page on the parent resource to open modals for each operation.');

            throw new InvalidCommandOutput;
        }

        $parentResource = $this->option('nested');

        if (is_string($parentResource)) {
            $parentResourceNamespace = (string) str($parentResource)
                ->beforeLast('Resource')
                ->pluralStudly()
                ->replace('/', '\\')
                ->prepend("{$this->resourcesNamespace}\\");

            $parentResourceBasename = (string) str($parentResource)
                ->classBasename()
                ->beforeLast('Resource')
                ->singular()
                ->append('Resource');

            if (class_exists("{$parentResourceNamespace}\\{$parentResourceBasename}")) {
                $this->parentResourceFqn = "{$parentResourceNamespace}\\{$parentResourceBasename}";
                $this->resourcesNamespace = (string) str($this->parentResourceFqn)
                    ->beforeLast('\\')
                    ->append('\\Resources');
                $this->resourcesDirectory = (string) str((new ReflectionClass($this->parentResourceFqn))->getFileName())
                    ->beforeLast(DIRECTORY_SEPARATOR)
                    ->append('/Resources');

                return;
            }

            $parentResourceNamespace = (string) str($parentResourceNamespace)
                ->beforeLast('\\');

            if (class_exists("{$parentResourceNamespace}\\{$parentResourceBasename}")) {
                $this->parentResourceFqn = "{$parentResourceNamespace}\\{$parentResourceBasename}";
                $this->resourcesNamespace = (string) str($this->parentResourceFqn)
                    ->append('\\Resources');
                $this->resourcesDirectory = (string) str((new ReflectionClass($this->parentResourceFqn))->getFileName())
                    ->beforeLast('.')
                    ->append('/Resources');

                return;
            }
        }

        $parentResourceFqns = array_filter(
            $this->panel->getResources(),
            fn (string $resource): bool => str($resource)->startsWith("{$this->resourcesNamespace}\\"),
        );

        if ($parentResourceFqns) {
            $this->parentResourceFqn = text(
                label: "No resources were found within [{$this->resourcesNamespace}]. Which resource would you like to nest this resource inside?",
                placeholder: 'App\\Filament\\Resources\\Posts\\PostResource',
                validate: fn (string $value): ?string => match (true) {
                    ! class_exists($value) => 'The resource class does not exist. Please ensure you use the fully qualified class name (the namespace and class name) of the resource.',
                    ! is_subclass_of($value, Resource::class) => 'The resource class or one of its parents must extend [' . Resource::class . '].',
                    default => null,
                },
                hint: 'Please provide the fully qualified class name of the resource.',
            );
        } else {
            $this->parentResourceFqn = select(
                label: 'Which resource would you like to nest this resource inside?',
                options: $parentResourceFqns,
            );
        }

        $pluralParentResourceBasenameBeforeResource = (string) str($this->parentResourceFqn)
            ->classBasename()
            ->beforeLast('Resource')
            ->plural();

        $parentResourceNamespacePartBeforeBasename = (string) str($this->parentResourceFqn)
            ->beforeLast('\\')
            ->afterLast('\\');

        if ($pluralParentResourceBasenameBeforeResource === $parentResourceNamespacePartBeforeBasename) {
            $this->resourcesNamespace = (string) str($this->parentResourceFqn)
                ->beforeLast('\\')
                ->append('\\Resources');
            $this->resourcesDirectory = (string) str((new ReflectionClass($this->parentResourceFqn))->getFileName())
                ->beforeLast(DIRECTORY_SEPARATOR)
                ->append('/Resources');

            return;
        }

        $this->resourcesNamespace = (string) str($this->parentResourceFqn)->append('\\Resources');
        $this->resourcesDirectory = (string) str((new ReflectionClass($this->parentResourceFqn))->getFileName())
            ->beforeLast('.')
            ->append('/Resources');
    }

    protected function configureLocation(): void
    {
        if ($this->hasResourceClassesOutsideDirectories) {
            $this->fqnEnd = "{$this->modelFqnEnd}Resource";
        } else {
            $this->fqnEnd = Str::pluralStudly($this->modelFqnEnd) . '\\' . class_basename($this->modelFqn) . 'Resource';
        }

        $this->fqn = $this->resourcesNamespace . '\\' . $this->fqnEnd;

        if ($this->hasResourceClassesOutsideDirectories) {
            $this->namespace = $this->fqn;
            $this->directory = (string) str("{$this->resourcesDirectory}/{$this->fqnEnd}")
                ->replace('\\', '/')
                ->replace('//', '/');
        } else {
            $this->namespace = (string) str($this->fqn)
                ->beforeLast('\\');
            $this->directory = (string) str($this->resourcesDirectory . '/' . Str::pluralStudly($this->modelFqnEnd))
                ->replace('\\', '/')
                ->replace('//', '/');
        }
    }

    protected function configurePageRoutes(): void
    {
        $modelBasename = class_basename($this->modelFqn);
        $pluralModelBasename = Str::pluralStudly($modelBasename);

        if ($this->isSimple) {
            $this->pageRoutes = [
                'index' => [
                    'class' => "{$this->namespace}\\Pages\\Manage{$pluralModelBasename}",
                    'path' => '/',
                ],
            ];

            return;
        }

        $this->pageRoutes = [
            ...(blank($this->parentResourceFqn) ? [
                'index' => [
                    'class' => "{$this->namespace}\\Pages\\List{$pluralModelBasename}",
                    'path' => '/',
                ],
            ] : []),
            'create' => [
                'class' => "{$this->namespace}\\Pages\\Create{$modelBasename}",
                'path' => '/create',
            ],
            ...($this->hasViewOperation ? [
                'view' => [
                    'class' => "{$this->namespace}\\Pages\\View{$modelBasename}",
                    'path' => '/{record}',
                ],
            ] : []),
            'edit' => [
                'class' => "{$this->namespace}\\Pages\\Edit{$modelBasename}",
                'path' => '/{record}/edit',
            ],
        ];
    }

    protected function createFormSchema(): void
    {
        if ($this->hasEmbeddedSchemas()) {
            return;
        }

        $modelBasename = class_basename($this->modelFqn);

        $path = "{$this->directory}/Schemas/{$modelBasename}Form.php";

        if (! $this->option('force') && $this->checkForCollision($path)) {
            throw new InvalidCommandOutput;
        }

        $this->formSchemaFqn = "{$this->namespace}\\Schemas\\{$modelBasename}Form";

        $this->writeFile($path, app(ResourceFormSchemaClassGenerator::class, [
            'fqn' => $this->formSchemaFqn,
            'modelFqn' => $this->modelFqn,
            'isGenerated' => $this->isGenerated,
        ]));
    }

    protected function createInfolistSchema(): void
    {
        if (! $this->hasViewOperation) {
            return;
        }

        if ($this->hasEmbeddedSchemas()) {
            return;
        }

        $modelBasename = class_basename($this->modelFqn);

        $path = "{$this->directory}/Schemas/{$modelBasename}Infolist.php";

        if (! $this->option('force') && $this->checkForCollision($path)) {
            throw new InvalidCommandOutput;
        }

        $this->infolistSchemaFqn = "{$this->namespace}\\Schemas\\{$modelBasename}Infolist";

        $this->writeFile($path, app(ResourceInfolistSchemaClassGenerator::class, [
            'fqn' => $this->infolistSchemaFqn,
        ]));
    }

    protected function createTable(): void
    {
        if ($this->hasEmbeddedTable()) {
            return;
        }

        $modelBasename = class_basename($this->modelFqn);
        $pluralModelBasename = Str::pluralStudly($modelBasename);

        $path = "{$this->directory}/Tables/{$pluralModelBasename}Table.php";

        if (! $this->option('force') && $this->checkForCollision($path)) {
            throw new InvalidCommandOutput;
        }

        $this->tableFqn = "{$this->namespace}\\Tables\\{$pluralModelBasename}Table";

        $this->writeFile($path, app(ResourceTableClassGenerator::class, [
            'fqn' => $this->tableFqn,
            'modelFqn' => $this->modelFqn,
            'hasViewOperation' => $this->hasViewOperation,
            'isGenerated' => $this->isGenerated,
            'isSoftDeletable' => $this->isSoftDeletable,
            'isSimple' => $this->isSimple,
        ]));
    }

    protected function createResourceClass(): void
    {
        $path = (string) str("{$this->resourcesDirectory}\\{$this->fqnEnd}.php")
            ->replace('\\', '/')
            ->replace('//', '/');

        if (! $this->option('force') && $this->checkForCollision($path)) {
            throw new InvalidCommandOutput;
        }

        $this->writeFile($path, app(ResourceClassGenerator::class, [
            'fqn' => $this->fqn,
            'modelFqn' => $this->modelFqn,
            'clusterFqn' => $this->clusterFqn,
            'parentResourceFqn' => $this->parentResourceFqn,
            'pageRoutes' => $this->pageRoutes,
            'formSchemaFqn' => $this->formSchemaFqn,
            'infolistSchemaFqn' => $this->infolistSchemaFqn,
            'tableFqn' => $this->tableFqn,
            'hasViewOperation' => $this->hasViewOperation,
            'isGenerated' => $this->isGenerated,
            'isSoftDeletable' => $this->isSoftDeletable,
            'isSimple' => $this->isSimple,
        ]));
    }

    protected function createManagePage(): void
    {
        if (! $this->isSimple) {
            return;
        }

        $modelBasename = class_basename($this->modelFqn);
        $pluralModelBasename = Str::pluralStudly($modelBasename);

        $path = "{$this->directory}/Pages/Manage{$pluralModelBasename}.php";

        if (! $this->option('force') && $this->checkForCollision($path)) {
            throw new InvalidCommandOutput;
        }

        $this->writeFile($path, app(ResourceManageRecordsPageClassGenerator::class, [
            'fqn' => "{$this->namespace}\\Pages\\Manage{$pluralModelBasename}",
            'resourceFqn' => $this->fqn,
        ]));
    }

    protected function createListPage(): void
    {
        if ($this->isSimple) {
            return;
        }

        if (filled($this->parentResourceFqn)) {
            return;
        }

        $modelBasename = class_basename($this->modelFqn);
        $pluralModelBasename = Str::pluralStudly($modelBasename);

        $path = "{$this->directory}/Pages/List{$pluralModelBasename}.php";

        if (! $this->option('force') && $this->checkForCollision($path)) {
            throw new InvalidCommandOutput;
        }

        $this->writeFile($path, app(ResourceListRecordsPageClassGenerator::class, [
            'fqn' => "{$this->namespace}\\Pages\\List{$pluralModelBasename}",
            'resourceFqn' => $this->fqn,
        ]));
    }

    protected function createCreatePage(): void
    {
        if ($this->isSimple) {
            return;
        }

        $modelBasename = class_basename($this->modelFqn);

        $path = "{$this->directory}/Pages/Create{$modelBasename}.php";

        if (! $this->option('force') && $this->checkForCollision($path)) {
            throw new InvalidCommandOutput;
        }

        $this->writeFile($path, app(ResourceCreateRecordPageClassGenerator::class, [
            'fqn' => "{$this->namespace}\\Pages\\Create{$modelBasename}",
            'resourceFqn' => $this->fqn,
        ]));
    }

    protected function createEditPage(): void
    {
        if ($this->isSimple) {
            return;
        }

        $modelBasename = class_basename($this->modelFqn);

        $path = "{$this->directory}/Pages/Edit{$modelBasename}.php";

        if (! $this->option('force') && $this->checkForCollision($path)) {
            throw new InvalidCommandOutput;
        }

        $this->writeFile($path, app(ResourceEditRecordPageClassGenerator::class, [
            'fqn' => "{$this->namespace}\\Pages\\Edit{$modelBasename}",
            'resourceFqn' => $this->fqn,
            'hasViewOperation' => $this->hasViewOperation,
            'isSoftDeletable' => $this->isSoftDeletable,
        ]));
    }

    protected function createViewPage(): void
    {
        if ($this->isSimple) {
            return;
        }

        $modelBasename = class_basename($this->modelFqn);

        $path = "{$this->directory}/Pages/View{$modelBasename}.php";

        if (! $this->option('force') && $this->checkForCollision($path)) {
            throw new InvalidCommandOutput;
        }

        $this->writeFile($path, app(ResourceViewRecordPageClassGenerator::class, [
            'fqn' => "{$this->namespace}\\Pages\\View{$modelBasename}",
            'resourceFqn' => $this->fqn,
        ]));
    }

    protected function hasFileGenerationFlag(string $flag): bool
    {
        return in_array($flag, config('filament.file_generation.flags') ?? []);
    }

    protected function hasEmbeddedSchemas(): bool
    {
        if ($this->isSimple && (! $this->option('not-embedded'))) {
            return true;
        }

        if ($this->option('embed-schemas')) {
            return true;
        }

        return $this->hasFileGenerationFlag(FileGenerationFlag::EMBEDDED_PANEL_RESOURCE_SCHEMAS);
    }

    protected function hasEmbeddedTable(): bool
    {
        if ($this->isSimple && (! $this->option('not-embedded'))) {
            return true;
        }

        if ($this->option('embed-table')) {
            return true;
        }

        return $this->hasFileGenerationFlag(FileGenerationFlag::EMBEDDED_PANEL_RESOURCE_TABLES);
    }
}
