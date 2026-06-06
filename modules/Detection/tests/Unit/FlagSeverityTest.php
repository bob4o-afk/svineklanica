<?php

declare(strict_types=1);

use App\Shared\Enums\FlagSeverity;

it('maps a 0-100 score to the right band', function (int $score, FlagSeverity $expected) {
    expect(FlagSeverity::fromScore($score))->toBe($expected);
})->with([
    'floor' => [0, FlagSeverity::Low],
    'low top' => [39, FlagSeverity::Low],
    'medium floor' => [40, FlagSeverity::Medium],
    'medium top' => [69, FlagSeverity::Medium],
    'high floor' => [70, FlagSeverity::High],
    'high top' => [89, FlagSeverity::High],
    'critical floor' => [90, FlagSeverity::Critical],
    'ceiling' => [100, FlagSeverity::Critical],
]);

it('clamps out-of-range scores into a valid band', function () {
    expect(FlagSeverity::fromScore(-50))->toBe(FlagSeverity::Low)
        ->and(FlagSeverity::fromScore(250))->toBe(FlagSeverity::Critical);
});
