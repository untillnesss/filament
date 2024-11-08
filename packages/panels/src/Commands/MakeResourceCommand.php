<?php

namespace Filament\Commands;

use Filament\Clusters\Cluster;
use Filament\Commands\Concerns\HasPanel;
use Filament\Commands\FileGenerators\Resources\Pages\ResourceCreateRecordPageClassGenerator;
use Filament\Commands\FileGenerators\Resources\Pages\ResourceEditRecordPageClassGenerator;
use Filament\Commands\FileGenerators\Resources\Pages\ResourceListRecordsPageClassGenerator;
use Filament\Commands\FileGenerators\Resources\Pages\ResourceManageRecordsPageClassGenerator;
use Filament\Commands\FileGenerators\Resources\Pages\ResourceViewRecordPageClassGenerator;
use Filament\Commands\FileGenerators\Resources\ResourceClassGenerator;
use Filament\Commands\FileGenerators\Resources\Schemas\ResourceFormSchemaClassGenerator;
use Filament\Commands\FileGenerators\Resources\Schemas\ResourceInfolistSchemaClassGenerator;
use Filament\Commands\FileGenerators\Resources\Schemas\ResourceTableClassGenerator;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Support\Commands\Concerns\CanManipulateFiles;
use Filament\Support\Commands\Exceptions\InvalidCommandOutput;
use Filament\Support\Commands\FileGenerators\Concerns\CanCheckFileGenerationFlags;
use Filament\Support\Commands\FileGenerators\FileGenerationFlag;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;
use ReflectionClass;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\search;
use function Laravel\Prompts\suggest;
use function Laravel\Prompts\text;

#[AsCommand(name: 'make:filament-resource', aliases: [
    'filament:make-resource',
    'filament:resource',
])]
class MakeResourceCommand extends Command
{
    use CanCheckFileGenerationFlags;
    use CanManipulateFiles;
    use HasPanel;

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

