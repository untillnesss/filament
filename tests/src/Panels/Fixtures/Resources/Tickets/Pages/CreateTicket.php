<?php

namespace Filament\Tests\Panels\Fixtures\Resources\Tickets\Pages;

use Filament\Resources\Pages\CreateRecord;
use Filament\Tests\Panels\Fixtures\Resources\Tickets\TicketResource;

class CreateTicket extends CreateRecord
{
    protected static string $resource = TicketResource::class;
}
