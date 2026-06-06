<?php

declare(strict_types=1);

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Modules\Detection\Enums\ApprovalStatus;
use Modules\Detection\Models\Flag;

beforeEach(function () {
    $this->withHeader('Origin', 'http://localhost');
});

it('lists only approved flag-posts publicly', function () {
    Flag::factory()->count(2)->create(['status' => ApprovalStatus::Approved, 'published_at' => now()]);
    Flag::factory()->create(['status' => ApprovalStatus::Pending]);

    $response = $this->getJson('/api/flag-posts')->assertOk();

    expect($response->json('data'))->toHaveCount(2);
    expect($response->json('data.0.public_id'))->not->toBeEmpty();
    expect($response->json('data.0.type'))->toBeString();
});

it('returns snake_case admin user on admin login', function () {
    User::factory()->admin()->create([
        'email' => 'admin@test.com',
        'password' => 'secret-pass-1',
    ]);

    $this->postJson('/api/admin/login', [
        'email' => 'admin@test.com',
        'password' => 'secret-pass-1',
    ])
        ->assertOk()
        ->assertJsonPath('public_id', fn ($v) => is_string($v) && $v !== '')
        ->assertJsonPath('role', 'admin')
        ->assertJsonMissingPath('publicId');
});

it('lets an admin approve a pending flag-post', function () {
    Sanctum::actingAs(User::factory()->admin()->create());
    $flag = Flag::factory()->create(['status' => ApprovalStatus::Pending]);

    $this->postJson("/api/admin/flag-posts/{$flag->public_id}/approve", [
        'title' => 'Публикувано заглавие',
        'explanation_bg' => 'Редакторско обяснение',
        'tags' => ['dodgy_deal'],
    ])->assertOk()->assertJsonPath('status', 'approved');

    expect($flag->fresh()->status)->toBe(ApprovalStatus::Approved);
});
