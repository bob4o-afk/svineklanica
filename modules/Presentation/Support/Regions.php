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

    /**
     * Free-text (bare city / oblast names, as the ingest carries them) → NUTS3 code. Resolves the
     * София grad/oblast ambiguity to grad, and a few common spellings. Checked BEFORE the
     * derived-from-NAMES lookup so it wins on overlaps.
     *
     * @var array<string, string>
     */
    private const ALIASES = [
        'софия' => 'BG411',
        'софия град' => 'BG411',
        'софия-град' => 'BG411',
        'столична' => 'BG411',
        'столична община' => 'BG411',
        'софийска' => 'BG412',
        'софийска област' => 'BG412',
        'велико търново' => 'BG321',
        'търговище' => 'BG334',
        'стара загора' => 'BG344',
    ];

    /**
     * Reverse lookup: a Bulgarian region/oblast NAME (or an already-valid NUTS code) → its NUTS3
     * code, or null if unrecognised. Used to normalise the free-text `region` the ingest stores
     * into the canonical code the map + aggregates join on (data-sources.md geo normalisation).
     */
    public static function code(?string $name): ?string
    {
        if ($name === null) {
            return null;
        }

        $raw = trim($name);
        if ($raw === '') {
            return null;
        }

        // Already a NUTS code (BG311, bg411, …)?
        $upper = strtoupper($raw);
        if (isset(self::NAMES[$upper])) {
            return $upper;
        }

        $key = self::normalize($raw);

        if (isset(self::ALIASES[$key])) {
            return self::ALIASES[$key];
        }

        /** @var array<string, string>|null $byName */
        static $byName = null;
        if ($byName === null) {
            $byName = [];
            foreach (self::NAMES as $code => $label) {
                $byName[self::normalize($label)] = $code;
            }
        }

        return $byName[$key] ?? null;
    }

    /** Lowercase, trim, and drop a parenthetical qualifier: "София (град)" → "софия". */
    private static function normalize(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = preg_replace('/\s*\([^)]*\)\s*/u', '', $value) ?? $value;

        return trim($value);
    }
}
