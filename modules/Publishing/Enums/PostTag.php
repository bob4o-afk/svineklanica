<?php

declare(strict_types=1);

namespace Modules\Publishing\Enums;

use App\Shared\Contracts\HasLabel;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * Punk tags / badges (CLAUDE.md §1.0.1) — short, savage, plain-Bulgarian labels
 * an admin assigns when publishing a post. A curated EDITORIAL layer ON TOP of
 * the computed sphere/category/severity: the badge is the roast, the category is
 * the evidence. A post carries zero-or-more. Extend as cases demand — keep them
 * punchy, factual, never libellous. Block 7000, cases step by 10 (backend.md §9.5).
 */
#[TypeScript]
enum PostTag: int implements HasLabel
{
    case StealingMoney = 7000;   // крадене на пари
    case DodgyDeals = 7010;      // кофти сделки
    case ShadyBusiness = 7020;   // шуши-муши (the catch-all "something stinks")

    /**
     * Normalize validated scalar input (ints / numeric strings from a DTO) into
     * PostTag instances, ready for the model's AsEnumCollection cast.
     *
     * @param  array<int, int|string|self>|null  $values
     * @return array<int, self>
     */
    public static function collectFrom(?array $values): array
    {
        return array_map(
            static fn (int|string|self $value): self => $value instanceof self ? $value : self::from((int) $value),
            $values ?? [],
        );
    }

    public function label(): string
    {
        return match ($this) {
            self::StealingMoney => __('enums.post_tag.stealing_money'),
            self::DodgyDeals => __('enums.post_tag.dodgy_deals'),
            self::ShadyBusiness => __('enums.post_tag.shady_business'),
        };
    }
}
