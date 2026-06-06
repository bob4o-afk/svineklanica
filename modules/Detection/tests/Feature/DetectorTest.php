<?php

declare(strict_types=1);

use App\Shared\Enums\CorruptionCategory;
use App\Shared\Enums\FlagSeverity;
use App\Shared\Enums\Sphere;
use Illuminate\Support\Facades\Queue;
use Modules\Detection\Detectors\CancelledTenderDetector;
use Modules\Detection\Detectors\PriceDiscrepancyDetector;
use Modules\Detection\Detectors\SerialWinnerDetector;
use Modules\Detection\Enums\FlagType;
use Modules\Detection\Jobs\RunDetectorJob;
use Modules\Detection\Models\Flag;
use Modules\Procurement\Enums\TenderStatus;
use Modules\Procurement\Models\Company;
use Modules\Procurement\Models\ContractingAuthority;
use Modules\Procurement\Models\PriceSnapshot;
use Modules\Procurement\Models\Tender;
use Modules\Procurement\Models\TenderItem;

/** Create a price snapshot for `productKey` at `price`, on its own tender. */
function snapshot(string $productKey, float $price, ?Sphere $sphere = null): Tender
{
    $tender = Tender::factory()->create(['sphere' => $sphere]);
    $item = TenderItem::factory()->create(['tender_id' => $tender->id]);
    PriceSnapshot::factory()->create([
        'tender_item_id' => $item->id,
        'product_key' => $productKey,
        'description' => 'Лаптоп',
        'price' => $price,
        'currency' => 'BGN',
        'source_url' => 'https://ted.europa.eu/notice/'.$tender->id,
    ]);

    return $tender;
}

it('flags an item priced well above the cluster median', function () {
    // Cluster of three "лаптоп" prices; the 1000 one is the outlier vs median 110.
    snapshot('лаптоп', 100);
    snapshot('лаптоп', 110);
    $overpriced = snapshot('лаптоп', 1000, Sphere::Healthcare);

    $written = app(PriceDiscrepancyDetector::class)->run();

    expect($written)->toBe(1);
    $flag = Flag::where('type', FlagType::PriceDiscrepancy)->sole();
    expect($flag->subject_type)->toBe('tender')
        ->and($flag->subject_id)->toBe($overpriced->id)
        ->and($flag->sphere)->toBe(Sphere::Healthcare)
        ->and($flag->category)->toBe(CorruptionCategory::PublicProcurement)
        ->and($flag->score)->toBe(100)
        ->and($flag->severity)->toBe(FlagSeverity::Critical)
        ->and($flag->source_urls)->not->toBeEmpty();
});

it('ignores a price cluster that is too small to judge', function () {
    snapshot('монитор', 100);
    snapshot('монитор', 5000); // only 2 observations → below MIN_CLUSTER

    expect(app(PriceDiscrepancyDetector::class)->run())->toBe(0);
    expect(Flag::where('type', FlagType::PriceDiscrepancy)->count())->toBe(0);
});

it('flags a serial winner and scores authority concentration', function () {
    $authority = ContractingAuthority::factory()->create();
    $company = Company::factory()->create(['source_url' => 'https://portal.registryagency.bg/x']);
    Tender::factory()->count(3)->create([
        'winner_company_id' => $company->id,
        'contracting_authority_id' => $authority->id,
    ]);

    // A two-win company stays below the threshold.
    $small = Company::factory()->create();
    Tender::factory()->count(2)->create(['winner_company_id' => $small->id]);

    $written = app(SerialWinnerDetector::class)->run();

    expect($written)->toBe(1);
    $flag = Flag::where('type', FlagType::SerialWinner)->sole();
    expect($flag->subject_type)->toBe('company')
        ->and($flag->subject_id)->toBe($company->id)
        ->and($flag->score)->toBe(65) // 3*15 + (3-1)*10
        ->and($flag->evidence['win_count'])->toBe(3);
});

it('flags cancelled and terminated tenders, louder for termination', function () {
    Tender::factory()->cancelled()->create();
    Tender::factory()->create(['status' => TenderStatus::Terminated, 'cancelled_at' => now()]);
    Tender::factory()->create(); // announced — not flagged

    $written = app(CancelledTenderDetector::class)->run();

    expect($written)->toBe(2);
    expect(Flag::where('type', FlagType::Cancelled)->where('score', 70)->count())->toBe(1) // terminated
        ->and(Flag::where('type', FlagType::Cancelled)->where('score', 50)->count())->toBe(1); // cancelled
});

it('is idempotent — re-running replaces, never duplicates', function () {
    snapshot('лаптоп', 100);
    snapshot('лаптоп', 110);
    snapshot('лаптоп', 1000);

    $detector = app(PriceDiscrepancyDetector::class);
    $detector->run();
    $detector->run();

    expect(Flag::where('type', FlagType::PriceDiscrepancy)->count())->toBe(1);
});

it('dispatches a queued job per detector with --queue', function () {
    Queue::fake();

    $this->artisan('detect:run', ['--queue' => true])->assertSuccessful();

    Queue::assertPushed(RunDetectorJob::class, 3);
});
