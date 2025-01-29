<?php

namespace Filament\Support\Colors;

use Closure;
use Filament\Support\Concerns\EvaluatesClosures;
use Filament\Support\View\Components\Contracts\HasColor;
use Filament\Support\View\Components\Contracts\HasDefaultGrayColor;

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
     * @var array<array<string, array<int | string, string | int> | string> | Closure>
     */
    protected array $colors = [];

    /**
     * @var array<string, array<int | string, string | int>>
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
     * @var array<class-string<HasColor>, array<string, array<string>>>
     */
    protected array $componentClasses = [];

    /**
     * @param  array<string, array<int | string, string | int> | string> | Closure  $colors
     */
    public function register(array | Closure $colors): static
    {
        $this->colors[] = $colors;

        return $this;
    }

    /**
     * @return array<string, array<int | string, string | int>>
     */
    public function getColors(): array
    {
        if (isset($this->cachedColors)) {
            return $this->cachedColors;
        }

        array_unshift($this->colors, static::DEFAULT_COLORS);

        foreach ($this->colors as $colors) {
            $colors = $this->evaluate($colors);

            foreach ($colors as $name => $color) {
                if (is_string($color)) {
                    $color = Color::generatePalette($color);
                } else {
                    $color = array_map(
                        fn (string | int $color): string | int => is_string($color) ? Color::convertToOklch($color) : $color,
                        $color,
                    );
                }

                $colorShades = $this->filterColorForShadesOnly($color);

                if (! array_key_exists((array_key_first($colorShades) . '-text'), $color)) {
                    $color = array_replace(
                        Color::findMatchingAccessibleTextColorsForBackgroundColors($colorShades),
                        $color,
                    );
                }

                $this->cachedColors[$name] = $color;
            }
        }

        if (array_key_exists('gray', $this->cachedColors)) {
            $gray = $this->cachedColors['gray'];

            foreach ($this->cachedColors as $name => $color) {
                if (! array_key_exists('white-text', $this->cachedColors[$name])) {
                    $this->cachedColors[$name] = array_replace(
                        Color::findMatchingAccessibleTextColorsForGrayBackgroundColors($this->filterColorForShadesOnly($this->cachedColors[$name]), $gray),
                        $this->cachedColors[$name],
                    );
                }
            }
        }

        return $this->cachedColors;
    }

    /**
     * @param  array<int | string, string | int>  $color
     * @return array<int, bool>
     */
    public function generateTextLightnessIndex(array $color): array
    {
        $textLightnessIndex = [];

        foreach (array_keys($this->filterColorForShadesOnly($color)) as $shade) {
            $textShade = $color["{$shade}-text"];

            if ($textShade === 0) { // White
                $textLightnessIndex[$shade] = true;

                continue;
            }

            $textLightnessIndex[$shade] = Color::isLight($color[$textShade]);
        }

        return $textLightnessIndex;
    }

    /**
     * @param  array<int | string, string | int>  $color
     * @return array<int, string | int>
     */
    protected function filterColorForShadesOnly(array $color): array
    {
        return collect($color)
            ->filter(fn ($value, $key): bool => is_numeric($key))
            ->all();
    }

    /**
     * @return ?array<int | string, string | int>
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

    /**
     * @param  class-string<HasColor>  $component
     * @return array<string>
     */
    public function getComponentClasses(string $component, string $color): array
    {
        $component = app($component);

        if (($color === 'gray') && ($component instanceof HasDefaultGrayColor)) {
            return [];
        }

        if ($this->componentClasses[$component::class][$color] ?? []) {
            return $this->componentClasses[$component::class][$color];
        }

        $classes = ['fi-color', "fi-color-{$color}"];

        $resolvedColor = $this->getColor($color);

        if (! $resolvedColor) {
            return $this->componentClasses[$component::class][$color] = $classes;
        }

        return $this->componentClasses[$component::class][$color] = [
            ...$classes,
            ...$component->getColorClasses($resolvedColor),
        ];
    }
}
