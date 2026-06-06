<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Spatie\LaravelData\Support\TypeScriptTransformer\DataTypeScriptTransformer;
use Spatie\TypeScriptTransformer\Collectors\DefaultCollector;
use Spatie\TypeScriptTransformer\Collectors\EnumCollector;
use Spatie\TypeScriptTransformer\Transformers\EnumTransformer;
use Spatie\TypeScriptTransformer\Writers\TypeDefinitionWriter;

// Backend → frontend type sync (backend.md §9). `composer sync:api-types`
// writes the generated types into the web client. Discovery covers both the
// root app and every module.
return [
    'auto_discover_types' => [
        app_path(),
        base_path('modules'),
    ],

    'collectors' => [
        DefaultCollector::class,
        EnumCollector::class,
    ],

    'transformers' => [
        // laravel-data DTOs (our Data classes) → TS interfaces.
        DataTypeScriptTransformer::class,
        // int-backed enums (TenderStatus, FlagType, …) → TS.
        EnumTransformer::class,
    ],

    'default_type_replacements' => [
        DateTime::class => 'string',
        Carbon\Carbon::class => 'string',
        CarbonImmutable::class => 'string',
    ],

    // Single generated file consumed by apps/web.
    'output_file' => base_path('apps/web/src/types/generated.d.ts'),

    'writer' => TypeDefinitionWriter::class,

    'formatter' => null,

    'transform_to_native_enums' => true,
];
