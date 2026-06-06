<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use App\Shared\Enums\CorruptionCategory;
use App\Shared\Enums\FlagSeverity;
use App\Shared\Enums\Sphere;
use App\Support\PublicId\PublicIdGenerator;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Publishing\Enums\PostStatus;
use Modules\Publishing\Enums\PostTag;
use Modules\Publishing\Models\Post;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    protected $model = Post::class;

    public function definition(): array
    {
        $title = 'Как '.fake()->company().' спечели поръчка за '.fake()->numberBetween(1, 50).' млн. лв.';

        return [
            'public_id' => PublicIdGenerator::generate(),
            'author_id' => User::factory()->admin(),
            'title' => $title,
            'slug' => fake()->unique()->slug(),
            'excerpt' => fake()->sentence(14),
            'body' => fake()->paragraphs(5, true),
            'status' => PostStatus::Published,
            'sphere' => fake()->randomElement(Sphere::cases()),
            'category' => fake()->randomElement(CorruptionCategory::cases()),
            'severity' => fake()->randomElement(FlagSeverity::cases()),
            'tags' => fake()->randomElements(PostTag::cases(), fake()->numberBetween(1, count(PostTag::cases()))),
            'source_urls' => [fake()->url(), fake()->url()],
            'view_count' => fake()->numberBetween(0, 25000),
            'published_at' => now()->subDays(fake()->numberBetween(0, 60)),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PostStatus::Draft,
            'published_at' => null,
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PostStatus::Archived,
        ]);
    }
}