    public static bool $shouldCheckModelForSoftDeletes = true;

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
                name: 'cluster',
                shortcut: 'C',
                mode: InputOption::VALUE_OPTIONAL,
                description: 'The cluster to create the resource in',
                default: false,
            ),
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
                default: false,
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
            $this->configurePanel(question: 'Which panel would you like to create this resource in?');
            $this->configureCluster();
            $this->configureResourcesLocation();
            $this->configureIsSimple();
            $this->configureParentResource();
            $this->configureHasViewOperation();
            $this->configureIsGenerated();
            $this->configureIsSoftDeletable();
            $this->configureHasResourceClassesOutsideDirectories();

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

        if (empty($this->panel->getResourceNamespaces())) {
            $this->components->info('Make sure to register the resource with `resources()` or discover it with `discoverResources()` in the panel service provider.');
        }

        return static::SUCCESS;
    }

    protected function configureModel(): void
    {
        $modelNamespace = $this->option('model-namespace') ?? 'App\\Models';

        $modelFqns = collect(get_declared_classes())
            ->filter(fn (string $class): bool => is_subclass_of($class, Model::class) &&
                str($class)->startsWith("{$modelNamespace}\\"))
            ->map(fn (string $class): string => str($class)->after("{$modelNamespace}\\"))
            ->all();

        $this->modelFqnEnd = (string) str($this->argument('name') ?? suggest(
            label: 'What is the model name?',
            options: function (string $search) use ($modelFqns): array {
                $search = str($search)->trim()->replace(['\\', '/'], '');

                if (blank($search)) {
                    return $modelFqns;
                }

                return array_filter(
                    $modelFqns,
                    fn (string $class): bool => str($class)->replace(['\\', '/'], '')->contains($search, ignoreCase: true),
                );
            },
            placeholder: 'BlogPost',
            required: true,
        ))
            ->trim('/')
            ->trim('\\')
            ->trim(' ')
            ->when(
                fn (Stringable $model): bool => str($model)->endsWith('Resource'),
                fn (Stringable $model): Stringable => str($model)->beforeLast('Resource'),
            )
            ->studly()
            ->replace('/', '\\');

        if (blank($this->modelFqnEnd)) {
            $this->modelFqnEnd = 'Resource';
        }

        $this->modelFqn = "{$modelNamespace}\\{$this->modelFqnEnd}";

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

    protected function configureCluster(): void
    {
        $cluster = $this->option('cluster');

        if ($cluster === false) {
            return;
        }

        if (is_string($cluster)) {
            $cluster = (string) str($cluster)
                ->trim('/')
                ->trim('\\')
                ->trim(' ')
                ->replace('/', '\\');

            if (! class_exists($cluster)) {
                $this->components->warn('The cluster class provided does not exist.');
            } elseif (! is_subclass_of($cluster, Cluster::class)) {
                $this->components->warn('The cluster class or one of its parents must extend [' . Cluster::class . '].');
            } else {
                $this->clusterFqn = $cluster;

                return;
            }
        }

        $clusterFqns = array_values($this->panel->getClusters());

        if (empty($clusterFqns)) {
            $this->clusterFqn = (string) str(text(
                label: "No clusters were found within the [{$this->panel->getId()}] panel. Which cluster would you like to create this resource in?",
                placeholder: 'App\\Filament\\Clusters\\Blog',
                required: true,
                validate: function (string $value): ?string {
                    $value = (string) str($value)
                        ->trim('/')
                        ->trim('\\')
                        ->trim(' ')
                        ->replace('/', '\\');

                    if (
                        (! class_exists($value)) &&
                        class_exists("{$value}\\" . class_basename($value) . 'Cluster')
                    ) {
                        $value = "{$value}\\" . class_basename($value) . 'Cluster';
                    }

                    return match (true) {
                        ! class_exists($value) => 'The cluster class does not exist. Please ensure you use the fully qualified class name of the cluster, such as [App\\Filament\\Clusters\\Blog].',
                        ! is_subclass_of($value, Cluster::class) => 'The cluster class or one of its parents must extend [' . Cluster::class . '].',
                        default => null,
                    };
                },
                hint: 'Please provide the fully qualified class name of the cluster.',
            ))
                ->trim('/')
                ->trim('\\')
                ->trim(' ')
                ->replace('/', '\\');

            if (
                (! class_exists($this->clusterFqn)) &&
                class_exists("{$this->clusterFqn}\\" . class_basename($this->clusterFqn) . 'Cluster')
            ) {
                $this->clusterFqn = "{$this->clusterFqn}\\" . class_basename($this->clusterFqn) . 'Cluster';
            }

            return;
        }

        $this->clusterFqn = search(
            label: 'Which cluster would you like to create this resource in?',
            options: function (?string $search) use ($clusterFqns): array {
                if (blank($search)) {
                    return $clusterFqns;
                }

                $search = str($search)->trim()->replace(['\\', '/'], '');

                return collect($clusterFqns)
                    ->filter(fn (string $fqn): bool => str($fqn)->replace(['\\', '/'], '')->contains($search, ignoreCase: true))
                    ->mapWithKeys(function (string $fqn): array {
                        $basenameBeforeCluster = (string) str($fqn)
                            ->classBasename()
                            ->beforeLast('Cluster');

                        $namespacePartBeforeBasename = (string) str($fqn)
                            ->beforeLast('\\')
                            ->classBasename();

                        if ($basenameBeforeCluster === $namespacePartBeforeBasename) {
                            return [$fqn => (string) str($fqn)->beforeLast('\\')];
                        }

                        return [$fqn => $fqn];
                    })
                    ->all();
            },
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
                ->classBasename();

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

        $this->resourcesNamespace = search(
            label: 'Which namespace would you like to create this resource in?',
            options: function (?string $search) use ($namespaces): array {
                if (blank($search)) {
                    return $namespaces;
                }

                $search = str($search)->trim()->replace(['\\', '/'], '');

                return array_filter($namespaces, fn (string $namespace): bool => str($namespace)->replace(['\\', '/'], '')->contains($search, ignoreCase: true));
            },
        );
        $this->resourcesDirectory = $directories[array_search($this->resourcesNamespace, $namespaces)];
    }

    protected function configureIsSimple(): void
    {
        $this->isSimple = $this->option('simple');
    }

    protected function configureParentResource(): void
    {
        $parentResource = $this->option('nested');

        if ($parentResource === false) {
            return;
        }

        if ($this->isSimple) {
            $this->components->error('Nested resources cannot be simple, you can use the relation manager or relation page on the parent resource to open modals for each operation.');

            throw new InvalidCommandOutput;
        }

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
            array_values($this->panel->getResources()),
            fn (string $resource): bool => str($resource)->startsWith("{$this->resourcesNamespace}\\"),
        );

        if (! $parentResourceFqns) {
            $this->parentResourceFqn = (string) str(text(
                label: "No resources were found within [{$this->resourcesNamespace}]. Which resource would you like to nest this resource inside?",
                placeholder: 'App\\Filament\\Resources\\Posts\\PostResource',
                required: true,
                validate: function (string $value): ?string {
                    $value = (string) str($value)
                        ->trim('/')
                        ->trim('\\')
                        ->trim(' ')
                        ->replace('/', '\\');

                    return match (true) {
                        ! class_exists($value) => 'The resource class does not exist. Please ensure you use the fully qualified class name of the resource, such as [App\\Filament\\Resources\\Posts\\PostResource].',
                        ! is_subclass_of($value, Resource::class) => 'The resource class or one of its parents must extend [' . Resource::class . '].',
                        default => null,
                    };
                },
                hint: 'Please provide the fully qualified class name of the resource.',
            ))
                ->trim('/')
                ->trim('\\')
                ->trim(' ')
                ->replace('/', '\\');
        } else {
            $this->parentResourceFqn = search(
                label: 'Which resource would you like to nest this resource inside?',
                options: function (?string $search) use ($parentResourceFqns): array {
                    $search = str($search)->trim()->replace(['\\', '/'], '');

                    return collect($parentResourceFqns)
                        ->when(
                            filled($search = (string) str($search)->trim()->replace(['\\', '/'], '')),
                            fn (Collection $parentResourceFqns) => $parentResourceFqns->filter(fn (string $fqn): bool => str($fqn)->replace(['\\', '/'], '')->contains($search, ignoreCase: true)),
                        )
                        ->mapWithKeys(fn (string $fqn): array => [$fqn => (string) str($fqn)->after("{$this->resourcesNamespace}\\")])
                        ->all();
                },
            );
        }

        $pluralParentResourceBasenameBeforeResource = (string) str($this->parentResourceFqn)
            ->classBasename()
            ->beforeLast('Resource')
            ->plural();

        $parentResourceNamespacePartBeforeBasename = (string) str($this->parentResourceFqn)
            ->beforeLast('\\')
            ->classBasename();

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

    protected function configureHasViewOperation(): void
    {
        $this->hasViewOperation = $this->option('view') || confirm(
            label: $this->isSimple
                ? 'Would you like to generate an infolist and view modal for the resource?'
                : 'Would you like to generate an infolist and view page for the resource?',
            default: false,
        );
    }

    protected function configureIsGenerated(): void
    {
        $this->isGenerated = $this->option('generate') || confirm(
            label: 'Would you like to generate the form schema and table columns based on the attributes of the model?',
            default: false,
        );
    }

    protected function configureIsSoftDeletable(): void
    {
        $this->isSoftDeletable = $this->option('soft-deletes') || ((static::$shouldCheckModelForSoftDeletes && class_exists($this->modelFqn))
            ? in_array(SoftDeletes::class, class_uses_recursive($this->modelFqn))
            : confirm(
                label: 'Does the model use soft deletes?',
                default: false,
            ));
    }

    protected function configureHasResourceClassesOutsideDirectories(): void
    {
        $this->hasResourceClassesOutsideDirectories = $this->hasFileGenerationFlag(FileGenerationFlag::PANEL_RESOURCE_CLASSES_OUTSIDE_DIRECTORIES);
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
