<?php

namespace Filament\Resources\Resource\Concerns;

use Exception;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

use function Filament\Support\original_request;

trait CanGenerateUrls
{
    /**
     * @param  array<mixed>  $parameters
     */
    public static function getUrl(?string $name = null, array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?Model $tenant = null): string
    {
        $record = $parameters['record'] ?? null;
        $parentResource = static::getParentResourceRegistration();
        $isFirstParentResource = true;
        $originalRequest = null;

        while (filled($parentResource)) {
            if ($record instanceof Collection) {
                throw new Exception("Attempted to generate a URL for the [{$name}] route on the [" . static::class . "] resource, but the [{$parentResourceInverseRelationshipName}] parameter was missing and its value cannot be guessed since it relies on a multiple relationship.");
            }

            $parentResourceInverseRelationshipName = $parentResource->getInverseRelationshipName();

            if ($parameters[$parentResource->getParentRouteParameterName()] ?? null) {
                $record = $parameters[$parentResource->getParentRouteParameterName()] ?? null;
            } elseif (
                $record &&
                method_exists($record, $parentResourceInverseRelationshipName) &&
                ($record->{$parentResourceInverseRelationshipName}() instanceof BelongsToMany)
            ) {
                $originalRequest ??= original_request();

                $recordKey = $isFirstParentResource
                    ? $originalRequest->route()->parameter('record')
                    : $originalRequest->route()->parameter($parentResource->getParentRouteParameterName());

                if (blank($recordKey)) {
                    throw new Exception("Attempted to generate a URL for the [{$name}] route on the [" . static::class . "] resource, but the [{$parentResourceInverseRelationshipName}] parameter was missing and its value cannot be guessed since it relies on a multiple relationship.");
                }

                $record = $parentResource->getParentResource()::resolveRecordRouteBinding($recordKey);

                if (! $record) {
                    throw new Exception("Attempted to generate a URL for the [{$name}] route on the [" . static::class . "] resource, but the [{$parentResourceInverseRelationshipName}] parameter was missing and its value cannot be guessed since it relies on a multiple relationship.");
                }
            } else {
                $record = $record?->{$parentResourceInverseRelationshipName};
            }

            $parameters[$parentResource->getParentRouteParameterName()] ??= $record;
            $parameters['record'] ??= $record;

            $parentResource = $parentResource->getParentResource()::getParentResourceRegistration();

            $isFirstParentResource = false;
        }

        if (blank($name)) {
            return static::getIndexUrl($parameters, $isAbsolute, $panel, $tenant);
        }

        if (blank($panel) || Filament::getPanel($panel)->hasTenancy()) {
            $parameters['tenant'] ??= ($tenant ?? Filament::getTenant());
        }

        $routeBaseName = static::getRouteBaseName(panel: $panel);

        return route("{$routeBaseName}.{$name}", $parameters, $isAbsolute);
    }

    /**
     * @param  array<mixed>  $parameters
     */
    public static function getIndexUrl(array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?Model $tenant = null): string
    {
        $parentResourceRegistration = static::getParentResource();

        if ($parentResourceRegistration) {
            $parentResource = $parentResourceRegistration->getParentResource();
            $parentRouteParameterName = $parentResourceRegistration->getParentRouteParameterName();

            $record = $parameters[$parentRouteParameterName] ?? null;
            unset($parameters[$parentRouteParameterName]);

            if ($parentResource::hasPage($relationshipPageName = $parentResourceRegistration->getRouteName())) {
                return $parentResource::getUrl($relationshipPageName, [
                    ...$parameters,
                    'record' => $record,
                ], $isAbsolute, $panel, $tenant);
            }

            if ($parentResource::hasPage('view')) {
                return $parentResource::getUrl('view', [
                    'activeRelationManager' => $parentResourceRegistration->getRelationshipName(),
                    ...$parameters,
                    'record' => $record,
                ], $isAbsolute, $panel, $tenant);
            }

            if ($parentResource::hasPage('edit')) {
                return $parentResource::getUrl('edit', [
                    'activeRelationManager' => $parentResourceRegistration->getRelationshipName(),
                    ...$parameters,
                    'record' => $record,
                ], $isAbsolute, $panel, $tenant);
            }

            if ($parentResource::hasPage('index')) {
                return $parentResource::getUrl('index', $parameters, $isAbsolute, $panel, $tenant);
            }
        }

        if (! static::hasPage('index')) {
            throw new Exception('The resource [' . static::class . '] does not have an [index] page or define [getIndexUrl()] for alternative routing.');
        }

        return static::getUrl('index', $parameters, $isAbsolute, $panel, $tenant);
    }
}
