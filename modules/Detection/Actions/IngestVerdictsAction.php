<?php

declare(strict_types=1);

namespace Modules\Detection\Actions;

use App\Shared\Contracts\ProcurementReadPort;
use App\Support\Logging\LoggingService;
use Illuminate\Support\Facades\DB;
use JsonException;
use Modules\Detection\Contracts\FlagRepository;
use Modules\Detection\Models\Flag;
use Modules\Detection\Support\VerdictFlagMapper;

/**
 * Reads the AI analyzer's verdict NDJSON (apps/ai → storage/ingest/verdicts/<source>.ndjson)
 * and turns each verdict into a {@see Flag} attached to the
 * already-ingested tender. This is the seam that makes the AI's corruption assessment
 * visible to the citizen feed/map (it reads precomputed flags, backend.md §11).
 *
 * Idempotent: AI-origin flags for the source's tenders are cleared, then rewritten —
 * re-running never duplicates and never touches the deterministic detectors' flags.
 *
 * @phpstan-type VerdictSummary array{source: string, path: string, read: int, written: int, skipped: int, levels: array<string, int>, skipReasons: array<int, string>}
 */
final class IngestVerdictsAction
{
    private const MAX_STORED_REASONS = 50;

    public function __construct(
        private readonly ProcurementReadPort $procurement,
        private readonly FlagRepository $flags,
        private readonly VerdictFlagMapper $mapper,
        private readonly LoggingService $log,
    ) {}

    /**
     * @param  int  $minScore  only persist verdicts at/above this 0–100 score
     * @return VerdictSummary
     */
    public function execute(string $source, ?string $path = null, int $minScore = 0): array
    {
        $path ??= storage_path("ingest/verdicts/{$source}.ndjson");

        $levels = [];
        $reasons = [];
        $read = 0;
        $skipped = 0;

        if (! is_readable($path)) {
            $this->log->warning('verdict ingest: NDJSON not found', ['source' => $source, 'path' => $path]);

            return [
                'source' => $source, 'path' => $path, 'read' => 0, 'written' => 0,
                'skipped' => 0, 'levels' => [], 'skipReasons' => ["verdicts NDJSON not found at {$path}"],
            ];
        }

        // Resolve the source's tenders once (natural_key → subject handle).
        $subjects = $this->procurement->tenderSubjectsByNaturalKey($source);

        $rows = [];
        $handle = fopen($path, 'rb');

        try {
            $lineNo = 0;
            while (($line = fgets($handle)) !== false) {
                $lineNo++;
                $line = trim($line);
                if ($line === '') {
                    continue;
                }
                $read++;

                $skip = function (string $why) use (&$skipped, &$reasons, $lineNo): void {
                    $skipped++;
                    if (count($reasons) < self::MAX_STORED_REASONS) {
                        $reasons[] = "line {$lineNo}: {$why}";
                    }
                };

                try {
                    /** @var array<string, mixed> $verdict */
                    $verdict = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
                } catch (JsonException $e) {
                    $skip('invalid JSON — '.$e->getMessage());

                    continue;
                }

                $naturalKey = (string) ($verdict['natural_key'] ?? '');
                $subject = $subjects[$naturalKey] ?? null;
                if ($subject === null) {
                    $skip("no ingested tender for natural_key '{$naturalKey}' (ingest the source first)");

                    continue;
                }

                $score = (int) round((float) ($verdict['corruption_score'] ?? 0));
                if ($score < $minScore) {
                    $skip("score {$score} below --min-score {$minScore}");

                    continue;
                }

                $level = (string) ($verdict['level'] ?? '');
                $levels[$level] = ($levels[$level] ?? 0) + 1;

                $rows[] = $this->mapper->toFlagRow($verdict, $subject);
            }
        } finally {
            fclose($handle);
        }

        $tenderIds = array_map(static fn (array $r): int => (int) $r['subject_id'], $rows);

        $written = DB::transaction(function () use ($tenderIds, $rows): int {
            $this->flags->deleteAiFlagsForTenders($tenderIds);

            return $this->flags->createMany($rows);
        });

        $this->log->info('verdict ingest: run complete', [
            'source' => $source, 'read' => $read, 'written' => $written, 'skipped' => $skipped, 'path' => $path,
        ]);

        return [
            'source' => $source, 'path' => $path, 'read' => $read, 'written' => $written,
            'skipped' => $skipped, 'levels' => $levels, 'skipReasons' => $reasons,
        ];
    }
}
