<?php

use Filament\Support\Colors\Color;
use Filament\Support\Colors\ColorManager;
use Filament\Support\Facades\FilamentColor;
use Filament\Support\View\Components\Badge;
use Filament\Support\View\Components\Button;
use Filament\Support\View\Components\Contracts\HasColor;
use Filament\Support\View\Components\Dropdown\Header as DropdownHeader;
use Filament\Support\View\Components\Dropdown\Item as DropdownItem;
use Filament\Support\View\Components\Dropdown\Item\Icon as DropdownItemIcon;
use Filament\Support\View\Components\IconButton;
use Filament\Support\View\Components\Link;
use Filament\Support\View\Components\Toggle;
use Filament\Tests\TestCase;
use Illuminate\Support\Str;

uses(TestCase::class);

it('generates colors from a HEX value', function (string $color) {
    expect(Color::generatePalette($color))
        ->toMatchSnapshot();
})->with([
    '#49D359',
    '#8A2BE2',
    '#A52A2A',
    '#000000',
    '#FFFFFF',
]);

it('generates colors from an RGB value', function (string $color) {
    expect(Color::generatePalette($color))
        ->toMatchSnapshot();
})->with([
    'rgb(128, 8, 8)',
    'rgb(93, 255, 2)',
    'rgb(243, 243, 21)',
    'rgb(0, 0, 0)',
    'rgb(255, 255, 255)',
]);

it('returns all colors', function () {
    $colors = [];

    foreach ((new ReflectionClass(Color::class))->getConstants() as $name => $color) {
        $colors[Str::lower($name)] = $color;
    }

    expect(Color::all())
        ->toBe($colors);
});

it('generates component classes', function (string | HasColor $component, string $color) {
    expect(FilamentColor::getComponentClasses($component, $color))
        ->toMatchSnapshot();
})
    ->with([
        'badge' => Badge::class,
        'button' => new Button(isOutlined: false),
        'outlined button' => new Button(isOutlined: true),
        'dropdown header' => DropdownHeader::class,
        'dropdown item icon' => DropdownItemIcon::class,
        'dropdown item' => DropdownItem::class,
        'icon button' => IconButton::class,
        'link' => Link::class,
        'toggle' => Toggle::class,
    ])
    ->with(fn (): array => array_keys(app(ColorManager::class)->getColors()));
