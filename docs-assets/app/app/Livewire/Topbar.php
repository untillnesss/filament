<?php

namespace App\Livewire;

use Filament\Navigation\MenuItem;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class Topbar extends Page
{
    protected static string $view = 'livewire.topbar';

    public function mount()
    {
        filament()
            ->getCurrentOrDefaultPanel()
            ->userMenuItems([
                MenuItem::make()
                    ->label('Settings')
                    ->url(fn (): string => '#')
                    ->icon(Heroicon::OutlinedCog6Tooth),
            ]);
    }
}
