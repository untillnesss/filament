<?php

namespace Filament\Support\Colors;

use Closure;
use Filament\Support\Concerns\EvaluatesClosures;
use Filament\Support\Facades\FilamentColor;
use Filament\Support\View\Components\Contracts\DoesNotHaveGrayColor;
use Illuminate\Support\Arr;
use Illuminate\View\ComponentAttributeBag;

class ColorManager
{
    use EvaluatesClosures;

    const DEFAULT_COLORS = [
        'danger' => Color::Red,
        'gray' => Color::Zinc,
        'info' => Color::Blue,
        'primary' => Color::Amber,
        'success' => Color::Green,
        'warning' => Color::Amber,
    ];

    /**
     * @var array<array<string, array{50: string, 100: string, 200: string, 300: string, 400: string, 500: string, 600: string, 700: string, 800: string, 900: string, 950: string} | string> | Closure>
     */
    protected array $colors = [];

    /**
     * @var array<string, array{50: string, 100: string, 200: string, 300: string, 400: string, 500: string, 600: string, 700: string, 800: string, 900: string, 950: string}>
     */
    protected array $cachedColors;

    /**
     * @var array<string,array<int | string>>
     */
    protected array $overridingShades = [];

    /**
     * @var array<string,array<int | string>>
     */
    protected array $addedShades = [];

    /**
     * @var array<string,array<int | string>>
     */
    protected array $removedShades = [];

    protected array $componentColorClasses = [];

    /**
     * @param  array<string, array{50: string, 100: string, 200: string, 300: string, 400: string, 500: string, 600: string, 700: string, 800: string, 900: string, 950: string} | string> | Closure  $colors
     */
    public function register(array | Closure $colors): static
    {
        $this->colors[] = $colors;

        return $this;
    }

    /**
     * @return array<string, array{50: string, 100: string, 200: string, 300: string, 400: string, 500: string, 600: string, 700: string, 800: string, 900: string, 950: string}>
     */
    public function getColors(): array
    {
        if (isset($this->cachedColors)) {
            return $this->cachedColors;
        }

        $this->cachedColors = static::DEFAULT_COLORS;

        foreach ($this->colors as $index => $colors) {
            $this->colors[$index] = $this->evaluate($colors);

            foreach ($this->colors[$index] as $name => $color) {
                if (is_string($color)) {
                    $color = Color::generatePalette($color);
                } else {
                    $color = array_map(
                        fn (string | int $color): string | int => is_string($color) ? Color::convertToOklch($color) : $color,
                        $color,
                    );
                }

                $shades = collect($color)
                    ->keys()
                    ->filter(fn (int | string $key): bool => is_numeric($key))
                    ->all();

                $colorOnlyShades = Arr::only($color, $shades);

                if (! array_key_exists((Arr::first($shades) . '-text'), $color)) {
                    $color = [
                        ...Color::findMatchingAccessibleTextColorsForBackgroundColors($colorOnlyShades),
                        ...$color,
                    ];
                }

                if (! array_key_exists("white-text", $color)) {
                    $color = [
                        ...Color::findMatchingAccessibleTextColorsForGrayBackgroundColors($colorOnlyShades),
                        ...$color,
                    ];
                }

                $this->cachedColors[$name] = $color;
            }
        }

        return $this->cachedColors;
    }

    /**
     * @return array{50: string, 100: string, 200: string, 300: string, 400: string, 500: string, 600: string, 700: string, 800: string, 900: string, 950: string}
     */
    public function getColor(string $color): ?array
    {
        return $this->getColors()[$color] ?? null;
    }

    /**
     * @param  array<int | string>  $shades
     */
    public function overrideShades(string $alias, array $shades): void
    {
        $this->overridingShades[$alias] = $shades;
    }

    /**
     * @return array<int | string> | null
     */
    public function getOverridingShades(string $alias): ?array
    {
        return $this->overridingShades[$alias] ?? null;
    }

    /**
     * @param  array<int | string>  $shades
     */
    public function addShades(string $alias, array $shades): void
    {
        $this->addedShades[$alias] = $shades;
    }

    /**
     * @return array<int | string> | null
     */
    public function getAddedShades(string $alias): ?array
    {
        return $this->addedShades[$alias] ?? null;
    }

    /**
     * @param  array<int | string>  $shades
     */
    public function removeShades(string $alias, array $shades): void
    {
        $this->removedShades[$alias] = $shades;
    }

    /**
     * @return array<int | string> | null
     */
    public function getRemovedShades(string $alias): ?array
    {
        return $this->removedShades[$alias] ?? null;
    }

    public function applyColorToComponentAttributes(string $color, string $component, ComponentAttributeBag $attributes): ComponentAttributeBag
    {
        $component = app($component);

        if (($color === 'gray') && ($component instanceof DoesNotHaveGrayColor)) {
            return $attributes;
        }

        if ($this->componentColorClasses[$component::class][$color] ?? []) {
            return $attributes->class($this->componentColorClasses[$component::class][$color]);
        }

        $classes = ['fi-color', "fi-color-{$color}"];

        $resolvedColor = FilamentColor::getColor($color);

        if (! $resolvedColor) {
            $this->componentColorClasses[$component::class][$color] = $classes;

            return $attributes->class($classes);
        }

        $classes = [
            ...$classes,
            ...$component->getColorClasses($resolvedColor),
        ];

        $this->componentColorClasses[$component::class][$color] = $classes;

        return $attributes->class($classes);
    }
}
