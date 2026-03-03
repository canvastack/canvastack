<?php

namespace Canvastack\Canvastack\Tests\Fixtures\Factories;

use Canvastack\Canvastack\Models\Permission;
use Illuminate\Database\Eloquent\Factories\Factory;

class PermissionFactory extends Factory
{
    protected $model = Permission::class;

    public function definition(): array
    {
        $modules = ['users', 'posts', 'products', 'orders', 'settings'];
        $actions = ['view', 'create', 'edit', 'delete', 'manage'];

        $module = $modules[array_rand($modules)];
        $action = $actions[array_rand($actions)];

        return [
            'name' => "{$module}.{$action}." . $this->faker->unique()->numberBetween(1, 10000),
            'display_name' => ucfirst($action) . ' ' . ucfirst($module),
            'description' => 'Test description for ' . $module,
            'module' => $module,
        ];
    }

    /**
     * Set a specific module for the permission.
     */
    public function module(string $module): static
    {
        return $this->state(fn (array $attributes) => [
            'module' => $module,
        ]);
    }

    /**
     * Set a specific name for the permission.
     */
    public function name(string $name): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $name,
        ]);
    }
}
