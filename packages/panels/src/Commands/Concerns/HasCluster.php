<?php

namespace Filament\Commands\Concerns;

use Filament\Clusters\Cluster;
use ReflectionClass;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\search;
use function Laravel\Prompts\text;

trait HasCluster
{
    /**
     * @var ?class-string<Cluster>
     */
    protected ?string $clusterFqn = null;

    protected function configureClusterFqn(string $initialQuestion, string $question): void
    {
        $cluster = $this->option('cluster');

        $clusterFqns = array_values($this->panel->getClusters());

        if (
            blank($cluster) &&
            (empty($clusterFqns) || (! confirm(
                label: $initialQuestion,
                default: false,
            )))
        ) {
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

        if (empty($clusterFqns)) {
            $this->clusterFqn = (string) str(text(
                label: "No clusters were found within the [{$this->panel->getId()}] panel. {$question}",
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
            label: $question,
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

    protected function configureClusterResourcesLocation(): void
    {
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
    }
}
