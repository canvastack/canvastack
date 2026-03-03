<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Models;

use Canvastack\Canvastack\Models\Permission;
use Canvastack\Canvastack\Models\PermissionRule;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PermissionRuleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that permission rule uses correct table name.
     */
    public function test_permission_rule_uses_correct_table_name(): void
    {
        $rule = new PermissionRule();

        $this->assertEquals('permission_rules', $rule->getTable());
    }

    /**
     * Test that permission rule has correct fillable attributes.
     */
    public function test_permission_rule_has_correct_fillable_attributes(): void
    {
        $rule = new PermissionRule();

        $expected = [
            'permission_id',
            'rule_type',
            'rule_config',
            'priority',
        ];

        $this->assertEquals($expected, $rule->getFillableAttributes());
    }

    /**
     * Test that rule_config is cast to array.
     */
    public function test_rule_config_is_cast_to_array(): void
    {
        $rule = PermissionRule::factory()->create([
            'rule_config' => ['key' => 'value'],
        ]);

        $this->assertIsArray($rule->rule_config);
        $this->assertEquals(['key' => 'value'], $rule->rule_config);
    }

    /**
     * Test that priority is cast to integer.
     */
    public function test_priority_is_cast_to_integer(): void
    {
        $rule = PermissionRule::factory()->create([
            'priority' => '10',
        ]);

        $this->assertIsInt($rule->priority);
        $this->assertEquals(10, $rule->priority);
    }

    /**
     * Test that permission rule belongs to permission.
     */
    public function test_permission_rule_belongs_to_permission(): void
    {
        $permission = Permission::factory()->create();
        $rule = PermissionRule::factory()->create([
            'permission_id' => $permission->id,
        ]);

        $this->assertInstanceOf(Permission::class, $rule->permission);
        $this->assertEquals($permission->id, $rule->permission->id);
    }

    /**
     * Test forPermission scope filters by permission.
     */
    public function test_for_permission_scope_filters_by_permission(): void
    {
        $permission1 = Permission::factory()->create();
        $permission2 = Permission::factory()->create();

        PermissionRule::factory()->count(3)->create(['permission_id' => $permission1->id]);
        PermissionRule::factory()->count(2)->create(['permission_id' => $permission2->id]);

        $rules = PermissionRule::forPermission($permission1->id)->get();

        $this->assertCount(3, $rules);
        $this->assertTrue($rules->every(fn ($rule) => $rule->permission_id === $permission1->id));
    }

    /**
     * Test byType scope filters by rule type.
     */
    public function test_by_type_scope_filters_by_rule_type(): void
    {
        PermissionRule::factory()->rowLevel()->count(2)->create();
        PermissionRule::factory()->columnLevel()->count(3)->create();
        PermissionRule::factory()->conditional()->count(1)->create();

        $rowRules = PermissionRule::byType(PermissionRule::TYPE_ROW)->get();
        $columnRules = PermissionRule::byType(PermissionRule::TYPE_COLUMN)->get();

        $this->assertCount(2, $rowRules);
        $this->assertCount(3, $columnRules);
        $this->assertTrue($rowRules->every(fn ($rule) => $rule->rule_type === PermissionRule::TYPE_ROW));
        $this->assertTrue($columnRules->every(fn ($rule) => $rule->rule_type === PermissionRule::TYPE_COLUMN));
    }

    /**
     * Test byPriority scope orders by priority.
     */
    public function test_by_priority_scope_orders_by_priority(): void
    {
        PermissionRule::factory()->create(['priority' => 30]);
        PermissionRule::factory()->create(['priority' => 10]);
        PermissionRule::factory()->create(['priority' => 20]);

        $rules = PermissionRule::byPriority()->get();

        $this->assertEquals(10, $rules[0]->priority);
        $this->assertEquals(20, $rules[1]->priority);
        $this->assertEquals(30, $rules[2]->priority);
    }

    /**
     * Test byPriority scope orders by priority descending.
     */
    public function test_by_priority_scope_orders_by_priority_descending(): void
    {
        PermissionRule::factory()->create(['priority' => 30]);
        PermissionRule::factory()->create(['priority' => 10]);
        PermissionRule::factory()->create(['priority' => 20]);

        $rules = PermissionRule::byPriority('desc')->get();

        $this->assertEquals(30, $rules[0]->priority);
        $this->assertEquals(20, $rules[1]->priority);
        $this->assertEquals(10, $rules[2]->priority);
    }

    /**
     * Test getValidTypes returns all valid rule types.
     */
    public function test_get_valid_types_returns_all_valid_rule_types(): void
    {
        $types = PermissionRule::getValidTypes();

        $this->assertCount(4, $types);
        $this->assertContains(PermissionRule::TYPE_ROW, $types);
        $this->assertContains(PermissionRule::TYPE_COLUMN, $types);
        $this->assertContains(PermissionRule::TYPE_JSON_ATTRIBUTE, $types);
        $this->assertContains(PermissionRule::TYPE_CONDITIONAL, $types);
    }

    /**
     * Test isValidType returns true for valid types.
     */
    public function test_is_valid_type_returns_true_for_valid_types(): void
    {
        $this->assertTrue(PermissionRule::isValidType(PermissionRule::TYPE_ROW));
        $this->assertTrue(PermissionRule::isValidType(PermissionRule::TYPE_COLUMN));
        $this->assertTrue(PermissionRule::isValidType(PermissionRule::TYPE_JSON_ATTRIBUTE));
        $this->assertTrue(PermissionRule::isValidType(PermissionRule::TYPE_CONDITIONAL));
    }

    /**
     * Test isValidType returns false for invalid types.
     */
    public function test_is_valid_type_returns_false_for_invalid_types(): void
    {
        $this->assertFalse(PermissionRule::isValidType('invalid'));
        $this->assertFalse(PermissionRule::isValidType('unknown'));
        $this->assertFalse(PermissionRule::isValidType(''));
    }

    /**
     * Test evaluate method returns boolean.
     */
    public function test_evaluate_method_returns_boolean(): void
    {
        $rule = PermissionRule::factory()->rowLevel()->create();
        $model = new \stdClass();

        $result = $rule->evaluate($model);

        $this->assertIsBool($result);
    }

    /**
     * Test evaluate method calls correct evaluation method for row type.
     */
    public function test_evaluate_method_calls_correct_evaluation_method_for_row_type(): void
    {
        $rule = PermissionRule::factory()->rowLevel()->create();
        $model = new \stdClass();

        $result = $rule->evaluate($model);

        // Currently returns true as placeholder
        $this->assertTrue($result);
    }

    /**
     * Test evaluate method calls correct evaluation method for column type.
     */
    public function test_evaluate_method_calls_correct_evaluation_method_for_column_type(): void
    {
        $rule = PermissionRule::factory()->columnLevel()->create();
        $model = new \stdClass();

        $result = $rule->evaluate($model, 'field_name');

        // Currently returns true as placeholder
        $this->assertTrue($result);
    }

    /**
     * Test evaluate method calls correct evaluation method for json attribute type.
     */
    public function test_evaluate_method_calls_correct_evaluation_method_for_json_attribute_type(): void
    {
        $rule = PermissionRule::factory()->jsonAttribute()->create();
        $model = new \stdClass();

        $result = $rule->evaluate($model, 'metadata.seo.title');

        // Currently returns true as placeholder
        $this->assertTrue($result);
    }

    /**
     * Test evaluate method calls correct evaluation method for conditional type.
     */
    public function test_evaluate_method_calls_correct_evaluation_method_for_conditional_type(): void
    {
        $rule = PermissionRule::factory()->conditional()->create();
        $model = new \stdClass();

        $result = $rule->evaluate($model);

        // Currently returns true as placeholder
        $this->assertTrue($result);
    }

    /**
     * Test factory creates row level rule correctly.
     */
    public function test_factory_creates_row_level_rule_correctly(): void
    {
        $rule = PermissionRule::factory()->rowLevel()->create();

        $this->assertEquals(PermissionRule::TYPE_ROW, $rule->rule_type);
        $this->assertArrayHasKey('type', $rule->rule_config);
        $this->assertArrayHasKey('model', $rule->rule_config);
        $this->assertArrayHasKey('conditions', $rule->rule_config);
        $this->assertEquals('row', $rule->rule_config['type']);
    }

    /**
     * Test factory creates column level rule correctly.
     */
    public function test_factory_creates_column_level_rule_correctly(): void
    {
        $rule = PermissionRule::factory()->columnLevel()->create();

        $this->assertEquals(PermissionRule::TYPE_COLUMN, $rule->rule_type);
        $this->assertArrayHasKey('type', $rule->rule_config);
        $this->assertArrayHasKey('model', $rule->rule_config);
        $this->assertArrayHasKey('allowed_columns', $rule->rule_config);
        $this->assertArrayHasKey('denied_columns', $rule->rule_config);
        $this->assertEquals('column', $rule->rule_config['type']);
    }

    /**
     * Test factory creates json attribute rule correctly.
     */
    public function test_factory_creates_json_attribute_rule_correctly(): void
    {
        $rule = PermissionRule::factory()->jsonAttribute()->create();

        $this->assertEquals(PermissionRule::TYPE_JSON_ATTRIBUTE, $rule->rule_type);
        $this->assertArrayHasKey('type', $rule->rule_config);
        $this->assertArrayHasKey('model', $rule->rule_config);
        $this->assertArrayHasKey('json_column', $rule->rule_config);
        $this->assertArrayHasKey('allowed_paths', $rule->rule_config);
        $this->assertEquals('json_attribute', $rule->rule_config['type']);
    }

    /**
     * Test factory creates conditional rule correctly.
     */
    public function test_factory_creates_conditional_rule_correctly(): void
    {
        $rule = PermissionRule::factory()->conditional()->create();

        $this->assertEquals(PermissionRule::TYPE_CONDITIONAL, $rule->rule_type);
        $this->assertArrayHasKey('type', $rule->rule_config);
        $this->assertArrayHasKey('model', $rule->rule_config);
        $this->assertArrayHasKey('condition', $rule->rule_config);
        $this->assertEquals('conditional', $rule->rule_config['type']);
    }

    /**
     * Test permission has many permission rules relationship.
     */
    public function test_permission_has_many_permission_rules_relationship(): void
    {
        $permission = Permission::factory()->create();
        PermissionRule::factory()->count(3)->create(['permission_id' => $permission->id]);

        $this->assertCount(3, $permission->permissionRules);
        $this->assertInstanceOf(PermissionRule::class, $permission->permissionRules->first());
    }
}
