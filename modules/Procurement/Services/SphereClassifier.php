<?php

declare(strict_types=1);

namespace Modules\Procurement\Services;

use App\Shared\Enums\CorruptionCategory;
use App\Shared\Enums\Sphere;

/**
 * Tags an ingested record with its Sphere → Category (CLAUDE.md §1.0, data-sources.md
 * §3) from the contracting authority name, CPV code, and source. Deterministic and
 * conservative: when the sphere can't be inferred it returns null rather than guessing
 * (no fabricated taxonomy — §0 of data-sources). The category for a tender is always
 * `обществена поръчка`; a payments source (СЕБРА) maps to `нерегламентирани плащания`.
 */
final class SphereClassifier
{
    public function classify(?string $authorityName, ?string $cpvCode, string $source): TenderClassification
    {
        return new TenderClassification(
            sphere: $this->sphere($authorityName, $cpvCode),
            category: $this->category($source),
        );
    }

    /**
     * Resolve sphere/category, trusting the Bulgarian strings the scraper already
     * computed (contract.py v2 carries `sphere`/`category` as BG strings, spheres.py)
     * and only re-deriving when they're absent or don't map to a known enum case.
     * "Agree once" — Laravel maps the string → its enum, it doesn't re-classify.
     */
    public function resolve(
        ?string $sphereBg,
        ?string $categoryBg,
        ?string $authorityName,
        ?string $cpvCode,
        string $source,
    ): TenderClassification {
        return new TenderClassification(
            sphere: $this->sphereFromBg($sphereBg) ?? $this->sphere($authorityName, $cpvCode),
            category: $this->categoryFromBg($categoryBg) ?? $this->category($source),
        );
    }

    private function sphereFromBg(?string $bg): ?Sphere
    {
        return match ($bg) {
            'съдебна система' => Sphere::Judiciary,
            'здравеопазване' => Sphere::Healthcare,
            'полиция' => Sphere::Police,
            'образование' => Sphere::Education,
            // Python may emit spheres we don't model yet (правителство, пътно
            // строителство…) — fall through to derivation / unset rather than guess.
            default => null,
        };
    }

    private function categoryFromBg(?string $bg): ?CorruptionCategory
    {
        return match ($bg) {
            'обществена поръчка' => CorruptionCategory::PublicProcurement,
            'нерегламентирани плащания' => CorruptionCategory::UnregulatedPayment,
            default => null,
        };
    }

    private function sphere(?string $authorityName, ?string $cpvCode): ?Sphere
    {
        // Primary signal: Bulgarian keywords in the authority name (accent-free, lower-cased).
        if ($authorityName !== null && $authorityName !== '') {
            $haystack = mb_strtolower($authorityName, 'UTF-8');
            foreach ($this->nameRules() as [$sphere, $keywords]) {
                foreach ($keywords as $keyword) {
                    if (str_contains($haystack, $keyword)) {
                        return $sphere;
                    }
                }
            }
        }

        // Weaker fallback: the CPV division (first two digits).
        if ($cpvCode !== null && strlen($cpvCode) >= 2) {
            return $this->cpvRules()[substr($cpvCode, 0, 2)] ?? null;
        }

        return null; // unknown — left unset rather than guessed
    }

    private function category(string $source): CorruptionCategory
    {
        return str_contains(mb_strtolower($source, 'UTF-8'), 'sebra')
            ? CorruptionCategory::UnregulatedPayment
            : CorruptionCategory::PublicProcurement;
    }

    /**
     * Authority-name keyword rules, first match wins.
     *
     * @return array<int, array{0: Sphere, 1: array<int, string>}>
     */
    private function nameRules(): array
    {
        return [
            [Sphere::Healthcare, ['болниц', 'здрав', 'нзок', 'мбал', 'диспансер', 'медицин', 'поликлиник', 'аптек', 'център за спешна']],
            [Sphere::Police, ['мвр', 'полиц', 'вътрешни работи', 'жандармер', 'гранична', 'пожарна', 'дгп']],
            [Sphere::Judiciary, ['съд', 'прокуратур', 'всс', 'правосъд', 'следствен', 'съдебн']],
            [Sphere::Education, ['училищ', 'университет', 'образоват', 'руо', 'детска градина', 'гимназия', 'мон', 'просвет']],
        ];
    }

    /**
     * CPV division (first two digits) → sphere: 33 medical, 85 health services,
     * 35 security/police, 80 education.
     *
     * @return array<string, Sphere>
     */
    private function cpvRules(): array
    {
        return [
            '33' => Sphere::Healthcare,
            '85' => Sphere::Healthcare,
            '35' => Sphere::Police,
            '80' => Sphere::Education,
        ];
    }
}
