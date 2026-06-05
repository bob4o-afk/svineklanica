<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Support\PublicId\PublicIdGenerator;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Procurement\Models\PriceSnapshot;
use Modules\Procurement\Models\TenderItem;

/**
 * @extends Factory<PriceSnapshot>
 */
class PriceSnapshotFactory extends Factory
{
    protected $model = PriceSnapshot::class;

    public function definition(): array
    {
        return [
            'public_id' => PublicIdGenerator::generate(),
            'tender_item_id' => TenderItem::factory(),
            'product_key' => fake()->randomElement(['laptop', 'monitor', 'printer']),
            'description' => fake()->randomElement(['Лаптоп', 'Монитор', 'Принтер']),
            'price' => fake()->randomFloat(2, 10, 10000),
            'currency' => 'BGN',
            'captured_at' => now()->subDays(fake()->numberBetween(0, 365)),
            'source_url' => fake()->url(),
        ];
    }
}
