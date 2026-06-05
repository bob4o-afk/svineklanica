<?php

use Spatie\LaravelTypeScriptTransformer\Collectors\DefaultCollector;
use Spatie\TypeScriptTransformer\Transformers\SpatieStateTransformer;
use Spatie\TypeScriptTransformer\Transformers\EnumTransformer;
use Spatie\LaravelData\Support\TypeScriptTransformer\DataTypeScriptTransformer;

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
    ],

    'transformers' => [
        DataTypeScriptTransformer::class,
        SpatieStateTransformer::class,
        EnumTransformer::class,
    ],

    'default_type_replacements' => [
        DateTime::class => 'string',
        Carbon\Carbon::class => 'string',
        Carbon\CarbonImmutable::class => 'string',
    ],

    // Single generated file consumed by apps/web.
    'output_file' => base_path('apps/web/src/types/generated.d.ts'),

    'writers' => [
        Spatie\TypeScriptTransformer\Writers\TypeDefinitionWriter::class,
    ],

    'formatter' => null,

    'transform_to_native_enums' => true,
];
