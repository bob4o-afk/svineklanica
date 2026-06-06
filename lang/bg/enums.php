<?php

declare(strict_types=1);

// Bulgarian-first enum labels (backend.md §10). Keys mirror the enum case names
// (snake_case) used by each enum's label() method.
return [
    'tender_status' => [
        'announced' => 'Обявена',
        'open' => 'Отворена',
        'awarded' => 'Възложена',
        'cancelled' => 'Прекратена',
        'terminated' => 'Анулирана',
    ],
    'flag_type' => [
        'price_discrepancy' => 'Ценова разлика',
        'tailored_spec' => 'Нагласена спецификация',
        'serial_winner' => 'Сериен победител',
        'cancelled' => 'Обявена и прекратена',
        'implausible_scope' => 'Неправдоподобен обхват',
        'delayed_payment' => 'Забавено плащане',
        'doc_clone' => 'Копирана документация',
    ],
    'flag_severity' => [
        'low' => 'Ниска',
        'medium' => 'Средна',
        'high' => 'Висока',
        'critical' => 'Критична',
    ],
    'post_status' => [
        'draft' => 'Чернова',
        'published' => 'Публикувана',
        'archived' => 'Архивирана',
    ],
];
