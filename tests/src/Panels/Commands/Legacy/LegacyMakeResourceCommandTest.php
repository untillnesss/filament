<?php

use Filament\Support\Config\FileGenerationFlag;
use Filament\Tests\TestCase;
use function PHPUnit\Framework\assertFileDoesNotExist;
use function PHPUnit\Framework\assertFileExists;

uses(TestCase::class);

beforeEach(function () {
    config()->set('filament.file_generation.flags', [
        FileGenerationFlag::EMBEDDED_PANEL_RESOURCE_SCHEMAS,
        FileGenerationFlag::EMBEDDED_PANEL_RESOURCE_TABLES,
        FileGenerationFlag::PARTIAL_IMPORTS,
        FileGenerationFlag::PANEL_RESOURCE_CLASSES_OUTSIDE_DIRECTORIES,
    ]);
});

it('can generate a resource class', function () {
    $this->artisan('make:filament-resource', [
        'name' => 'Post',
        '--model-namespace' => 'Filament\Tests\Models',
        '--panel' => 'admin',
    ])->assertExitCode(0);

    assertFileExists($path = app_path('Filament/Resources/PostResource.php'));
    expect(file_get_contents($path))
        ->toMatchSnapshot();
});

it('can generate a resource list page', function () {
    $this->artisan('make:filament-resource', [
        'name' => 'Post',
        '--model-namespace' => 'Filament\Tests\Models',
        '--panel' => 'admin',
    ])->assertExitCode(0);

    assertFileExists($path = app_path('Filament/Resources/PostResource/Pages/ListPosts.php'));
    expect(file_get_contents($path))
        ->toMatchSnapshot();
});

it('can generate a resource create page', function () {
    $this->artisan('make:filament-resource', [
        'name' => 'Post',
        '--model-namespace' => 'Filament\Tests\Models',
        '--panel' => 'admin',
    ])->assertExitCode(0);

    assertFileExists($path = app_path('Filament/Resources/PostResource/Pages/CreatePost.php'));
    expect(file_get_contents($path))
        ->toMatchSnapshot();
});

it('can generate a resource edit page', function () {
    $this->artisan('make:filament-resource', [
        'name' => 'Post',
        '--model-namespace' => 'Filament\Tests\Models',
        '--panel' => 'admin',
    ])->assertExitCode(0);

    assertFileExists($path = app_path('Filament/Resources/PostResource/Pages/EditPost.php'));
    expect(file_get_contents($path))
        ->toMatchSnapshot();
});

it('can generate a resource view page', function () {
    $this->artisan('make:filament-resource', [
        'name' => 'Post',
        '--view' => true,
        '--model-namespace' => 'Filament\Tests\Models',
        '--panel' => 'admin',
    ])->assertExitCode(0);

    assertFileExists($path = app_path('Filament/Resources/PostResource/Pages/ViewPost.php'));
    expect(file_get_contents($path))
        ->toMatchSnapshot();
});

it('can generate the schemas and table of a resource class', function () {
    $this->artisan('make:filament-resource', [
        'name' => 'Post',
        '--generate' => true,
        '--view' => true,
        '--model-namespace' => 'Filament\Tests\Models',
        '--panel' => 'admin',
    ])->assertExitCode(0);

    assertFileExists($path = app_path('Filament/Resources/PostResource.php'));
    expect(file_get_contents($path))
        ->toMatchSnapshot();
});

it('can generate a resource class with soft deletes', function () {
    $this->artisan('make:filament-resource', [
        'name' => 'Post',
        '--soft-deletes' => true,
        '--model-namespace' => 'Filament\Tests\Models',
        '--panel' => 'admin',
    ])->assertExitCode(0);

    assertFileExists($path = app_path('Filament/Resources/PostResource.php'));
    expect(file_get_contents($path))
        ->toMatchSnapshot();
});

