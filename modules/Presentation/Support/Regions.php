<?php

declare(strict_types=1);

namespace Modules\Presentation\Support;

/**
 * NUTS3 (oblast) code → Bulgarian name, the backend mirror of the frontend's
 * `lib/regions.ts`. Codes match `properties.NUTS_ID` in the committed
 * `public/geo/bg-provinces.geojson` so the region map joins cleanly. A
 * ContractingAuthority stores its NUTS3 code in `region`.
 */
final class Regions
{
    /** @var array<string, string> */
    public const NAMES = [
        'BG311' => 'Видин',
        'BG312' => 'Монтана',
        'BG313' => 'Враца',
        'BG314' => 'Плевен',
        'BG315' => 'Ловеч',
        'BG321' => 'Велико Търново',
        'BG322' => 'Габрово',
        'BG323' => 'Русе',
        'BG324' => 'Разград',
        'BG325' => 'Силистра',
        'BG331' => 'Варна',
        'BG332' => 'Добрич',
        'BG333' => 'Шумен',
        'BG334' => 'Търговище',
        'BG341' => 'Бургас',
        'BG342' => 'Сливен',
        'BG343' => 'Ямбол',
        'BG344' => 'Стара Загора',
        'BG411' => 'София (град)',
        'BG412' => 'София (област)',
        'BG413' => 'Благоевград',
        'BG414' => 'Перник',
        'BG415' => 'Кюстендил',
        'BG421' => 'Пловдив',
        'BG422' => 'Пазарджик',
        'BG423' => 'Смолян',
        'BG424' => 'Хасково',
        'BG425' => 'Кърджали',
    ];

    public static function name(string $code): string
    {
        return self::NAMES[$code] ?? $code;
    }
}
