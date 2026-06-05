<?php

declare(strict_types=1);

namespace Modules\Procurement;

use Illuminate\Support\ServiceProvider;

/**
 * Procurement bounded context: tenders, authorities, companies, items,
 * price snapshots, ingest. Binds this module's repository interfaces to their
 * Eloquent implementations (backend.md §1). Bindings are added as the
 * repositories land.
 */
class ProcurementServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        //
    }
}
