<?php

declare(strict_types=1);

use App\Shared\Enums\Sphere;
use Modules\Detection\Models\Flag;
use Modules\Procurement\Models\Tender;

/**
 * BFF endpoint: GET /api/insights/corruption-tax?taxes_paid=…  (snake_case contract).
 * total = 100k (flagged @100%) + 300k (clean) = 400k → rate 0.25 → 250 of 1000 лв.
 */
it('serves the score-weighted corruption tax with a readable flag link', function () {
    $flagged = Tender::factory()->create(['value' => 100000, 'sphere' => Sphere::Healthcare]);
    Tender::factory()->create(['value' => 300000]); // unflagged
    $flag = Flag::factory()->create([
        'subject_type' => 'tender',
        'subject_id' => $flagged->id,
        'score' => 100,
    ]);

    $response = $this->getJson('/api/insights/corruption-tax?taxes_paid=1000');

    $response->assertOk()
        ->assertJsonPath('total_spend.amount', 400000)
        ->assertJsonPath('flagged_spend.amount', 100000)
        ->assertJsonPath('user_corruption_amount.amount', 250)
        // snake_case mapping + the readable flag-post link.
        ->assertJsonPath('top_cases.0.flag_public_id', $flag->public_id)
        ->assertJsonPath('top_cases.0.score', 100);

    expect((float) $response->json('corruption_rate'))->toBe(0.25)
        ->and($response->json('top_cases.0.source_url'))->not->toBeEmpty();
});

it('clamps a negative taxes_paid to zero', function () {
    $response = $this->getJson('/api/insights/corruption-tax?taxes_paid=-50');

    $response->assertOk()
        ->assertJsonPath('taxes_paid.amount', 0);
});
