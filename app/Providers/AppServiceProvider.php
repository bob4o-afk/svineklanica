<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;
use Modules\Procurement\Models\Company;
use Modules\Procurement\Models\ContractingAuthority;
use Modules\Procurement\Models\Tender;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Stable morph aliases for Flag::subject() — decouples the DB from class
        // paths (so a model move doesn't orphan flag rows) and keeps subject_type
        // values short + meaningful.
        Relation::enforceMorphMap([
            'tender' => Tender::class,
            'company' => Company::class,
            'authority' => ContractingAuthority::class,
        ]);
    }
}
