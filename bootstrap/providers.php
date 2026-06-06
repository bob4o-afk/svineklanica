<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use Modules\Detection\DetectionServiceProvider;
use Modules\Identity\IdentityServiceProvider;
use Modules\Notifications\NotificationsServiceProvider;
use Modules\Procurement\ProcurementServiceProvider;
use Modules\Publishing\PublishingServiceProvider;

return [
    AppServiceProvider::class,
    ProcurementServiceProvider::class,
    DetectionServiceProvider::class,
    IdentityServiceProvider::class,
    PublishingServiceProvider::class,
    NotificationsServiceProvider::class,
];
