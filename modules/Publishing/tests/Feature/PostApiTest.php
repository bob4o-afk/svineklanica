<?php

declare(strict_types=1);

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Modules\Publishing\Enums\PostStatus;
use Modules\Publishing\Models\Post;

it('publicly lists only published posts', function () {
    Post::factory()->count(2)->create();              // published (factory default)
    Post::factory()->draft()->create();
    Post::factory()->archived()->create();

    $response = $this->getJson('/api/posts')->assertOk();

    expect($response->json('data'))->toHaveCount(2);
    foreach ($response->json('data') as $post) {
        expect($post['status'])->toBe(PostStatus::Published->value);
    }
});

it('shows a published post but 404s a draft', function () {
    $published = Post::factory()->create();
    $draft = Post::factory()->draft()->create();

    $this->getJson("/api/posts/{$published->public_id}")
        ->assertOk()
        ->assertJsonPath('publicId', $published->public_id);

    $this->getJson("/api/posts/{$draft->public_id}")->assertNotFound();
});

it('forbids guests from creating posts', function () {
    $this->postJson('/api/admin/posts', ['title' => 'x', 'body' => 'y'])
        ->assertUnauthorized();
});

it('forbids non-admins from creating posts', function () {
    Sanctum::actingAs(User::factory()->create()); // not an admin

    $this->postJson('/api/admin/posts', ['title' => 'x', 'body' => 'y'])
        ->assertForbidden();
});

it('lets an admin create a post in Draft', function () {
    Sanctum::actingAs(User::factory()->admin()->create());

    $response = $this->postJson('/api/admin/posts', [
        'title' => 'Скандал с обществена поръчка',
        'excerpt' => 'Кратко описание',
        'body' => 'Пълен текст на разследването',
        'sourceUrls' => ['https://ted.europa.eu/notice/1'],
    ])->assertCreated();

    $response->assertJsonPath('status', PostStatus::Draft->value)
        ->assertJsonPath('viewCount', 0);
    expect(Post::count())->toBe(1);
});

it('lets an admin update and publish a post', function () {
    Sanctum::actingAs(User::factory()->admin()->create());
    $post = Post::factory()->draft()->create();

    $this->putJson("/api/admin/posts/{$post->public_id}", [
        'title' => 'Обновено заглавие',
        'excerpt' => null,
        'body' => 'Обновен текст',
        'status' => PostStatus::Published->value,
        'sourceUrls' => ['https://example.org/src'],
    ])->assertOk()->assertJsonPath('status', PostStatus::Published->value);

    expect($post->fresh()->published_at)->not->toBeNull();
});

it('lets an admin soft-delete a post', function () {
    Sanctum::actingAs(User::factory()->admin()->create());
    $post = Post::factory()->create();

    $this->deleteJson("/api/admin/posts/{$post->public_id}")->assertNoContent();

    $this->assertSoftDeleted($post);
});
