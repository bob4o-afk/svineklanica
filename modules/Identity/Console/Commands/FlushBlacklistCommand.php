<?php

declare(strict_types=1);

namespace Modules\Identity\Console\Commands;

use Illuminate\Console\Command;
use Modules\Identity\Security\Blacklist\BlacklistService;

/**
 * `php artisan security:flush-blacklist` — lift EVERY abuse ban at once
 * (security.md §3). The blacklist is cache-backed and keyed by salted hashes, so
 * there's no per-caller list to walk by hand; this clears all tracked entries
 * across every signal type (ip, device, client, headerfp) in one shot.
 *
 * Destructive and prod-facing, so it confirms before acting unless `--force` is
 * passed (use --force for unattended runs on the VM). Whitelisted operators were
 * never recorded as banned, so they're unaffected.
 */
final class FlushBlacklistCommand extends Command
{
    protected $signature = 'security:flush-blacklist {--force : Skip the confirmation prompt}';

    protected $description = 'Remove all abuse blacklist entries (every signal type) in one shot.';

    public function handle(BlacklistService $blacklist): int
    {
        if (! $this->option('force') && ! $this->confirm('Remove ALL blacklist entries? This lets every currently-banned caller back in.')) {
            $this->info('Aborted — no entries removed.');

            return self::SUCCESS;
        }

        $removed = $blacklist->flush();

        $this->info('Cleared '.$removed.' blacklist entr'.($removed === 1 ? 'y' : 'ies').'.');

        return self::SUCCESS;
    }
}
