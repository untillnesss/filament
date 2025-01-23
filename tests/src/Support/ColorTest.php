<?php

use Filament\Support\Colors\Color;
use Filament\Tests\TestCase;
use Illuminate\Support\Str;

uses(TestCase::class);

it('generates colors from a HEX value', function (string $color) {
    expect(Color::palette($color))
        ->toMatchSnapshot();
})->with([
    '#49D359',
    '#8A2BE2',
    '#A52A2A',
    '#000000',
    '#FFFFFF',
]);

it('generates colors from an RGB value', function (string $color) {
    expect(Color::palette($color))
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
