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

    /**
     * @var array<string, array{50: string, 100: string, 200: string, 300: string, 400: string, 500: string, 600: string, 700: string, 800: string, 900: string, 950: string}>
     */
    protected array $normalizedColors = [];

    /**
     * @var array<string, ?array{50: string, 100: string, 200: string, 300: string, 400: string, 500: string, 600: string, 700: string, 800: string, 900: string, 950: string}>
     */
    protected array $indexedColors = [];

    /**
     * @var array<string, bool>
     */
    protected array $lightnessIndex = [];

    protected array $componentColorClasses = [];

    protected array $componentColorStyles = [];

    /**
     * @param  array<string, array{50: string, 100: string, 200: string, 300: string, 400: string, 500: string, 600: string, 700: string, 800: string, 900: string, 950: string} | string> | Closure  $colors
     */
    public function register(array | Closure $colors): static
    {
        $this->colors[] = $colors;

        return $this;
    }

    /**
     * @param  array{50: string, 100: string, 200: string, 300: string, 400: string, 500: string, 600: string, 700: string, 800: string, 900: string, 950: string} | string  $color
     * @return array{50: string, 100: string, 200: string, 300: string, 400: string, 500: string, 600: string, 700: string, 800: string, 900: string, 950: string}
     */
    public function normalizeColor(array | string $color): array
    {
        $serializedColor = serialize($color);

        if (array_key_exists($serializedColor, $this->normalizedColors)) {
            return $this->normalizedColors[$serializedColor];
        }

        if (is_string($color)) {
            $color = Color::generatePalette($color);
        } else {
            $color = array_map(
                fn (string | int $color): string | int => is_string($color)
                    ? Color::convertToOklch($color)
                    : $color,
                $color,
            );
        }

        $firstShade = collect($color)
            ->keys()
            ->first(fn (int | string $key): bool => is_numeric($key));

        if (! array_key_exists("{$firstShade}-text", $color)) {
            $color = [
                ...Color::findMatchingAccessibleTextColorsForBackgroundColors($color),
                ...$color,
            ];
        }

        if (! array_key_exists("white-text", $color)) {
            $color = [
                ...Color::findMatchingAccessibleTextColorsForGrayBackgroundColors($color),
                ...$color,
            ];
        }

        return $this->normalizedColors[$serializedColor] = $color;
    }

    /**
     * @return array<string, array{50: string, 100: string, 200: string, 300: string, 400: string, 500: string, 600: string, 700: string, 800: string, 900: string, 950: string}>
     */
    public function getColors(): array
    {
        if (isset($this->cachedColors)) {
            return $this->cachedColors;
        }

        $cachedColors = static::DEFAULT_COLORS;

        foreach ($this->colors as $index => $colors) {
            $this->colors[$index] = $this->evaluate($colors);

            foreach ($this->colors[$index] as $name => $color) {
                $cachedColors[$name] = $this->normalizeColor($color);
            }
        }

        return $this->cachedColors = $cachedColors;
    }

    /**
     * @return array{50: string, 100: string, 200: string, 300: string, 400: string, 500: string, 600: string, 700: string, 800: string, 900: string, 950: string}
     */
    public function getColor(string $color): ?array
    {
        return $this->indexedColors[$color] ??= $this->getColors()[$color] ?? null;
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

    public function isLight(string $oklchColor): bool
    {
        return $this->lightnessIndex[$oklchColor] ??= Color::isLight($oklchColor);
    }

    public function applyColorToComponentAttributes(string | array $color, string $component, ComponentAttributeBag $attributes): ComponentAttributeBag
    {
        $component = app($component);

        if (($color === 'gray') && ($component instanceof DoesNotHaveGrayColor)) {
            return $attributes;
        }

        $serializedColor = serialize($color);

        if (
            filled($this->componentColorClasses[$component::class][$serializedColor] ?? []) ||
            filled($this->componentColorStyles[$component::class][$serializedColor] ?? [])
        ) {
            return $attributes
                ->class($this->componentColorClasses[$component::class][$serializedColor] ?? [])
                ->style($this->componentColorStyles[$component::class][$serializedColor] ?? []);
        }

        $classes = ['fi-color'];

        if (is_string($color) && ($resolvedColor = FilamentColor::getColor($color))) {
            $classes[] = "fi-color-{$color}";

            $color = $resolvedColor;
        }

        if (is_string($color)) {
            $cssVariables = $component->getCustomColorCssVariables(
                Color::convertToOklch($color),
            );

            $styles = array_reduce(
                array_keys($cssVariables),
                fn (array $carry, string $key): array => [
                    ...$carry,
                    "--{$key}:{$cssVariables[$key]}",
                ],
                initial: [],
            );

            $this->componentColorClasses[$component::class][$serializedColor] = $classes;
            $this->componentColorStyles[$component::class][$serializedColor] = $styles;

            return $attributes
                ->class($classes)
                ->style($styles);
        }

        $classes = [
            ...$classes,
            ...$component->getColorClasses($color),
        ];

        $this->componentColorClasses[$component::class][$serializedColor] = $classes;

        return $attributes->class($classes);
    }
}
