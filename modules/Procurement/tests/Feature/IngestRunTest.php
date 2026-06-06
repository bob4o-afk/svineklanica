<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Queue;
use Modules\Procurement\Actions\IngestSourceAction;
use Modules\Procurement\Enums\TenderStatus;
use Modules\Procurement\Jobs\IngestSourceJob;
use Modules\Procurement\Models\Company;
use Modules\Procurement\Models\ContractingAuthority;
use Modules\Procurement\Models\Tender;
use Modules\Procurement\Models\TenderItem;

/** Writes a small NDJSON fixture to a temp file and returns its path. */
function writeFixture(string $source): string
{
    $valid1 = json_encode([
        'source' => $source,
        'natural_key' => '2026/S-000001',
        'source_url' => 'https://ted.europa.eu/notice/1',
        'fetched_at' => '2026-06-05T10:00:00Z',
        'schema_version' => 1,
        'payload' => [
            'title' => 'Доставка на лаптопи',
            'value' => 250000.00,
            'currency' => 'BGN',
            'status' => 'awarded',
            'authority' => ['name' => 'Община Бургас', 'eik' => '000056814'],
            'winner' => ['name' => 'Техно Трейд ЕООД', 'eik' => '201234567'],
            'items' => [['description' => 'Лаптоп', 'quantity' => 50, 'unit_price' => 5000.00]],
        ],
    ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

    $valid2 = json_encode([
        'source' => $source,
        'natural_key' => '2026/S-000002',
        'source_url' => 'https://ted.europa.eu/notice/2',
        'fetched_at' => '2026-06-05T10:05:00Z',
        'schema_version' => 1,
        'payload' => ['title' => 'Ремонт на път', 'status' => 'announced', 'authority' => ['name' => 'Община Белица']],
    ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

    $missingKeys = json_encode(['source' => $source, 'natural_key' => 'x'], JSON_THROW_ON_ERROR);
    $mismatch = json_encode([
        'source' => 'other', 'natural_key' => 'y', 'source_url' => 'https://x', 'fetched_at' => '2026-06-05T10:00:00Z', 'payload' => [],
    ], JSON_THROW_ON_ERROR);

    $lines = [$valid1, $valid2, '{bad json}', $missingKeys, $mismatch];

    $path = tempnam(sys_get_temp_dir(), 'ndjson_').'.ndjson';
    file_put_contents($path, implode("\n", $lines)."\n");

    return $path;
}

it('ingests an NDJSON file into the domain tables', function () {
    $path = writeFixture('test');

    $summary = app(IngestSourceAction::class)->execute('test', $path);

    expect($summary->read)->toBe(5)
        ->and($summary->written)->toBe(2)
        ->and($summary->skipped)->toBe(3);

    expect(Tender::count())->toBe(2)
        ->and(ContractingAuthority::count())->toBe(2)
        ->and(Company::count())->toBe(1)
        ->and(TenderItem::count())->toBe(1);

    $tender = Tender::where('natural_key', '2026/S-000001')->firstOrFail();
    expect($tender->status)->toBe(TenderStatus::Awarded)
        ->and((float) $tender->value)->toBe(250000.00)
        ->and($tender->authority->name)->toBe('Община Бургас')
        ->and($tender->winner->name)->toBe('Техно Трейд ЕООД')
        ->and($tender->public_id)->not->toBeEmpty();

    unlink($path);
});

it('is idempotent — re-running does not duplicate', function () {
    $path = writeFixture('test');

    app(IngestSourceAction::class)->execute('test', $path);
    app(IngestSourceAction::class)->execute('test', $path);

    expect(Tender::count())->toBe(2)
        ->and(ContractingAuthority::count())->toBe(2)
        ->and(Company::count())->toBe(1)
        ->and(TenderItem::count())->toBe(1);

    unlink($path);
});

it('skips malformed / mismatched rows and reports why', function () {
    $path = writeFixture('test');

    $summary = app(IngestSourceAction::class)->execute('test', $path);

    expect($summary->skipReasons)->toHaveCount(3);
    $joined = implode("\n", $summary->skipReasons);
    expect($joined)->toContain('invalid JSON')
        ->and($joined)->toContain('missing keys')
        ->and($joined)->toContain('source mismatch');

    unlink($path);
});

it('returns an empty summary when the NDJSON is missing', function () {
    $summary = app(IngestSourceAction::class)->execute('nope', '/tmp/does-not-exist.ndjson');

    expect($summary->read)->toBe(0)
        ->and($summary->written)->toBe(0)
        ->and(Tender::count())->toBe(0);
});

it('dispatches the async job with --queue instead of running inline', function () {
    Queue::fake();

    $this->artisan('ingest:run', ['--source' => 'test', '--queue' => true])
        ->assertSuccessful();

    Queue::assertPushed(IngestSourceJob::class);
    expect(Tender::count())->toBe(0); // nothing ran inline
});
