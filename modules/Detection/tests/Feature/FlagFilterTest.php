<?php

declare(strict_types=1);

use App\Shared\Enums\CorruptionCategory;
use App\Shared\Enums\FlagSeverity;
use App\Shared\Enums\Sphere;
use Modules\Detection\Models\Flag;

it('publicly lists flags, most suspicious first', function () {
    Flag::factory()->create(['score' => 20, 'severity' => FlagSeverity::Low]);
    Flag::factory()->create(['score' => 95, 'severity' => FlagSeverity::Critical]);

    $response = $this->getJson('/api/flags')->assertOk();

    expect($response->json('data'))->toHaveCount(2);
    // Ordered by score desc → the 95 comes first.
    expect($response->json('data.0.score'))->toBe(95);
});

it('filters by sphere, category and severity', function () {
    $match = Flag::factory()->create([
        'sphere' => Sphere::Healthcare,
        'category' => CorruptionCategory::PublicProcurement,
        'score' => 88,
        'severity' => FlagSeverity::High,
    ]);
    Flag::factory()->create([
        'sphere' => Sphere::Police,
        'category' => CorruptionCategory::UnregulatedPayment,
        'score' => 10,
        'severity' => FlagSeverity::Low,
    ]);

    $response = $this->getJson('/api/flags?'.http_build_query([
        'sphere' => Sphere::Healthcare->value,
        'category' => CorruptionCategory::PublicProcurement->value,
        'severity' => FlagSeverity::High->value,
    ]))->assertOk();

    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.publicId'))->toBe($match->public_id);
});

it('filters by a minimum score', function () {
    Flag::factory()->create(['score' => 30]);
    Flag::factory()->create(['score' => 80]);

    $response = $this->getJson('/api/flags?minScore=70')->assertOk();

    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.score'))->toBe(80);
});

it('rejects an out-of-range score filter', function () {
    $this->getJson('/api/flags?minScore=500')->assertStatus(422);
});

it('rejects an unknown sphere value', function () {
    $this->getJson('/api/flags?sphere=999999')->assertStatus(422);
});

it('shows a single flag by public id and 404s an unknown one', function () {
    $flag = Flag::factory()->create();

    $this->getJson("/api/flags/{$flag->public_id}")
        ->assertOk()
        ->assertJsonPath('publicId', $flag->public_id);

    $this->getJson('/api/flags/does-not-exist')->assertNotFound();
});
