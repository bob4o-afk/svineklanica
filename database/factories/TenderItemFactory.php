<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Support\PublicId\PublicIdGenerator;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Procurement\Models\Tender;
use Modules\Procurement\Models\TenderItem;

/**
 * @extends Factory<TenderItem>
 */
class TenderItemFactory extends Factory
{
    protected $model = TenderItem::class;

    public function definition(): array
    {
        return [
            'public_id' => PublicIdGenerator::generate(),
            'tender_id' => Tender::factory(),
            'description' => fake()->randomElement(['Лаптоп', 'Преносим компютър', 'Монитор', 'Принтер']).' '.fake()->word(),
            'quantity' => fake()->randomFloat(3, 1, 100),
            'unit' => 'бр.',
            'unit_price' => fake()->randomFloat(2, 10, 10000),
            'currency' => 'BGN',
            'vat_included' => true,
            'source_url' => fake()->url(),
        ];
    }
}
