<?php

declare(strict_types=1);

use App\Models\User;
use App\Shared\Enums\CorruptionCategory;
use App\Shared\Enums\FlagSeverity;
use App\Shared\Enums\Sphere;
use Laravel\Sanctum\Sanctum;
use Modules\Publishing\Enums\PostStatus;
use Modules\Publishing\Enums\PostTag;
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

it('filters the feed by sphere, category and severity', function () {
    $match = Post::factory()->create([
        'sphere' => Sphere::Healthcare,
        'category' => CorruptionCategory::PublicProcurement,
        'severity' => FlagSeverity::High,
    ]);
    Post::factory()->create([
        'sphere' => Sphere::Police,
        'category' => CorruptionCategory::UnregulatedPayment,
        'severity' => FlagSeverity::Low,
    ]);

    $response = $this->getJson('/api/posts?'.http_build_query([
        'sphere' => Sphere::Healthcare->value,
        'category' => CorruptionCategory::PublicProcurement->value,
        'severity' => FlagSeverity::High->value,
    ]))->assertOk();

    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.publicId'))->toBe($match->public_id);
});

it('filters the feed by a punk tag', function () {
    $match = Post::factory()->create(['tags' => [PostTag::StealingMoney, PostTag::ShadyBusiness]]);
    Post::factory()->create(['tags' => [PostTag::DodgyDeals]]);

    $response = $this->getJson('/api/posts?tag='.PostTag::StealingMoney->value)->assertOk();

    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.publicId'))->toBe($match->public_id);
    expect($response->json('data.0.tags'))->toContain(PostTag::StealingMoney->value);
});

it('rejects an unknown punk tag filter', function () {
    $this->getJson('/api/posts?tag=999999')->assertStatus(422);
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

it('lets an admin create a post in Draft with taxonomy + punk tags', function () {
    Sanctum::actingAs(User::factory()->admin()->create());

    $response = $this->postJson('/api/admin/posts', [
        'title' => 'Скандал с обществена поръчка',
        'excerpt' => 'Кратко описание',
        'body' => 'Пълен текст на разследването',
        'sphere' => Sphere::Healthcare->value,
        'category' => CorruptionCategory::PublicProcurement->value,
        'severity' => FlagSeverity::High->value,
        'tags' => [PostTag::StealingMoney->value, PostTag::ShadyBusiness->value],
        'sourceUrls' => ['https://ted.europa.eu/notice/1'],
    ])->assertCreated();

    $response->assertJsonPath('status', PostStatus::Draft->value)
        ->assertJsonPath('viewCount', 0)
        ->assertJsonPath('sphere', Sphere::Healthcare->value)
        ->assertJsonPath('category', CorruptionCategory::PublicProcurement->value)
        ->assertJsonPath('severity', FlagSeverity::High->value);

    expect($response->json('tags'))
        ->toBe([PostTag::StealingMoney->value, PostTag::ShadyBusiness->value]);
    expect(Post::count())->toBe(1);
});

it('rejects an unknown punk tag on create', function () {
    Sanctum::actingAs(User::factory()->admin()->create());

    $this->postJson('/api/admin/posts', [
        'title' => 'x',
        'body' => 'y',
        'tags' => [999999],
    ])->assertStatus(422);
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
