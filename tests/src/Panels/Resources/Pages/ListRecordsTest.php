<?php

use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Facades\Filament;
use Filament\Tests\Fixtures\Models\Post;
use Filament\Tests\Fixtures\Models\TicketMessage;
use Filament\Tests\Fixtures\Resources\Posts\Pages\ListPosts;
use Filament\Tests\Fixtures\Resources\Posts\PostResource;
use Filament\Tests\Fixtures\Resources\TicketMessages\TicketMessageResource;
use Filament\Tests\Fixtures\Resources\Users\UserResource;
use Filament\Tests\Panels\Resources\TestCase;

use function Filament\Tests\livewire;
use function Pest\Laravel\assertSoftDeleted;

uses(TestCase::class);

it('can render posts page', function () {
    $this->get(PostResource::getUrl('index'))
        ->assertSuccessful();
});

it('can render users page', function () {
    $this->get(UserResource::getUrl('index'))
        ->assertSuccessful();
});

it('can list posts', function () {
    $posts = Post::factory()->count(10)->create();

    livewire(ListPosts::class)
        ->assertCanSeeTableRecords($posts);
});

it('can render post titles', function () {
    Post::factory()->count(10)->create();

    livewire(ListPosts::class)
        ->assertCanRenderTableColumn('title');
});

it('can render post authors', function () {
    Post::factory()->count(10)->create();

    livewire(ListPosts::class)
        ->assertCanRenderTableColumn('author.name');
});

it('can sort posts by title', function () {
    $posts = Post::factory()->count(10)->create();

    livewire(ListPosts::class)
        ->sortTable('title')
        ->assertCanSeeTableRecords($posts->sortBy('title'), inOrder: true)
        ->sortTable('title', 'desc')
        ->assertCanSeeTableRecords($posts->sortByDesc('title'), inOrder: true);
});

it('can sort posts by author', function () {
    $posts = Post::factory()->count(10)->create();

    livewire(ListPosts::class)
        ->sortTable('author.name')
        ->assertCanSeeTableRecords($posts->sortBy('author.name'), inOrder: true)
        ->sortTable('author.name', 'desc')
        ->assertCanSeeTableRecords($posts->sortByDesc('author.name'), inOrder: true);
});

it('can search posts by title', function () {
    $posts = Post::factory()->count(10)->create();

    $title = $posts->first()->title;

    livewire(ListPosts::class)
        ->searchTable($title)
        ->assertCanSeeTableRecords($posts->where('title', $title))
        ->assertCanNotSeeTableRecords($posts->where('title', '!=', $title));
});

it('can search posts by author', function () {
    $posts = Post::factory()->count(10)->create();

    $author = $posts->first()->author->name;

    livewire(ListPosts::class)
        ->searchTable($author)
        ->assertCanSeeTableRecords($posts->where('author.name', $author))
        ->assertCanNotSeeTableRecords($posts->where('author.name', '!=', $author));
});

it('can filter posts by `is_published`', function () {
    $posts = Post::factory()->count(10)->create();

    livewire(ListPosts::class)
        ->assertCanSeeTableRecords($posts)
        ->filterTable('is_published')
        ->assertCanSeeTableRecords($posts->where('is_published', true))
        ->assertCanNotSeeTableRecords($posts->where('is_published', false));
});

it('can delete posts', function () {
    $post = Post::factory()->create();

    livewire(ListPosts::class)
        ->callTableAction(DeleteAction::class, $post);

    assertSoftDeleted($post);
});

it('can bulk delete posts', function () {
    $posts = Post::factory()->count(10)->create();

    livewire(ListPosts::class)
        ->callTableBulkAction(DeleteBulkAction::class, $posts);

    foreach ($posts as $post) {
        assertSoftDeleted($post);
    }
});

it('can render ticket messages page without a policy', function () {
    TicketMessage::factory(10)
        ->create();

    $this->get(TicketMessageResource::getUrl('index'))
        ->assertSuccessful();
});

it('does not render ticket messages page without a policy if authorization is strict', function () {
    Filament::getCurrentPanel()->strictAuthorization();

    TicketMessage::factory(10)
        ->create();

    $this->get(TicketMessageResource::getUrl('index'))
        ->assertServerError();

    Filament::getCurrentPanel()->strictAuthorization(false);
});
