<?php

namespace Filament\Support\View\Components;

use Filament\Support\Colors\Color;
use Filament\Support\View\Components\Contracts\HasColor;
use Filament\Support\View\Components\Contracts\HasDefaultGrayColor;

class Button implements HasColor, HasDefaultGrayColor
{
    /**
     * @param  array<int | string, string | int>  $color
     * @return array<string>
     */
    public function getColorClasses(array $color): array
    {
        $bg = $color['button.bg'] ?? null;
        $hoverBg = $color['button.hover:bg'] ?? null;
        $darkBg = $color['button.dark:bg'] ?? null;
        $darkHoverBg = $color['button.dark:hover:bg'] ?? null;

        if ($bg && $hoverBg && $darkBg && $darkHoverBg) {
            return [
                "fi-bg-color-{$bg}",
                "hover:fi-bg-color-{$hoverBg}",
                "dark:fi-bg-color-{$darkBg}",
                "dark:hover:fi-bg-color-{$darkHoverBg}",
            ];
        }

        $textLightnessIndex = $this->generateTextLightnessIndexForColorShades($color);

        if (blank($bg)) {
            if ($textLightnessIndex[600] && $textLightnessIndex[500]) {
                $bg = 600;
                $matchingHoverBg = 500;
            } else {
                $bg = 400;
                $matchingHoverBg = ($textLightnessIndex[400] === $textLightnessIndex[300])
                    ? 300
                    : 500;
            }
        }

        if (blank($hoverBg)) {
            $hoverBg ??= $matchingHoverBg ?? match (true) {
                $textLightnessIndex[$bg] === $textLightnessIndex[$bg - 100] => $bg - 100,
                $textLightnessIndex[$bg] === $textLightnessIndex[$bg + 100] => $bg + 100,
                default => $bg - 100,
            };
        }

        if (blank($darkBg)) {
            $darkBg = 600;
            $matchingDarkHoverBg = ($textLightnessIndex[600] === $textLightnessIndex[500])
                ? 500
                : 700;
        }

        if (blank($darkHoverBg)) {
            $darkHoverBg ??= $matchingDarkHoverBg ?? match (true) {
                $textLightnessIndex[$darkBg] === $textLightnessIndex[$darkBg - 100] => $darkBg - 100,
                $textLightnessIndex[$darkBg] === $textLightnessIndex[$darkBg + 100] => $darkBg + 100,
                default => $darkBg - 100,
            };
        }

        return [
            "fi-bg-color-{$bg}",
            "hover:fi-bg-color-{$hoverBg}",
            "dark:fi-bg-color-{$darkBg}",
            "dark:hover:fi-bg-color-{$darkHoverBg}",
        ];
    }

    /**
     * @param  array<int | string, string | int>  $color
     * @return array<int, bool>
     */
    protected function generateTextLightnessIndexForColorShades(array $color): array
    {
        $textLightnessIndex = [];

        foreach (array_keys($color) as $shade) {
            if (! is_numeric($shade)) {
                continue;
            }

            $textShade = $color["{$shade}-text"];

            if ($textShade === 0) { // White
                $textLightnessIndex[$shade] = true;

                continue;
            }

            $textLightnessIndex[$shade] = Color::isLight($color[$textShade]);
        }

        return $textLightnessIndex;
    }
}
