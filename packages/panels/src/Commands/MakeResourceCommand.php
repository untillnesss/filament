<?php

namespace Filament\Commands;

use Filament\Clusters\Cluster;
use Filament\Commands\FileGenerators\Resources\Pages\ResourceListRecordsPageClassGenerator;
use Filament\Commands\FileGenerators\Resources\Pages\ResourceManageRecordsPageClassGenerator;
use Filament\Commands\FileGenerators\Resources\ResourceClassGenerator;
use Filament\Facades\Filament;
use Filament\Forms\Commands\Concerns\CanGenerateForms;
use Filament\Panel;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Support\Commands\Concerns\CanIndentStrings;
use Filament\Support\Commands\Concerns\CanManipulateFiles;
use Filament\Support\Commands\Concerns\CanReadModelSchemas;
use Filament\Support\Commands\Exceptions\InvalidCommandOutput;
use Filament\Tables\Commands\Concerns\CanGenerateTables;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

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

    protected $signature = 'make:filament-resource {name?} {--model-namespace=} {--soft-deletes} {--view} {--G|generate} {--S|simple} {--panel=} {--model} {--migration} {--factory} {--F|force}';

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
    protected ?string $clusterFqn;

    /**
     * @var class-string<resource>
     */
    protected string $fqn;

    protected string $fqnEnd;

    /**
     * @var array<string, array{
     *      class: class-string<Page>,
     *      path: string,
     * }>
     */
    protected array $pages;

    protected bool $hasViewOperation;

    protected bool $isGenerated;

    protected bool $isSimple;

    protected bool $isSoftDeletable;

    protected Panel $panel;

    public function handle(): int
    {
        $this->configureModel();
        $this->configurePanel();
        $this->configureResourcesLocation();
        $this->hasViewOperation = $this->option('view');
        $this->isGenerated = $this->option('generate');
        $this->isSoftDeletable = $this->option('soft-deletes');
        $this->isSimple = $this->option('simple');

        $this->configureFqn();
        $this->configurePages();

        try {
            $this->createResourceClass();
            $this->createManagePage();
            $this->createListPage();
            // $this->createCreatePage();
            // $this->createEditPage();
            // $this->createViewPage();
        } catch (InvalidCommandOutput) {
            return static::INVALID;
        }

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

    protected function configureResourcesLocation(): void
    {
        $directories = $this->panel->getResourceDirectories();
        $namespaces = $this->panel->getResourceNamespaces();

        foreach ($directories as $index => $directory) {
            if (str($directory)->startsWith(base_path('vendor'))) {
                unset($directories[$index]);
                unset($namespaces[$index]);
            }
        }

        /** @var array<string> $namespaces */
        $this->resourcesNamespace = (count($namespaces) > 1) ?
            select(
                label: 'Which namespace would you like to create this in?',
                options: $namespaces,
            ) :
            (Arr::first($namespaces) ?? 'App\\Filament\\Resources');

        /** @var array<string> $directories */
        $this->resourcesDirectory = (count($directories) > 1) ?
            $directories[array_search($this->resourcesNamespace, $namespaces)] :
            (Arr::first($directories) ?? app_path('Filament/Resources/'));

        $clusterNamespace = (string) str($this->resourcesNamespace)->beforeLast('\Resources');
        $this->clusterFqn = null;

        if (
            class_exists($cluster = ($clusterNamespace . '\\' . class_basename($clusterNamespace))) &&
            is_subclass_of($cluster, Cluster::class)
        ) {
            $this->clusterFqn = $cluster;
        } elseif (
            class_exists($clusterNamespace) &&
            is_subclass_of($clusterNamespace, Cluster::class)
        ) {
            $this->clusterFqn = $clusterNamespace;
        }
    }

    protected function configureFqn(): void
    {
        $this->fqnEnd = "{$this->modelFqnEnd}Resource";
        $this->fqn = $this->resourcesNamespace . '\\' . $this->fqnEnd;
    }

    protected function configurePages(): void
    {
        $modelBasename = class_basename($this->modelFqn);
        $pluralModelBasename = Str::pluralStudly($modelBasename);

        if ($this->isSimple) {
            $this->pages = [
                'index' => [
                    'class' => "{$this->fqn}\\Pages\\Manage{$pluralModelBasename}",
                    'path' => '/',
                ],
            ];

            return;
        }

        $this->pages = [
            'index' => [
                'class' => "{$this->fqn}\\Pages\\List{$pluralModelBasename}",
                'path' => '/',
            ],
            'create' => [
                'class' => "{$this->fqn}\\Pages\\Create{$modelBasename}",
                'path' => '/create',
            ],
            ...($this->hasViewOperation ? [
                'view' => [
                    'class' => "{$this->fqn}\\Pages\\View{$modelBasename}",
                    'path' => '/{record}',
                ],
            ] : []),
            'edit' => [
                'class' => "{$this->fqn}\\Pages\\Edit{$modelBasename}",
                'path' => '/{record}/edit',
            ],
        ];
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
            'pages' => $this->pages,
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

        $path = (string) str("{$this->resourcesDirectory}\\{$this->fqnEnd}\\Pages\\Manage{$pluralModelBasename}.php")
            ->replace('\\', '/')
            ->replace('//', '/');

        if (! $this->option('force') && $this->checkForCollision($path)) {
            throw new InvalidCommandOutput;
        }

        $this->writeFile($path, app(ResourceManageRecordsPageClassGenerator::class, [
            'fqn' => "{$this->fqn}\\Pages\\Manage{$pluralModelBasename}",
            'resourceFqn' => $this->fqn,
        ]));
    }

    protected function createListPage(): void
    {
        if ($this->isSimple) {
            return;
        }

        $modelBasename = class_basename($this->modelFqn);
        $pluralModelBasename = Str::pluralStudly($modelBasename);

        $path = (string) str("{$this->resourcesDirectory}\\{$this->fqnEnd}\\Pages\\List{$pluralModelBasename}.php")
            ->replace('\\', '/')
            ->replace('//', '/');

        if (! $this->option('force') && $this->checkForCollision($path)) {
            throw new InvalidCommandOutput;
        }

        $this->writeFile($path, app(ResourceListRecordsPageClassGenerator::class, [
            'fqn' => "{$this->fqn}\\Pages\\List{$pluralModelBasename}",
            'resourceFqn' => $this->fqn,
        ]));
    }
}
