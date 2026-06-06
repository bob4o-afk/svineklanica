<?php

declare(strict_types=1);

namespace Modules\Detection\Detectors;

use App\Shared\Contracts\ProcurementReadPort;
use Illuminate\Support\Facades\DB;
use Modules\Detection\Contracts\FlagRepository;
use Modules\Detection\Detectors\Contracts\Detector;

/**
 * Shared detector plumbing: compute the flag rows, then atomically replace this
 * type's existing flags with the fresh batch — so a re-run never duplicates
 * (backend.md §11). Subclasses only implement type() + detect().
 */
abstract class AbstractDetector implements Detector
{
    public function __construct(
        protected readonly ProcurementReadPort $procurement,
        protected readonly FlagRepository $flags,
    ) {}

    /**
     * Build the flag rows this detector asserts from the current data.
     *
     * @return array<int, array<string, mixed>>
     */
    abstract protected function detect(): array;

    public function run(): int
    {
        $rows = $this->detect(); // read + compute outside the write transaction

        return DB::transaction(function () use ($rows): int {
            $this->flags->deleteByType($this->type());

            return $this->flags->createMany($rows);
        });
    }
}
