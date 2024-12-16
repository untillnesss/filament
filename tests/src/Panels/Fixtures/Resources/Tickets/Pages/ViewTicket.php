<?php

namespace Filament\Tests\Panels\Fixtures\Resources\Tickets\Pages;

use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Tests\Panels\Fixtures\Resources\Tickets\TicketResource;

class ViewTicket extends ViewRecord
{
    protected static string $resource = TicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
