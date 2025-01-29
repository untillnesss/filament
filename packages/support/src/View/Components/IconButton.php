<?php

namespace Filament\Support\View\Components;

use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;
use Filament\Support\View\Components\Contracts\HasColor;
use Filament\Support\View\Components\Contracts\HasDefaultGrayColor;

class IconButton implements HasColor, HasDefaultGrayColor
{
    /**
     * @param  array<int | string, string | int>  $color
     * @return array<string>
     */
    public function getColorClasses(array $color): array
    {
        $text = $color['icon-button.text'] ?? null;
        $hoverText = $color['icon-button.hover:text'] ?? null;
        $darkText = $color['icon-button.dark:text'] ?? null;
        $darkHoverText = $color['icon-button.dark:hover:text'] ?? null;

        if ($text && $hoverText && $darkText && $darkHoverText) {
            return [
                "fi-text-color-{$text}",
                "hover:fi-text-color-{$hoverText}",
                "dark:fi-text-color-{$darkText}",
                "dark:hover:fi-text-color-{$darkHoverText}",
            ];
        }

        /**
         * Since the icon button doesn't contain text, the icon is imperative for the user to understand the
         * button's purpose. Therefore, the icon should have a color that contrasts at least 3:1 with the
         * background to remain compliant with WCAG AA standards.
         *
         * @ref https://www.w3.org/WAI/WCAG21/Understanding/non-text-contrast.html
         */
        if (blank($text) || blank($hoverText)) {
            ksort($color);

            $darkestLightGrayBg = FilamentColor::getColor('gray')[50];

            foreach ($color as $shade => $shadeValue) {
                if (! is_numeric($shade)) {
                    continue;
                }

                if (Color::isIconContrastRatioAccessible($darkestLightGrayBg, $shadeValue)) {
                    if ($shade > 500) {
                        // Shades above 500 are likely to be quite dark, so instead of lightening the button
                        // when it is hovered, we darken it.
                        $text ??= $shade;
                        $hoverText ??= $shade + 100;
                    } else {
                        $text ??= $shade + 100;
                        $hoverText ??= $shade;
                    }

                    break;
                }
            }

            $text ??= 900;
            $hoverText ??= 800;
        }

        if (blank($darkText) || blank($darkHoverText)) {
            krsort($color);

            $lightestDarkGrayBg = FilamentColor::getColor('gray')[800];

            foreach ($color as $shade => $shadeValue) {
                if (! is_numeric($shade)) {
                    continue;
                }

                if (Color::isIconContrastRatioAccessible($lightestDarkGrayBg, $shadeValue)) {
                    $darkText ??= $shade;
                    $darkHoverText ??= $shade - 100;

                    break;
                }
            }

            $darkText ??= 200;
            $darkHoverText ??= 100;
        }

        return [
            "fi-text-color-{$text}",
            "hover:fi-text-color-{$hoverText}",
            "dark:fi-text-color-{$darkText}",
            "dark:hover:fi-text-color-{$darkHoverText}",
        ];
    }
}
