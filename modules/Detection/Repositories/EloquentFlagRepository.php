<?php

declare(strict_types=1);

namespace Modules\Detection\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Detection\Contracts\FlagRepository;
use Modules\Detection\Data\ReviewDecisionData;
use Modules\Detection\Enums\ApprovalStatus;
use Modules\Detection\Models\Flag;
use Modules\Procurement\Models\ContractingAuthority;
use Modules\Procurement\Models\Tender;

final class EloquentFlagRepository implements FlagRepository
{
    /** @return LengthAwarePaginator<int, Flag> */
    public function paginateApproved(array $filters, int $perPage): LengthAwarePaginator
    {
        $query = Flag::query()
            ->with(['subject'])
            ->where('status', ApprovalStatus::Approved)
            ->orderByDesc('published_at')
            ->orderByDesc('detected_at');

        if ($types = $filters['type'] ?? []) {
            $query->whereIn('type', $this->mapTypes($types));
        }
        if ($severities = $filters['severity'] ?? []) {
            $query->whereIn('severity', $this->mapSeverities($severities));
        }
        if ($categories = $filters['category'] ?? []) {
            $query->whereIn('category', $categories);
        }
        if ($q = $filters['q'] ?? null) {
            $query->where(function ($inner) use ($q): void {
                $inner->where('title', 'ilike', "%{$q}%")
                    ->orWhere('explanation_bg', 'ilike', "%{$q}%")
                    ->orWhere('subject_label', 'ilike', "%{$q}%");
            });
        }
        if ($region = $filters['region'] ?? null) {
            $query->whereHasMorph('subject', [Tender::class], function ($tender) use ($region): void {
                $tender->whereHas('authority', fn ($auth) => $auth->where('region', $region));
            });
        }

        if (($filters['sort'] ?? 'newest') === 'severity') {
            $query->reorder()->orderByDesc('severity')->orderByDesc('detected_at');
        }

        return $query->paginate($perPage);
    }

    public function findApproved(string $publicId): ?Flag
    {
        return Flag::query()
            ->with(['subject'])
            ->where('public_id', $publicId)
            ->where('status', ApprovalStatus::Approved)
            ->first();
    }

    /** @return LengthAwarePaginator<int, Flag> */
    public function paginateByStatus(string $status, int $perPage): LengthAwarePaginator
    {
        $enum = ApprovalStatus::fromWire($status) ?? ApprovalStatus::Pending;

        return Flag::query()
            ->with(['subject'])
            ->where('status', $enum)
            ->orderByDesc('detected_at')
            ->paginate($perPage);
    }

    public function findAny(string $publicId): ?Flag
    {
        return Flag::query()->with(['subject'])->where('public_id', $publicId)->first();
    }

    public function approve(Flag $flag, ReviewDecisionData $decision): Flag
    {
        if ($decision->title !== null) {
            $flag->title = $decision->title;
        }
        if ($decision->explanation_bg !== null) {
            $flag->explanation_bg = $decision->explanation_bg;
        }
        if ($decision->tags !== null) {
            $flag->tags = $decision->tags;
        }
        $flag->status = ApprovalStatus::Approved;
        $flag->published_at = now();
        $flag->save();

        return $flag->fresh(['subject']);
    }

    public function reject(Flag $flag, ?string $note): Flag
    {
        $flag->status = ApprovalStatus::Rejected;
        if ($note !== null) {
            $evidence = $flag->evidence ?? [];
            $evidence['review_note'] = $note;
            $flag->evidence = $evidence;
        }
        $flag->save();

        return $flag->fresh(['subject']);
    }

    /** @param array<int, string> $wires */
    private function mapTypes(array $wires): array
    {
        return array_values(array_filter(array_map(
            static fn (string $wire) => collect(\Modules\Detection\Enums\FlagType::cases())
                ->first(fn ($case) => $case->wire() === $wire)?->value,
            $wires,
        )));
    }

    /** @param array<int, string> $wires */
    private function mapSeverities(array $wires): array
    {
        return array_values(array_filter(array_map(
            static fn (string $wire) => collect(\Modules\Detection\Enums\FlagSeverity::cases())
                ->first(fn ($case) => $case->wire() === $wire)?->value,
            $wires,
        )));
    }
}
