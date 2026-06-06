<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Support\PublicId\PublicIdGenerator;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Procurement\Models\ContractingAuthority;

/**
 * @extends Factory<ContractingAuthority>
 */
class ContractingAuthorityFactory extends Factory
{
    protected $model = ContractingAuthority::class;

    public function definition(): array
    {
        return [
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Община '.fake()->city(),
            'eik' => (string) fake()->numberBetween(100000000, 999999999),
            'region' => fake()->city(),
            'source_url' => fake()->url(),
        ];
    }
}
