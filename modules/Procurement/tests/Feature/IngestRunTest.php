<?php

declare(strict_types=1);

use App\Shared\Enums\CorruptionCategory;
use App\Shared\Enums\Sphere;
use Illuminate\Support\Facades\Queue;
use Modules\Procurement\Actions\IngestSourceAction;
use Modules\Procurement\Enums\TenderStatus;
use Modules\Procurement\Jobs\IngestSourceJob;
use Modules\Procurement\Models\Company;
use Modules\Procurement\Models\ContractingAuthority;
use Modules\Procurement\Models\IngestRecord;
use Modules\Procurement\Models\Payment;
use Modules\Procurement\Models\PriceSnapshot;
use Modules\Procurement\Models\Tender;
use Modules\Procurement\Models\TenderItem;

/**
 * Writes a small NDJSON fixture to a temp file and returns its path.
 * Uses the canonical v2 payload (contract.py): a shared envelope + a typed
 * `tender` block, dispatched by `record_type`.
 */
function writeFixture(string $source): string
{
    $valid1 = json_encode([
        'source' => $source,
        'natural_key' => '2026/S-000001',
        'source_url' => 'https://ted.europa.eu/notice/1',
        'fetched_at' => '2026-06-05T10:00:00Z',
        'schema_version' => 2,
        'payload' => [
            'record_type' => 'tender',
            'category' => 'обществена поръчка',
            'title' => 'Доставка на лаптопи',
            'authority' => ['name' => 'Община Бургас', 'eik' => '000056814'],
            'winner' => ['name' => 'Техно Трейд ЕООД', 'eik' => '201234567'],
            'tender' => [
                'value' => 250000.00,
                'currency' => 'BGN',
                'status' => 'awarded',
                'items' => [['description' => 'Лаптоп', 'quantity' => 50, 'unit_price' => 5000.00]],
            ],
        ],
    ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

    $valid2 = json_encode([
        'source' => $source,
        'natural_key' => '2026/S-000002',
        'source_url' => 'https://ted.europa.eu/notice/2',
        'fetched_at' => '2026-06-05T10:05:00Z',
        'schema_version' => 2,
        'payload' => [
            'record_type' => 'tender',
            'category' => 'обществена поръчка',
            'title' => 'Ремонт на път',
            'authority' => ['name' => 'Община Белица'],
            'tender' => ['status' => 'announced'],
        ],
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

it('tags category + writes a price snapshot per priced item', function () {
    $path = writeFixture('test');

    app(IngestSourceAction::class)->execute('test', $path);

    $tender = Tender::where('natural_key', '2026/S-000001')->firstOrFail();
    // "Община Бургас" matches no sphere keyword → left unset (no guessing).
    expect($tender->sphere)->toBeNull()
        ->and($tender->category)->toBe(CorruptionCategory::PublicProcurement);

    // The one priced item ("Лаптоп" @ 5000) becomes a snapshot; the item-less tender adds none.
    expect(PriceSnapshot::count())->toBe(1);
    $snapshot = PriceSnapshot::firstOrFail();
    expect($snapshot->product_key)->toBe('лаптоп')
        ->and((float) $snapshot->price)->toBe(5000.00);

    unlink($path);
});

it('infers the sphere from the contracting authority name', function () {
    $line = json_encode([
        'source' => 'test',
        'natural_key' => '2026/S-HEALTH',
        'source_url' => 'https://ted.europa.eu/notice/h',
        'fetched_at' => '2026-06-05T10:00:00Z',
        'payload' => [
            'record_type' => 'tender',
            'category' => 'обществена поръчка',
            'title' => 'Доставка на медицинско оборудване',
            'authority' => ['name' => 'МБАЛ „Света Анна" Бургас'],
        ],
    ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

    $path = tempnam(sys_get_temp_dir(), 'ndjson_').'.ndjson';
    file_put_contents($path, $line."\n");

    app(IngestSourceAction::class)->execute('test', $path);

    $tender = Tender::where('natural_key', '2026/S-HEALTH')->firstOrFail();
    expect($tender->sphere)->toBe(Sphere::Healthcare);

    unlink($path);
});

it('dispatches a payment record to the payments table (not tenders)', function () {
    $line = json_encode([
        'source' => 'sebra',
        'natural_key' => 'PAY-0001',
        'source_url' => 'https://minfin.bg/sebra/1',
        'fetched_at' => '2026-06-05T10:00:00Z',
        'schema_version' => 2,
        'payload' => [
            'record_type' => 'payment',
            'category' => 'нерегламентирани плащания',
            'title' => 'Плащане към доставчик',
            'authority' => ['name' => 'Областна дирекция на МВР - Бургас'],
            'winner' => ['name' => 'Доставчик ООД'],
            'payment' => [
                'spender' => 'Областна дирекция на МВР - Бургас',
                'recipient' => 'Доставчик ООД',
                'amount' => 123456.78,
                'currency' => 'BGN',
                'paid_at' => '2026-05-01',
            ],
        ],
    ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

    $path = tempnam(sys_get_temp_dir(), 'ndjson_').'.ndjson';
    file_put_contents($path, $line."\n");

    $summary = app(IngestSourceAction::class)->execute('sebra', $path);

    expect($summary->written)->toBe(1)
        ->and(Payment::count())->toBe(1)
        ->and(Tender::count())->toBe(0); // a payment must NOT become a tender

    $payment = Payment::firstOrFail();
    expect((float) $payment->amount)->toBe(123456.78)
        ->and($payment->category)->toBe(CorruptionCategory::UnregulatedPayment)
        ->and($payment->sphere)->toBe(Sphere::Police) // "МВР" → police
        ->and($payment->spender->name)->toBe('Областна дирекция на МВР - Бургас')
        ->and($payment->recipient->name)->toBe('Доставчик ООД')
        ->and($payment->public_id)->not->toBeEmpty();

    unlink($path);
});

it('routes a non-tender/payment record type to provenance only', function () {
    $line = json_encode([
        'source' => 'gov_jobs',
        'natural_key' => 'JOB-1',
        'source_url' => 'https://iisda.government.bg/competitions/1',
        'fetched_at' => '2026-06-05T10:00:00Z',
        'schema_version' => 2,
        'payload' => [
            'record_type' => 'job',
            'category' => 'конкурси за работа',
            'title' => 'Конкурс за началник отдел',
        ],
    ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

    $path = tempnam(sys_get_temp_dir(), 'ndjson_').'.ndjson';
    file_put_contents($path, $line."\n");

    $summary = app(IngestSourceAction::class)->execute('gov_jobs', $path);

    // Counted as written (provenance landed), but no tender/payment domain row.
    expect($summary->written)->toBe(1)
        ->and(Tender::count())->toBe(0)
        ->and(Payment::count())->toBe(0)
        ->and(IngestRecord::where('natural_key', 'JOB-1')->exists())->toBeTrue();

    unlink($path);
});

it('re-ingest does not duplicate price snapshots', function () {
    $path = writeFixture('test');

    app(IngestSourceAction::class)->execute('test', $path);
    app(IngestSourceAction::class)->execute('test', $path);

    expect(PriceSnapshot::count())->toBe(1)
        ->and(TenderItem::count())->toBe(1);

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

it('with --require-verdict, ingests only AI-evaluated records and drops the rest', function () {
    $path = writeFixture('test');

    // The analyzer evaluated ONLY the first record — write its verdict sidecar.
    $verdictDir = storage_path('ingest/verdicts');
    if (! is_dir($verdictDir)) {
        mkdir($verdictDir, 0777, true);
    }
    $verdictPath = "{$verdictDir}/test.ndjson";
    file_put_contents($verdictPath, json_encode([
        'source' => 'test',
        'natural_key' => '2026/S-000001',
        'source_url' => 'https://ted.europa.eu/notice/1',
        'corruption_score' => 80,
    ], JSON_THROW_ON_ERROR)."\n");

    $summary = app(IngestSourceAction::class)->execute('test', $path, requireVerdict: true);

    // Record 2 is valid but UNevaluated → now dropped too (1 written, 4 skipped of 5 read).
    expect($summary->written)->toBe(1)
        ->and(Tender::count())->toBe(1)
        ->and(Tender::where('natural_key', '2026/S-000001')->exists())->toBeTrue()
        ->and(Tender::where('natural_key', '2026/S-000002')->exists())->toBeFalse();

    expect(implode("\n", $summary->skipReasons))->toContain('not evaluated');

    unlink($path);
    unlink($verdictPath);
});

it('with --require-verdict and no verdict file, ingests nothing', function () {
    $path = writeFixture('noverdict');

    $summary = app(IngestSourceAction::class)->execute('noverdict', $path, requireVerdict: true);

    expect($summary->written)->toBe(0)
        ->and(Tender::count())->toBe(0);

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
