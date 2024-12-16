<?php

namespace Filament\Tests\Panels\Fixtures\Resources\Departments\Pages;

use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Tests\Panels\Fixtures\Resources\Departments\DepartmentResource;

class ViewDepartment extends ViewRecord
{
    protected static string $resource = DepartmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
