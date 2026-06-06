<?php

declare(strict_types=1);

namespace Modules\Identity\Security\Honeypot;

use Illuminate\Http\Request;

/**
 * The isolated fake-data sandbox (security.md §10). When an attacker trips a
 * honeypot route we answer with believable-but-FAKE procurement data so they
 * keep scraping a dead end while we watch — but the data is generated here in
 * memory and NEVER touches the real DB (a separate "image" of the system that
 * happens to be wired into the same app). It contains zero real or personal
 * data: every name/EIK/amount below is fabricated.
 *
 * The output is DETERMINISTIC per request path: a scraper that re-hits the same
 * decoy sees the same "records", so the honeypot looks like a stable real
 * endpoint rather than obvious noise.
 */
final class FakeDataSandbox
{
    /** Plausible-but-invented contracting authorities (NOT real institutions). */
    private const FAKE_AUTHORITIES = [
        'Община Светлоград',
        'Министерство на вътрешния ред',
        'Дирекция „Регионално развитие" — Долна Митрополия',
        'Агенция за пътна инфраструктура — Северозапад',
        'Областна управа Звездополе',
    ];

    /** Plausible-but-invented bidders (NOT real companies). */
    private const FAKE_COMPANIES = [
        'Стройинвест Прима ЕООД',
        'Глобал Контракт Груп ООД',
        'Ремонти и Пътища АД',
        'Алфа Билд Системс ЕООД',
        'Виктори Консулт ООД',
    ];

    /**
     * Build a believable fake response for the tripped route. Looks like our
     * real list endpoints ({ data, meta }) so a scraper can't tell it apart.
     *
     * @return array<string, mixed>
     */
    public function payloadFor(Request $request): array
    {
        $seed = crc32($request->path());
        $count = 5 + ($seed % 6); // 5–10 fake records

        $records = [];
        for ($i = 0; $i < $count; $i++) {
            $records[] = $this->fakeTender($seed + $i);
        }

        return [
            'data' => $records,
            'meta' => [
                'total' => $count,
                'per_page' => $count,
                'current_page' => 1,
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function fakeTender(int $seed): array
    {
        // A tiny LCG so values are varied but reproducible from the seed.
        $rng = static function (int $s, int $mod): int {
            return (int) (($s * 1103515245 + 12345) & 0x7fffffff) % $mod;
        };

        $authority = self::FAKE_AUTHORITIES[$rng($seed, count(self::FAKE_AUTHORITIES))];
        $company = self::FAKE_COMPANIES[$rng($seed + 7, count(self::FAKE_COMPANIES))];
        $value = 50_000 + $rng($seed, 4_000) * 1_000; // 50k–4M, round-ish

        return [
            // A fabricated public_id — UUID-shaped but flagged as sandbox so we
            // can recognise our own bait if it ever shows up somewhere.
            'public_id' => sprintf('00000000-dead-7%03x-beef-%012x', $seed % 0xfff, $seed),
            'registry_number' => sprintf('%04d-SANDBOX-%05d', 2026, $seed % 100000),
            'authority' => $authority,
            'winner' => $company,
            'value_bgn' => $value,
            'status' => 'awarded',
            'announced_at' => '2026-0'.(1 + $seed % 5).'-1'.($seed % 9).'T09:00:00Z',
        ];
    }
}
