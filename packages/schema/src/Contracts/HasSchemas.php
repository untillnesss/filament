<?php

namespace Filament\Schema\Contracts;

use Filament\Schema\Components\Component;
use Filament\Schema\Schema;
use Filament\Support\Contracts\TranslatableContentDriver;

interface HasSchemas
{
    public function makeFilamentTranslatableContentDriver(): ?TranslatableContentDriver;

    public function getOldSchemaState(string $statePath): mixed;

    public function getSchemaComponent(string $key): ?Component;

    public function getSchema(string $name): ?Schema;
}
