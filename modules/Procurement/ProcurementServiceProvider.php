<?php

declare(strict_types=1);

namespace Modules\Procurement;

use App\Shared\Contracts\ProcurementReadPort;
use App\Shared\Contracts\SpendReadPort;
use Illuminate\Support\ServiceProvider;
use Modules\Procurement\Console\Commands\IngestRunCommand;
use Modules\Procurement\Contracts\IngestRecordRepository;
use Modules\Procurement\Contracts\PaymentIngestRepository;
use Modules\Procurement\Contracts\TenderIngestRepository;
use Modules\Procurement\Repositories\EloquentIngestRecordRepository;
use Modules\Procurement\Repositories\EloquentPaymentIngestRepository;
use Modules\Procurement\Repositories\EloquentProcurementReadRepository;
use Modules\Procurement\Repositories\EloquentSpendReadRepository;
use Modules\Procurement\Repositories\EloquentTenderIngestRepository;

/**
 * Procurement bounded context: tenders, authorities, companies, items,
 * price snapshots, ingest. Binds this module's repository interfaces to their
 * Eloquent implementations (backend.md §1).
 */
class ProcurementServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(IngestRecordRepository::class, EloquentIngestRecordRepository::class);
        $this->app->bind(TenderIngestRepository::class, EloquentTenderIngestRepository::class);
        $this->app->bind(PaymentIngestRepository::class, EloquentPaymentIngestRepository::class);
        $this->app->bind(ProcurementReadPort::class, EloquentProcurementReadRepository::class);
        $this->app->bind(SpendReadPort::class, EloquentSpendReadRepository::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                IngestRunCommand::class,
            ]);
        }
    }
}