it('can generate a resource edit page with soft deletes', function () {
    $this->artisan('make:filament-resource', [
        'name' => 'Post',
        '--soft-deletes' => true,
        '--model-namespace' => 'Filament\Tests\Models',
        '--panel' => 'admin',
    ])->assertExitCode(0);

    assertFileExists($path = app_path('Filament/Resources/PostResource/Pages/EditPost.php'));
    expect(file_get_contents($path))
        ->toMatchSnapshot();
});

it('can generate a simple resource class', function () {
    $this->artisan('make:filament-resource', [
        'name' => 'Post',
        '--simple' => true,
        '--model-namespace' => 'Filament\Tests\Models',
        '--panel' => 'admin',
    ])->assertExitCode(0);

    assertFileExists($path = app_path('Filament/Resources/PostResource.php'));
    expect(file_get_contents($path))
        ->toMatchSnapshot();
});

it('can generate a simple resource manage page', function () {
    $this->artisan('make:filament-resource', [
        'name' => 'Post',
        '--simple' => true,
        '--model-namespace' => 'Filament\Tests\Models',
        '--panel' => 'admin',
    ])->assertExitCode(0);

    assertFileExists($path = app_path('Filament/Resources/PostResource/Pages/ManagePosts.php'));
    expect(file_get_contents($path))
        ->toMatchSnapshot();
});

it('can generate a resource class in a nested directory', function () {
    $this->artisan('make:filament-resource', [
        'name' => 'Blog/Post',
        '--model-namespace' => 'Filament\Tests\Models',
        '--panel' => 'admin',
    ])->assertExitCode(0);

    assertFileExists($path = app_path('Filament/Resources/Blog/PostResource.php'));
    expect(file_get_contents($path))
        ->toMatchSnapshot();
});

it('can generate a resource list page in a nested directory', function () {
    $this->artisan('make:filament-resource', [
        'name' => 'Blog/Post',
        '--model-namespace' => 'Filament\Tests\Models',
        '--panel' => 'admin',
    ])->assertExitCode(0);

    assertFileExists($path = app_path('Filament/Resources/Blog/PostResource/Pages/ListPosts.php'));
    expect(file_get_contents($path))
        ->toMatchSnapshot();
});

it('can generate a resource create page in a nested directory', function () {
    $this->artisan('make:filament-resource', [
        'name' => 'Blog/Post',
        '--model-namespace' => 'Filament\Tests\Models',
        '--panel' => 'admin',
    ])->assertExitCode(0);

    assertFileExists($path = app_path('Filament/Resources/Blog/PostResource/Pages/CreatePost.php'));
    expect(file_get_contents($path))
        ->toMatchSnapshot();
});

it('can generate a resource edit page in a nested directory', function () {
    $this->artisan('make:filament-resource', [
        'name' => 'Blog/Post',
        '--model-namespace' => 'Filament\Tests\Models',
        '--panel' => 'admin',
    ])->assertExitCode(0);

    assertFileExists($path = app_path('Filament/Resources/Blog/PostResource/Pages/EditPost.php'));
    expect(file_get_contents($path))
        ->toMatchSnapshot();
});

it('can generate a resource view page in a nested directory', function () {
    $this->artisan('make:filament-resource', [
        'name' => 'Blog/Post',
        '--view' => true,
        '--model-namespace' => 'Filament\Tests\Models',
        '--panel' => 'admin',
    ])->assertExitCode(0);

    assertFileExists($path = app_path('Filament/Resources/Blog/PostResource/Pages/ViewPost.php'));
    expect(file_get_contents($path))
        ->toMatchSnapshot();
});

it('can generate a simple resource manage page in a nested directory', function () {
    $this->artisan('make:filament-resource', [
        'name' => 'Blog/Post',
        '--simple' => true,
        '--model-namespace' => 'Filament\Tests\Models',
        '--panel' => 'admin',
    ])->assertExitCode(0);

    assertFileExists($path = app_path('Filament/Resources/Blog/PostResource/Pages/ManagePosts.php'));
    expect(file_get_contents($path))
        ->toMatchSnapshot();
});
