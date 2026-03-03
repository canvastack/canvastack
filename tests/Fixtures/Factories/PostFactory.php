<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Fixtures\Factories;

use Canvastack\Canvastack\Tests\Fixtures\Models\Post;
use Canvastack\Canvastack\Tests\Fixtures\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Post Factory for Testing.
 *
 * Generates test data for Post model with valid columns.
 */
class PostFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Post::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(),
            'content' => fake()->paragraphs(3, true),
            'excerpt' => fake()->sentence(),
            'status' => fake()->randomElement(['draft', 'published', 'archived']),
            'featured' => fake()->boolean(20), // 20% chance of being featured
            'metadata' => [
                'seo' => [
                    'title' => fake()->sentence(),
                    'description' => fake()->sentence(),
                    'keywords' => fake()->words(5, true),
                ],
                'social' => [
                    'image' => fake()->imageUrl(),
                    'title' => fake()->sentence(),
                ],
            ],
        ];
    }

    /**
     * Indicate that the post is published.
     *
     * @return static
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'published',
            'published_at' => now(),
        ]);
    }

    /**
     * Indicate that the post is a draft.
     *
     * @return static
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'published_at' => null,
        ]);
    }

    /**
     * Indicate that the post is featured.
     *
     * @return static
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'featured' => true,
        ]);
    }

    /**
     * Indicate that the post belongs to a specific user.
     *
     * @param int|User $user
     * @return static
     */
    public function forUser(int|User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user instanceof User ? $user->id : $user,
        ]);
    }
}
