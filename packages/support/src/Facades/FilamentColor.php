<?php

namespace Filament\Support\Facades;

use Closure;
use Filament\Support\Colors\ColorManager;
use Illuminate\Support\Facades\Facade;

/**
 * @method static void addShades(string $alias, array<int> $shades)
 * @method static array<int> | null getAddedShades(string $alias)
 * @method static array<string, array{50: string, 100: string, 200: string, 300: string, 400: string, 500: string, 600: string, 700: string, 800: string, 900: string, 950: string}> getColors()
 * @method static array<string, array{50: string, 100: string, 200: string, 300: string, 400: string, 500: string, 600: string, 700: string, 800: string, 900: string, 950: string}> getDefaultColors()
 * @method static array{50: string, 100: string, 200: string, 300: string, 400: string, 500: string, 600: string, 700: string, 800: string, 900: string, 950: string} getColor(string $color)
 * @method static array<int> | null getOverridingShades(string $alias)
 * @method static array<int> | null getRemovedShades(string $alias)
 * @method static bool isLight(string $oklchColor)
 * @method static array{50: string, 100: string, 200: string, 300: string, 400: string, 500: string, 600: string, 700: string, 800: string, 900: string, 950: string} normalizeColor(array{50: string, 100: string, 200: string, 300: string, 400: string, 500: string, 600: string, 700: string, 800: string, 900: string, 950: string} | string $color)
 * @method static void overrideShades(string $alias, array<int> $shades)
 * @method static void removeShades(string $alias, array<int> $shades)
 *
 * @see ColorManager
 */
class FilamentColor extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ColorManager::class;
    }

    /**
     * @param  array<string, array{50: string, 100: string, 200: string, 300: string, 400: string, 500: string, 600: string, 700: string, 800: string, 900: string, 950: string} | string> | Closure  $colors
     */
    public static function register(array | Closure $colors): void
    {
        static::resolved(function (ColorManager $colorManager) use ($colors) {
            $colorManager->register($colors);
        });
    }
}
