<?php

namespace Filament\Support\View\Components;

use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;
use Filament\Support\View\Components\Contracts\HasColor;
use Filament\Support\View\Components\Contracts\HasDefaultGrayColor;

class Badge implements HasColor, HasDefaultGrayColor
{
    /**
     * @param  array<int, string>  $color
     * @return array<string>
     */
    public function getColorClasses(array $color): array
    {
        ksort($color);

        foreach ($color as $shade => $shadeValue) {
            if (Color::isIconContrastRatioAccessible($color[50], $shadeValue)) {
                $text = $shade;

                break;
            }
        }

        $text ??= 900;

        krsort($color);

        $gray = FilamentColor::getColor('gray');
        $lightestDarkGrayBg = $gray[500];

        foreach ($color as $shade => $shadeValue) {
            if ($shade > 500) {
                continue;
            }

            if (Color::isIconContrastRatioAccessible($lightestDarkGrayBg, $shadeValue)) {
                $darkText = $shade;

                break;
            }
        }

        $darkText ??= 200;

        return [
            "fi-text-color-{$text}",
            "dark:fi-text-color-{$darkText}",
        ];
    }
}
