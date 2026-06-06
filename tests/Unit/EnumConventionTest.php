<?php

declare(strict_types=1);

use App\Shared\Contracts\HasLabel;
use Modules\Detection\Enums\FlagSeverity;
use Modules\Detection\Enums\FlagType;
use Modules\Procurement\Enums\TenderStatus;
use Modules\Publishing\Enums\PostStatus;

dataset('enums', [
    'TenderStatus' => [TenderStatus::class, 1000],
    'FlagType' => [FlagType::class, 2000],
    'FlagSeverity' => [FlagSeverity::class, 3000],
    'PostStatus' => [PostStatus::class, 4000],
]);

it('is int-backed, labelled, and lives in its own thousands block (backend.md §9.5)', function (string $enum, int $block) {
    expect((new ReflectionEnum($enum))->getBackingType()?->getName())->toBe('int');

    foreach ($enum::cases() as $case) {
        // In its block (e.g. 2000..2999) and stepping by 10.
        expect($case->value)->toBeGreaterThanOrEqual($block)
            ->and($case->value)->toBeLessThan($block + 1000)
            ->and($case->value % 10)->toBe(0);

        expect($case)->toBeInstanceOf(HasLabel::class)
            ->and($case->label())->toBeString()->not->toBeEmpty();
    }
})->with('enums');

it('resolves Bulgarian labels when the locale is bg', function () {
    app()->setLocale('bg');

    expect(FlagSeverity::Critical->label())->toBe('Критична')
        ->and(TenderStatus::Cancelled->label())->toBe('Прекратена')
        ->and(PostStatus::Published->label())->toBe('Публикувана');
});
