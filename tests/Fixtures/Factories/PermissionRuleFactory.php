<?php

namespace Canvastack\Canvastack\Tests\Fixtures\Factories;

use Canvastack\Canvastack\Models\Permission;
use Canvastack\Canvastack\Models\PermissionRule;
use Illuminate\Database\Eloquent\Factories\Factory;

class PermissionRuleFactory extends Factory
{
    protected $model = PermissionRule::class;

    public function definition(): array
    {
        $types = PermissionRule::getValidTypes();

        return [
            'permission_id' => Permission::factory(),
            'rule_type' => $types[array_rand($types)],
            'rule_config' => $this->generateRuleConfig(),
            'priority' => $this->faker->numberBetween(0, 100),
        ];
    }

    /**
     * Generate rule configuration based on type.
     */
    protected function generateRuleConfig(): array
    {
        return [
            'model' => 'App\\Models\\Post',
            'conditions' => [
                'user_id' => '{{auth.id}}',
            ],
        ];
    }

    /**
     * Create a row-level rule.
     */
    public function rowLevel(): static
    {
        return $this->state(fn (array $attributes) => [
            'rule_type' => PermissionRule::TYPE_ROW,
            'rule_config' => [
                'type' => 'row',
                'model' => 'App\\Models\\Post',
                'conditions' => [
                    'user_id' => '{{auth.id}}',
                    'department_id' => '{{auth.department}}',
                ],
                'operator' => 'AND',
            ],
        ]);
    }

    /**
     * Create a column-level rule.
     */
    public function columnLevel(): static
    {
        return $this->state(fn (array $attributes) => [
            'rule_type' => PermissionRule::TYPE_COLUMN,
            'rule_config' => [
                'type' => 'column',
                'model' => 'App\\Models\\Post',
                'allowed_columns' => ['title', 'content', 'excerpt', 'tags', 'field_name'],
                'denied_columns' => ['status', 'featured', 'published_at'],
                'mode' => 'whitelist',
            ],
        ]);
    }

    /**
     * Create a JSON attribute rule.
     */
    public function jsonAttribute(): static
    {
        return $this->state(fn (array $attributes) => [
            'rule_type' => PermissionRule::TYPE_JSON_ATTRIBUTE,
            'rule_config' => [
                'type' => 'json_attribute',
                'model' => 'App\\Models\\Post',
                'json_column' => 'metadata',
                'allowed_paths' => ['seo.*', 'social.*', 'layout.*'],
                'denied_paths' => ['featured', 'promoted', 'sticky'],
                'path_separator' => '.',
            ],
        ]);
    }

    /**
     * Create a conditional rule.
     */
    public function conditional(): static
    {
        return $this->state(fn (array $attributes) => [
            'rule_type' => PermissionRule::TYPE_CONDITIONAL,
            'rule_config' => [
                'type' => 'conditional',
                'model' => 'App\\Models\\Post',
                'condition' => "status === 'draft' AND user_id === {{auth.id}}",
                'allowed_operators' => ['===', '!==', '>', '<', '>=', '<=', 'AND', 'OR'],
            ],
        ]);
    }

    /**
     * Set a specific permission for the rule.
     */
    public function forPermission(int|Permission $permission): static
    {
        return $this->state(fn (array $attributes) => [
            'permission_id' => $permission instanceof Permission ? $permission->id : $permission,
        ]);
    }

    /**
     * Set a specific priority for the rule.
     */
    public function priority(int $priority): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => $priority,
        ]);
    }
}
