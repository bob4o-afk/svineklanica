<?php

declare(strict_types=1);

namespace Modules\Detection\Detectors;

use App\Shared\DTO\CompanyWinData;
use App\Shared\Enums\CorruptionCategory;
use App\Shared\Enums\FlagSeverity;
use Modules\Detection\Enums\FlagType;

/**
 * 🏆 Serial winner (CLAUDE.md §1.1.3). Flags a company that wins many tenders —
 * louder when those wins concentrate on few contracting authorities (the same
 * company always winning from the same authority). Subject is the company.
 */
final class SerialWinnerDetector extends AbstractDetector
{
    /** Minimum wins before a company is even considered. */
    private const MIN_WINS = 3;

    public function type(): FlagType
    {
        return FlagType::SerialWinner;
    }

    protected function detect(): array
    {
        $rows = [];
        foreach ($this->procurement->serialWinners(self::MIN_WINS) as $winner) {
            $score = $this->score($winner);

            $rows[] = [
                'type' => FlagType::SerialWinner,
                'sphere' => null, // a serial winner spans tenders/spheres
                'category' => CorruptionCategory::PublicProcurement,
                'score' => $score,
                'severity' => FlagSeverity::fromScore($score),
                'subject_type' => 'company',
                'subject_id' => $winner->companyId,
                'subject_label' => $winner->name,
                'explanation_bg' => __(
                    $winner->eik !== null ? 'detection.serial_winner' : 'detection.serial_winner_no_eik',
                    [
                        'company' => $winner->name,
                        'eik' => $winner->eik ?? '',
                        'wins' => $winner->winCount,
                        'authorities' => $winner->distinctAuthorities,
                    ],
                ),
                'source_urls' => [$winner->sourceUrl],
                'evidence' => [
                    'eik' => $winner->eik,
                    'win_count' => $winner->winCount,
                    'distinct_authorities' => $winner->distinctAuthorities,
                ],
                'detected_at' => now(),
            ];
        }

        return $rows;
    }

    /**
     * Base on the win count; add a concentration bonus when wins pile onto few
     * authorities (winCount − distinctAuthorities = the repeat-pairings count).
     */
    private function score(CompanyWinData $winner): int
    {
        $base = $winner->winCount * 15;
        $concentration = max(0, $winner->winCount - $winner->distinctAuthorities) * 10;

        return (int) min(100, $base + $concentration);
    }
}
