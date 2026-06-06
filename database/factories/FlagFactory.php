<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Support\PublicId\PublicIdGenerator;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Detection\Enums\ApprovalStatus;
use Modules\Detection\Enums\FlagSeverity;
use Modules\Detection\Enums\FlagType;
use Modules\Detection\Models\Flag;
use Modules\Procurement\Models\Tender;

/**
 * @extends Factory<Flag>
 */
class FlagFactory extends Factory
{
    protected $model = Flag::class;

    public function definition(): array
    {
        return [
            'public_id' => PublicIdGenerator::generate(),
            'type' => fake()->randomElement(FlagType::cases()),
            'severity' => fake()->randomElement(FlagSeverity::cases()),
            'status' => ApprovalStatus::Pending,
            'title' => fake()->sentence(6),
            'category' => fake()->randomElement(['roads', 'health', 'supplies', 'other']),
            // Default subject: a tender (morph type respects the enforced morph map).
            'subject_type' => (new Tender)->getMorphClass(),
            'subject_id' => Tender::factory(),
            'subject_label' => 'Обществена поръчка '.fake()->numerify('2026/S-######'),
            'explanation_bg' => 'Открито е съществено отклонение спрямо нормата за категорията.',
            'source_urls' => [fake()->url()],
            'evidence' => ['note' => 'факторите зад сигнала'],
            'detected_at' => now(),
        ];
    }
}
