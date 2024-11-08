<?php

namespace Filament\Commands\Concerns;

use Filament\Facades\Filament;
use Filament\Panel;
use Illuminate\Support\Arr;

use function Laravel\Prompts\select;

trait HasPanel
{
    protected ?Panel $panel;

    protected function configurePanel(string $question): void
    {
        $panelName = $this->option('panel');

        $this->panel = filled($panelName) ? Filament::getPanel($panelName, isStrict: false) : null;

        if ($this->panel) {
            return;
        }

        $panels = Filament::getPanels();

        /** @var Panel $panel */
        $panel = (count($panels) > 1) ? $panels[select(
            label: $question,
            options: array_map(
                fn (Panel $panel): string => $panel->getId(),
                $panels,
            ),
            default: Filament::getDefaultPanel()->getId(),
        )] : Arr::first($panels);

        $this->panel = $panel;
    }
}
