<?php

namespace Filament\Support\Facades;

use Filament\Support\CliManager;
use Illuminate\Support\Facades\Facade;

/**
 * @method static void registerComponentLocation(string $directory, string $namespace, ?string $viewNamespace = null)
 * @method static array<string, string> getComponentLocations()
 *
 * @see CliManager
 */
class FilamentCli extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return CliManager::class;
    }
}
