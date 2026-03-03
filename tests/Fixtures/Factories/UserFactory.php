<?php

namespace Canvastack\Canvastack\Tests\Fixtures\Factories;

use Canvastack\Canvastack\Tests\Fixtures\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password',
            'active' => true, // Add active column for property tests
        ];
    }
}
