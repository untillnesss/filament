<?php

namespace Filament\Actions\Contracts;

use Filament\Actions\Action;

interface HasActions
{
    /**
     * @param  string | array<string>  $name
     */
    public function getAction(string | array $name): ?Action;

    /**
     * @param  array<string, mixed>  $arguments
     * @param  array<string, mixed>  $context
     */
    public function mountAction(string $name, array $arguments = [], array $context = []): mixed;

    /**
     * @param  array<string, mixed>  $arguments
     */
    public function mergeMountedActionArguments(array $arguments): void;
}
