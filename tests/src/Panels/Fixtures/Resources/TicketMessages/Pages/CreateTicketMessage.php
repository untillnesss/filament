<?php

namespace Filament\Tests\Panels\Fixtures\Resources\TicketMessages\Pages;

use Filament\Resources\Pages\CreateRecord;
use Filament\Tests\Panels\Fixtures\Resources\TicketMessages\TicketMessageResource;

class CreateTicketMessage extends CreateRecord
{
    protected static string $resource = TicketMessageResource::class;
}
