<?php

namespace Filament\Schemas\Contracts;

use Filament\Actions\Action;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Filament\Support\Contracts\TranslatableContentDriver;

interface HasSchemas
{
    public function makeFilamentTranslatableContentDriver(): ?TranslatableContentDriver;

    public function getOldSchemaState(string $statePath): mixed;

    public function getSchemaComponent(string $key, bool $withHidden = false): Component | Action | null;

    public function getSchema(string $name): ?Schema;
}
