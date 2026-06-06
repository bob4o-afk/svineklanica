<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Support\PublicId\PublicIdGenerator;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Procurement\Models\Company;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        return [
            'public_id' => PublicIdGenerator::generate(),
            'eik' => (string) fake()->unique()->numberBetween(100000000, 999999999),
            'name' => fake()->company().' ЕООД',
            'address' => fake()->streetAddress(),
            'owner_name' => fake()->name(),
            'phone' => '+359'.fake()->numberBetween(880000000, 899999999),
            'source_url' => fake()->url(),
        ];
    }
}
