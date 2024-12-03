<?php

namespace Filament\Tests\Schemas\Components\StateCasts\Fixtures;

enum StringBackedEnum: string
{
    case One = 'one';
    case Two = 'two';
    case Three = 'three';
}
