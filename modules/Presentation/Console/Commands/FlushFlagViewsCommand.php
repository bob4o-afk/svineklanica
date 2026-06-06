<?php

declare(strict_types=1);

namespace Modules\Presentation\Console\Commands;

use App\Support\Logging\LoggingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use Modules\Detection\Models\Flag;
use Modules\Presentation\Services\FlagViewService;

/**
 * `php artisan flags:flush-views` — fold the hot Redis view deltas into the durable
 * flags.view_count column (backend.md §14). Scheduled every minute (bootstrap/app.php).
 *
 * Snapshots the delta hash first (RENAME) so views recorded mid-flush land in a fresh
 * hash and aren't lost. Idempotent: a leftover snapshot from a crashed run is processed
 * before a new one is taken.
 */
final class FlushFlagViewsCommand extends Command
{
    protected $signature = 'flags:flush-views';

    protected $description = 'Persist Redis view-count deltas into the durable flags.view_count column.';

    public function handle(LoggingService $log): int
    {
        $conn = Redis::connection();

        // Recover a crashed prior flush; otherwise snapshot the live delta hash so new
        // increments accumulate in a fresh DELTA_KEY while we drain the snapshot.
        if (! $conn->exists(FlagViewService::FLUSH_KEY)) {
            if (! $conn->exists(FlagViewService::DELTA_KEY)) {
                $this->info('No view deltas to flush.');

                return self::SUCCESS;
            }
            $conn->rename(FlagViewService::DELTA_KEY, FlagViewService::FLUSH_KEY);
        }

        /** @var array<string, string> $deltas */
        $deltas = $conn->hgetall(FlagViewService::FLUSH_KEY);

        $views = 0;
        foreach ($deltas as $publicId => $count) {
            $delta = (int) $count;
            if ($delta <= 0) {
                continue;
            }
            // increment() is a single bound UPDATE; a flag deleted since the view just
            // matches 0 rows (the view is dropped) — no error.
            Flag::query()->where('public_id', $publicId)->increment('view_count', $delta);
            $views += $delta;
        }

        $conn->del(FlagViewService::FLUSH_KEY);

        $log->info('flag_views_flushed', ['flags' => count($deltas), 'views' => $views]);
        $this->info('Flushed '.$views.' view(s) across '.count($deltas).' flag(s).');

        return self::SUCCESS;
    }
}
