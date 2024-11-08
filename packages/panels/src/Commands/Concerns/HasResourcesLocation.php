<?php

namespace Filament\Commands\Concerns;

use Illuminate\Support\Arr;

use function Laravel\Prompts\search;

trait HasResourcesLocation
{
    protected string $resourcesNamespace;

    protected string $resourcesDirectory;

    protected function configureResourcesLocation(string $question): void
    {
        if (filled($this->clusterFqn)) {
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
            label: $question,
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
}
