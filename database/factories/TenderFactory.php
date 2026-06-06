<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Shared\Enums\CorruptionCategory;
use App\Support\PublicId\PublicIdGenerator;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Procurement\Enums\TenderStatus;
use Modules\Procurement\Models\ContractingAuthority;
use Modules\Procurement\Models\Tender;

/**
 * @extends Factory<Tender>
 */
class TenderFactory extends Factory
{
    protected $model = Tender::class;

    public function definition(): array
    {
        return [
            'public_id' => PublicIdGenerator::generate(),
            'source' => 'ted',
            'natural_key' => fake()->unique()->numerify('2026/S-######'),
            'source_url' => 'https://ted.europa.eu/udl?uri=TED:NOTICE:'.fake()->unique()->numerify('######-2026'),
            'fetched_at' => now(),
            'contracting_authority_id' => ContractingAuthority::factory(),
            'winner_company_id' => null,
            'title' => 'Доставка на '.fake()->word(),
            'description' => fake()->sentence(12),
            'cpv_code' => fake()->numerify('########'),
            // A tender IS a public procurement; sphere stays null unless a test sets it.
            'sphere' => null,
            'category' => CorruptionCategory::PublicProcurement,
            'value' => fake()->randomFloat(2, 1000, 5000000),
            'currency' => 'BGN',
            'vat_included' => true,
            'status' => TenderStatus::Announced,
            'announced_at' => now()->subDays(30),
            'deadline_at' => now()->subDays(10),
            'awarded_at' => null,
            'cancelled_at' => null,
        ];
    }

    public function awarded(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => TenderStatus::Awarded,
            'awarded_at' => now()->subDays(5),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => TenderStatus::Cancelled,
            'cancelled_at' => now()->subDays(5),
        ]);
    }
}
