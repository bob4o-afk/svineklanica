<?php

declare(strict_types=1);

namespace Modules\Presentation\Data;

use Illuminate\Http\Request;
use Modules\Detection\Enums\FlagType;
use Modules\Presentation\Support\ContractEnums;
use Modules\Presentation\Support\SectorResolver;

/**
 * Parsed filters for the public flag feed. The frontend encodes multi-value filters
 * as repeated bare keys (`type=a&type=b`) — which PHP's superglobals collapse to the
 * last value — so we parse them out of the raw query string ourselves, accepting both
 * `type=` and `type[]=` forms. All values are validated against the known enums/sectors
 * (unknown values are dropped), so the feed is abuse-safe (security.md §5).
 */
final class FlagFeedFilterData
{
    /**
     * @param  int[]  $typeValues  FlagType backing ints
     * @param  string[]  $sectors  ProcurementSector strings
     * @param  string[]  $severities  FlagSeverity backing ints (as int[])
     */
    public function __construct(
        public array $typeValues,
        public array $sectors,
        public array $severities,
        public ?string $region,
        public ?string $q,
        public string $sort,
        public int $page,
        public int $perPage,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $raw = (string) $request->server('QUERY_STRING', '');

        $types = array_values(array_filter(array_map(
            static fn (string $v): ?FlagType => ContractEnums::flagTypeFrom($v),
            self::multi($raw, 'type'),
        )));
        $typeValues = array_map(static fn (FlagType $t): int => $t->value, $types);

        $severities = array_values(array_filter(array_map(
            static fn (string $v): ?int => ContractEnums::severityFrom($v)?->value,
            self::multi($raw, 'severity'),
        )));

        $sectors = array_values(array_filter(
            self::multi($raw, 'category'),
            static fn (string $v): bool => SectorResolver::isValid($v),
        ));

        $region = self::str($request->query('region'));
        $q = self::str($request->query('q'));
        $sort = $request->query('sort') === 'severity' ? 'severity' : 'newest';

        $page = max(1, (int) $request->query('page', '1'));
        $perPage = (int) $request->query('per_page', '6');
        $perPage = max(1, min(50, $perPage));

        return new self(
            typeValues: $typeValues,
            sectors: $sectors,
            severities: $severities,
            region: $region,
            q: $q,
            sort: $sort,
            page: $page,
            perPage: $perPage,
        );
    }

    /**
     * All decoded values for a repeated query key (`k=v` and `k[]=v` both match).
     *
     * @return string[]
     */
    private static function multi(string $rawQuery, string $key): array
    {
        if ($rawQuery === '') {
            return [];
        }

        $out = [];
        foreach (explode('&', $rawQuery) as $pair) {
            if ($pair === '') {
                continue;
            }
            [$k, $v] = array_pad(explode('=', $pair, 2), 2, '');
            $k = urldecode($k);
            if ($k === $key || $k === $key.'[]') {
                $value = urldecode(str_replace('+', ' ', $v));
                if ($value !== '') {
                    $out[] = $value;
                }
            }
        }

        return $out;
    }

    private static function str(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }
        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
