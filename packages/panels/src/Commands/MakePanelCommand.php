<?php

namespace Filament\Commands;

use Filament\Support\Commands\Concerns\CanGeneratePanels;
use Filament\Support\Commands\Concerns\CanManipulateFiles;
use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'make:filament-panel', aliases: [
    'filament:make-panel',
    'filament:panel',
])]
class MakePanelCommand extends Command
{
    use CanGeneratePanels;
    use CanManipulateFiles;

    protected $description = 'Create a new Filament panel';

    protected $signature = 'make:filament-panel {id?} {--F|force}';

    /**
     * @var array<string>
     */
    protected $aliases = [
        'filament:make-panel',
        'filament:panel',
    ];

    public function handle(): int
    {
        $isPanelGenerated = $this->generatePanel(
            id: $this->argument('id'),
            placeholderId: 'app',
            isForced: $this->option('force'),
        );

        if (! $isPanelGenerated) {
            return static::FAILURE;
        }

        return static::SUCCESS;
    }
}
