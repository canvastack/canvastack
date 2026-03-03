<?php

namespace Canvastack\Canvastack\Tests\Fixtures\Factories;

use Canvastack\Canvastack\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoleFactory extends Factory
{
    protected $model = Role::class;

    public function definition(): array
    {
        $name = 'role_' . $this->faker->unique()->numberBetween(1, 100000);

        return [
            'name' => $name,
            'display_name' => ucfirst(str_replace('_', ' ', $name)),
            'description' => 'Test role description',
            'level' => $this->faker->numberBetween(1, 99),
            'is_system' => false,
        ];
    }

    /**
     * Indicate that the role is a system role.
     */
    public function system(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_system' => true,
        ]);
    }

    /**
     * Set a specific level for the role.
     */
    public function level(int $level): static
    {
        return $this->state(fn (array $attributes) => [
            'level' => $level,
        ]);
    }
}
