<?php

declare(strict_types=1);

namespace Modules\Detection\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Detection\Data\ReviewDecisionData;
use Modules\Detection\Models\Flag;

interface FlagRepository
{
    /** @return LengthAwarePaginator<int, Flag> */
    public function paginateApproved(array $filters, int $perPage): LengthAwarePaginator;

    public function findApproved(string $publicId): ?Flag;

    /** @return LengthAwarePaginator<int, Flag> */
    public function paginateByStatus(string $status, int $perPage): LengthAwarePaginator;

    public function findAny(string $publicId): ?Flag;

    public function approve(Flag $flag, ReviewDecisionData $decision): Flag;

    public function reject(Flag $flag, ?string $note): Flag;
}
