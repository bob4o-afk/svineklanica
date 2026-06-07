<?php

declare(strict_types=1);

namespace Modules\Procurement\Actions;

use App\Support\Google\GoogleAiClient;
use App\Support\Logging\LoggingService;
use Illuminate\Database\Eloquent\Model;
use Modules\Procurement\Data\EmbedSummary;
use Modules\Procurement\Models\Company;
use Modules\Procurement\Models\ContractingAuthority;
use Modules\Procurement\Models\Tender;
use Modules\Procurement\Support\SearchableText;

/**
 * Vectorize the DB (CLAUDE.md §3): embed each entity's searchable text with
 * Google's embedding model and store it in its pgvector column, so the search box
 * can return semantically close records. Idempotent — only rows whose embedding
 * IS NULL are processed, so re-running tops up new rows (backend.md §12). Heavy +
 * external, so it runs out-of-band (CLI or the queued {@see EmbedRecordsJob},
 * backend.md §3), batching texts into single API round-trips.
 */
final class EmbedRecordsAction
{
    /** Texts per Google round-trip — keep modest so a partial failure costs little. */
    private const BATCH = 50;

    private const MAX_STORED_REASONS = 50;

    public function __construct(
        private readonly GoogleAiClient $google,
        private readonly LoggingService $log,
    ) {}

    public function execute(string $type = 'all', ?int $limit = null): EmbedSummary
    {
        return match ($type) {
            'tenders' => $this->embedTenders($limit),
            'companies' => $this->embedCompanies($limit),
            'authorities' => $this->embedAuthorities($limit),
            'all' => $this->embedAll($limit),
            default => new EmbedSummary(
                $type,
                skipReasons: ["Unknown type '{$type}' (use tenders|companies|authorities|all)."],
            ),
        };
    }

    private function embedTenders(?int $limit): EmbedSummary
    {
        return $this->embed(
            Tender::class,
            ['authority'],
            'description_embedding',
            static fn (Tender $t): string => SearchableText::forTender($t),
            'tenders',
            $limit,
        );
    }

    private function embedCompanies(?int $limit): EmbedSummary
    {
        return $this->embed(
            Company::class,
            [],
            'name_embedding',
            static fn (Company $c): string => SearchableText::forCompany($c),
            'companies',
            $limit,
        );
    }

    private function embedAuthorities(?int $limit): EmbedSummary
    {
        return $this->embed(
            ContractingAuthority::class,
            [],
            'name_embedding',
            static fn (ContractingAuthority $a): string => SearchableText::forAuthority($a),
            'authorities',
            $limit,
        );
    }

    private function embedAll(?int $limit): EmbedSummary
    {
        $parts = [$this->embedTenders($limit), $this->embedCompanies($limit), $this->embedAuthorities($limit)];

        return new EmbedSummary(
            'all',
            embedded: array_sum(array_map(static fn (EmbedSummary $p): int => $p->embedded, $parts)),
            skipped: array_sum(array_map(static fn (EmbedSummary $p): int => $p->skipped, $parts)),
            skipReasons: array_merge(...array_map(static fn (EmbedSummary $p): array => $p->skipReasons, $parts)),
        );
    }

    /**
     * @param  class-string<Model>  $modelClass
     * @param  list<string>  $with
     * @param  callable(Model): string  $textFor
     */
    private function embed(string $modelClass, array $with, string $column, callable $textFor, string $type, ?int $limit): EmbedSummary
    {
        if (! $this->google->configured()) {
            return new EmbedSummary($type, skipReasons: ['GOOGLE_API_KEY not configured — cannot embed.']);
        }

        // Resolve the id work-list up front so empty-text rows we skip can't be
        // re-selected into an infinite loop (they stay NULL but fall off the list).
        $ids = $modelClass::query()
            ->whereNull($column)
            ->orderBy('id')
            ->when($limit !== null, static fn ($q) => $q->limit($limit))
            ->pluck('id')
            ->all();

        $embedded = 0;
        $skipped = 0;
        $reasons = [];

        foreach (array_chunk($ids, self::BATCH) as $chunk) {
            $rows = $modelClass::query()->with($with)->whereIn('id', $chunk)->get();

            $texts = [];
            $targets = [];
            foreach ($rows as $row) {
                $text = trim($textFor($row));
                if ($text === '') {
                    $skipped++;
                    $this->pushReason($reasons, "{$type} #{$row->getKey()}: empty searchable text");

                    continue;
                }
                $texts[] = $text;
                $targets[] = $row;
            }

            if ($texts === []) {
                continue;
            }

            $vectors = $this->google->embedBatch($texts, 'RETRIEVAL_DOCUMENT');
            if ($vectors === null || count($vectors) !== count($texts)) {
                $skipped += count($texts);
                $this->pushReason($reasons, "{$type}: batch of ".count($texts).' failed to embed (see google error log)');

                continue;
            }

            foreach ($targets as $i => $row) {
                $row->setAttribute($column, $vectors[$i]);
                $row->save();
                $embedded++;
            }
        }

        $this->log->info('search:embed complete', ['type' => $type, 'embedded' => $embedded, 'skipped' => $skipped]);

        return new EmbedSummary($type, $embedded, $skipped, $reasons);
    }

    /** @param list<string> $reasons */
    private function pushReason(array &$reasons, string $reason): void
    {
        if (count($reasons) < self::MAX_STORED_REASONS) {
            $reasons[] = $reason;
        }
    }
}
