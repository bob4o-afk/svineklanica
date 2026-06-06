<?php

declare(strict_types=1);

use App\Shared\Enums\Sphere;
use Carbon\Carbon;
use Modules\Detection\Models\Flag;
use Modules\Detection\Services\CorruptionTaxCalculator;
use Modules\Procurement\Models\Payment;
use Modules\Procurement\Models\Tender;

/**
 * total spend  = 100k (tA) + 300k (tB) + 100k (payment)        = 500k
 * flagged spend is SCORE-WEIGHTED:
 *   tA      100k × max(score 100, 30)/100 = 100k × 1.0          = 100,000
 *   payment 100k × score 50/100           = 100k × 0.5          =  50,000
 *                                                       flagged = 150,000  → rate 0.30
 */
function seedSpend(): void
{
    $tA = Tender::factory()->create(['value' => 100000, 'sphere' => Sphere::Healthcare]);
    Tender::factory()->create(['value' => 300000, 'sphere' => Sphere::Police]); // unflagged

    $payment = Payment::create([
        'source' => 'sebra',
        'natural_key' => 'PAY-CALC-1',
        'source_url' => 'https://minfin.bg/sebra/1',
        'fetched_at' => Carbon::parse('2026-06-05T10:00:00Z'),
        'title' => 'Плащане към доставчик',
        'sphere' => Sphere::Healthcare->value,
        'amount' => 100000,
        'currency' => 'BGN',
        'paid_at' => '2026-05-01',
    ]);

    // tA carries two flags — the MAX score (100) is the weight, not the 30.
    Flag::factory()->create(['subject_type' => 'tender', 'subject_id' => $tA->id, 'score' => 100]);
    Flag::factory()->create(['subject_type' => 'tender', 'subject_id' => $tA->id, 'score' => 30]);
    Flag::factory()->create(['subject_type' => 'payment', 'subject_id' => $payment->id, 'score' => 50]);
}

it('computes the score-weighted corruption rate and the user projection', function () {
    seedSpend();

    $result = app(CorruptionTaxCalculator::class)->calculate(1000.0);

    expect($result->totalSpend)->toBe(500000.0)
        ->and($result->flaggedSpend)->toBe(150000.0)   // 100k×1.0 + 100k×0.5
        ->and($result->corruptionRate)->toBe(0.3)
        ->and($result->userCorruptionAmount)->toBe(300.0)
        ->and($result->topCases)->toHaveCount(2)
        // Biggest contributor first: tA (100k × 1.0) ahead of payment (100k × 0.5).
        ->and($result->topCases[0]->score)->toBe(100)
        ->and($result->topCases[0]->userShare)->toBe(200.0)  // 1000 × 100k × 1.0 / 500k
        ->and($result->topCases[0]->sourceUrl)->not->toBeEmpty()
        ->and($result->topCases[1]->score)->toBe(50)
        ->and($result->topCases[1]->userShare)->toBe(100.0); // 1000 × 100k × 0.5 / 500k
});

it('returns 0 when there is no spend (no divide-by-zero)', function () {
    $result = app(CorruptionTaxCalculator::class)->calculate(1000.0);

    expect($result->totalSpend)->toBe(0.0)
        ->and($result->corruptionRate)->toBe(0.0)
        ->and($result->userCorruptionAmount)->toBe(0.0);
});

it('serves the calculator over the guarded API', function () {
    seedSpend();

    $response = $this->postJson('/api/calculator/corruption-tax', ['taxesPaid' => 1000]);

    // Cast: JSON drops trailing .0, so whole-number floats decode as ints.
    $response->assertOk();
    expect((float) $response->json('corruptionRate'))->toBe(0.3)
        ->and((float) $response->json('userCorruptionAmount'))->toBe(300.0)
        ->and((float) $response->json('totalSpend'))->toBe(500000.0);
});

it('rejects a missing / invalid taxesPaid input', function () {
    $this->postJson('/api/calculator/corruption-tax', ['taxesPaid' => -5])
        ->assertStatus(422);
});
