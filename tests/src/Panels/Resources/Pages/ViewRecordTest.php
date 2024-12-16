<?php

use Filament\Facades\Filament;
use Filament\Tests\Fixtures\Models\Post;
use Filament\Tests\Fixtures\Models\TicketMessage;
use Filament\Tests\Fixtures\Resources\Posts\Pages\ViewPost;
use Filament\Tests\Fixtures\Resources\Posts\PostResource;
use Filament\Tests\Fixtures\Resources\TicketMessages\TicketMessageResource;
use Filament\Tests\Panels\Resources\TestCase;
use Illuminate\Support\Str;

use function Filament\Tests\livewire;

uses(TestCase::class);

it('can render page', function () {
    $this->get(PostResource::getUrl('view', [
        'record' => Post::factory()->create(),
    ]))->assertSuccessful();
});

it('can retrieve data', function () {
    $post = Post::factory()->create();

    livewire(ViewPost::class, [
        'record' => $post->getKey(),
    ])
        ->assertFormSet([
            'author_id' => $post->author->getKey(),
            'content' => $post->content,
            'tags' => $post->tags,
            'title' => $post->title,
        ]);
});

it('can refresh data', function () {
    $post = Post::factory()->create();

    $page = livewire(ViewPost::class, [
        'record' => $post->getKey(),
    ]);

    $originalPostTitle = $post->title;

    $page->assertFormSet([
        'title' => $originalPostTitle,
    ]);

    $newPostTitle = Str::random();

    $post->title = $newPostTitle;
    $post->save();

    $page->assertFormSet([
        'title' => $originalPostTitle,
    ]);

    $page->call('refreshTitle');

    $page->assertFormSet([
        'title' => $newPostTitle,
    ]);
});

it('can ticket messages page without a policy', function () {
    $message = TicketMessage::factory()
        ->create();

    $this->get(TicketMessageResource::getUrl('view', ['record' => $message]))
        ->assertSuccessful();
});

it('does not render ticket messages page without a policy if authorization is strict', function () {
    Filament::getCurrentPanel()->strictAuthorization();

    $message = TicketMessage::factory()
        ->create();

    $this->get(TicketMessageResource::getUrl('view', ['record' => $message]))
        ->assertServerError();

    Filament::getCurrentPanel()->strictAuthorization(false);
});
