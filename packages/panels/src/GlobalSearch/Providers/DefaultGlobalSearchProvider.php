<?php

namespace Filament\GlobalSearch\Providers;

use Filament\Facades\Filament;
use Filament\GlobalSearch\GlobalSearchResults;
use Filament\GlobalSearch\Providers;

class DefaultGlobalSearchProvider implements Providers\Contracts\GlobalSearchProvider
{
    public function getResults(string $query): ?GlobalSearchResults
    {
        $builder = GlobalSearchResults::make();

        foreach (Filament::getResources() as $resource) {
            if (! $resource::canGloballySearch()) {
                continue;
            }

            $resourceResults = $resource::getGlobalSearchResults($query);

            if (! $resourceResults->count()) {
                continue;
            }

            $builder->category($resource::getPluralModelLabel(), $resourceResults);
        }

        return $builder;
    }
}
