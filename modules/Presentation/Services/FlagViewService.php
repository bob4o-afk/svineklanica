<?php

declare(strict_types=1);

namespace Modules\Presentation\Services;

use App\Support\Logging\LoggingService;
use Illuminate\Support\Facades\Redis;
use Modules\Detection\Models\Flag;
use Throwable;

/**
 * Per-flag view counter (backend.md §14). Redis is the hot counter, deduped by hashed IP
 * (one IP = one view per window); a scheduled flusher (FlushFlagViewsCommand) folds the
 * deltas into flags.view_count. The displayed total is the durable DB count PLUS the
 * not-yet-flushed Redis delta — live, without hitting Postgres on every view.
 *
 * Views are a soft feature: if Redis is unavailable we log it (never silently swallow,
 * backend.md §8) and degrade to the DB count rather than 500 the citizen feed.
 */
final class FlagViewService
{
    /** Redis hash of {public_id => unflushed view delta}. */
    public const DELTA_KEY = 'flag:views:delta';

    /** Snapshot the flusher renames the delta hash to, so views recorded mid-flush aren't lost. */
    public const FLUSH_KEY = 'flag:views:delta:flushing';

    /** One IP counts once per flag per 24h window. */
    private const DEDUPE_TTL = 86400;

    public function __construct(private readonly LoggingService $log) {}

    /** Record one view, deduped by hashed IP. Cheap (SETNX + HINCRBY) — never blocks the response. */
    public function record(string $publicId, ?string $ip): void
    {
        $seenKey = 'flag:views:seen:'.$publicId.':'.$this->hashIp($ip);

        try {
            $conn = Redis::connection();
            // SET … EX … NX → true only the first time this IP sees this flag in the window.
            $isNew = $conn->set($seenKey, '1', 'EX', self::DEDUPE_TTL, 'NX');
            if ($isNew) {
                $conn->hincrby(self::DELTA_KEY, $publicId, 1);
            }
        } catch (Throwable $e) {
            $this->log->warning('flag_view_record_failed', [
                'public_id' => $publicId,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /** Live total for one flag: durable DB count + unflushed Redis delta. */
    public function total(Flag $flag): int
    {
        return (int) $flag->view_count + $this->delta($flag->public_id);
    }

    /**
     * Live totals for a list of flags in ONE Redis round-trip, keyed by public_id.
     *
     * @param  iterable<int, Flag>  $flags
     * @return array<string, int>
     */
    public function totals(iterable $flags): array
    {
        $totals = [];
        $ids = [];
        foreach ($flags as $flag) {
            $totals[$flag->public_id] = (int) $flag->view_count;
            $ids[] = $flag->public_id;
        }

        if ($ids === []) {
            return [];
        }

        foreach ($this->deltas($ids) as $id => $delta) {
            $totals[$id] += $delta;
        }

        return $totals;
    }

    private function delta(string $publicId): int
    {
        try {
            return (int) Redis::connection()->hget(self::DELTA_KEY, $publicId);
        } catch (Throwable $e) {
            $this->log->warning('flag_view_delta_failed', ['message' => $e->getMessage()]);

            return 0;
        }
    }

    /**
     * @param  string[]  $publicIds
     * @return array<string, int>
     */
    private function deltas(array $publicIds): array
    {
        try {
            $values = Redis::connection()->hmget(self::DELTA_KEY, $publicIds);
        } catch (Throwable $e) {
            $this->log->warning('flag_view_deltas_failed', ['message' => $e->getMessage()]);

            return [];
        }

        $out = [];
        foreach (array_values($publicIds) as $i => $id) {
            $out[$id] = (int) ($values[$i] ?? 0);
        }

        return $out;
    }

    /** Never key on a raw IP (privacy — security.md §9); store a short one-way hash. */
    private function hashIp(?string $ip): string
    {
        return substr(hash('sha256', (string) $ip), 0, 32);
    }
}
