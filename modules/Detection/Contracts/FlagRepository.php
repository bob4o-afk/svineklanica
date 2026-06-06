<?php

declare(strict_types=1);

namespace Modules\Detection\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Detection\Data\FlagFilterData;
use Modules\Detection\Enums\FlagType;
use Modules\Detection\Models\Flag;

/** The only place that touches the flags table (backend.md §2). */
interface FlagRepository
{
    /** @return LengthAwarePaginator<Flag> Flags matching the filter, most suspicious + newest first. */
    public function paginateFiltered(FlagFilterData $filter): LengthAwarePaginator;

    public function find(string $publicId): ?Flag;

    /**
     * Max suspicion score (0–100) per distinct flagged subject, grouped by morph
     * alias — for the corruption-tax calculator's score-weighted flagged spend.
     * A subject with several flags keeps its STRONGEST signal (the max). Returns
     * e.g. ['tender' => [23 => 72, 24 => 50], 'company' => [5 => 100]].
     *
     * @return array<string, array<int, int>>
     */
    public function flaggedSubjectScores(): array;

    /** Clear every flag of a given type — lets a detector re-run idempotently. */
    public function deleteByType(FlagType $type): void;

    /**
     * Clear AI-authored flags (evidence.origin = 'ai') on the given tender subjects,
     * so the AI verdict ingest can re-run idempotently without touching detector flags.
     *
     * @param  array<int, int>  $tenderIds
     */
    public function deleteAiFlagsForTenders(array $tenderIds): void;

    /**
     * Persist a batch of flag rows (one detector's output).
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return int number of flags written
     */
    public function createMany(array $rows): int;
}
