<?php

declare(strict_types=1);

namespace Modules\Detection\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Modules\Detection\Contracts\FlagRepository;
use Modules\Detection\Data\FlagFilterData;
use Modules\Detection\Enums\FlagType;
use Modules\Detection\Models\Flag;

final class EloquentFlagRepository implements FlagRepository
{
    public function paginateFiltered(FlagFilterData $filter): LengthAwarePaginator
    {
        return Flag::query()
            ->when($filter->sphere !== null, fn ($q) => $q->where('sphere', $filter->sphere))
            ->when($filter->category !== null, fn ($q) => $q->where('category', $filter->category))
            ->when($filter->severity !== null, fn ($q) => $q->where('severity', $filter->severity))
            ->when($filter->type !== null, fn ($q) => $q->where('type', $filter->type))
            ->when($filter->minScore !== null, fn ($q) => $q->where('score', '>=', $filter->minScore))
            ->orderByDesc('score')
            ->orderByDesc('detected_at')
            ->paginate($filter->perPage);
    }

    public function find(string $publicId): ?Flag
    {
        // public_id is a uuid column — a malformed id is simply "not found",
        // never a DB-level cast error on this public, abuse-exposed endpoint.
        if (! Str::isUuid($publicId)) {
            return null;
        }

        return Flag::query()->where('public_id', $publicId)->first();
    }

    public function flaggedSubjectScores(): array
    {
        $grouped = [];
        Flag::query()
            ->whereNotNull('subject_id')
            ->groupBy('subject_type', 'subject_id')
            ->selectRaw('subject_type, subject_id, MAX(score) as max_score')
            ->get()
            ->each(function (Flag $row) use (&$grouped): void {
                // Strongest signal wins when a subject carries several flags.
                $grouped[$row->subject_type][(int) $row->subject_id] = (int) $row->max_score;
            });

        return $grouped;
    }

    public function representativeFlagIds(): array
    {
        $grouped = [];
        // Highest score first → the first row seen per subject is its representative.
        Flag::query()
            ->whereNotNull('subject_id')
            ->orderBy('subject_type')
            ->orderBy('subject_id')
            ->orderByDesc('score')
            ->get(['subject_type', 'subject_id', 'public_id'])
            ->each(function (Flag $flag) use (&$grouped): void {
                $grouped[$flag->subject_type] ??= [];
                $grouped[$flag->subject_type][(int) $flag->subject_id] ??= (string) $flag->public_id;
            });

        return $grouped;
    }

    public function deleteByType(FlagType $type): void
    {
        Flag::query()->where('type', $type)->delete();
    }

    public function deleteAiFlagsForTenders(array $tenderIds): void
    {
        if ($tenderIds === []) {
            return;
        }

        Flag::query()
            ->where('subject_type', 'tender')
            ->whereIn('subject_id', $tenderIds)
            ->where('evidence->origin', 'ai')
            ->delete();
    }

    public function createMany(array $rows): int
    {
        $written = 0;
        foreach ($rows as $row) {
            // Eloquent create (not a bulk insert) so HasPublicId assigns a UUIDv7
            // and the enum/array casts apply (backend.md §7).
            Flag::create($row);
            $written++;
        }

        return $written;
    }
}
