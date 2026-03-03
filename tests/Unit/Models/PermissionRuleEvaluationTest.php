<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Models;

use Canvastack\Canvastack\Models\PermissionRule;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Tests for PermissionRule evaluation methods.
 *
 * This test class focuses on testing all evaluation methods
 * with various edge cases to achieve 100% code coverage.
 *
 * Test Coverage Summary:
 * - Row-level rule evaluation (9 tests)
 *   - AND/OR operators
 *   - Empty conditions
 *   - Numeric string comparison
 *   - Null value comparison
 *   - Missing fields
 *
 * - Column-level rule evaluation (5 tests)
 *   - Whitelist mode
 *   - Blacklist mode
 *   - Null field handling
 *   - Empty allowed/denied columns
 *
 * - JSON attribute rule evaluation (6 tests)
 *   - Exact path matching
 *   - Wildcard path matching
 *   - Denied paths precedence
 *   - Null field handling
 *   - Empty allowed paths
 *   - Path without json column prefix
 *
 * - Conditional rule evaluation (18 tests)
 *   - All comparison operators (===, !==, >, <, >=, <=, in, not_in)
 *   - Logical operators (AND, OR, NOT)
 *   - Value types (string, boolean, null, numeric, float, array)
 *   - Empty conditions
 *   - Multiple AND/OR conditions
 *
 * - Edge cases and error handling (10 tests)
 *   - Invalid rule types
 *   - Path matching with wildcards
 *   - Value parsing for all types
 *
 * Total: 48 tests with 68 assertions
 */
class PermissionRuleEvaluationTest extends TestCase
{
    use RefreshDatabase;

    // ========================================
    // Row-Level Rule Evaluation Tests
    // ========================================

    /**
     * Test evaluateRowRule with matching conditions (AND operator).
     */
    public function test_evaluate_row_rule_with_matching_conditions_and_operator(): void
    {
        $rule = PermissionRule::factory()->create([
            'rule_type' => PermissionRule::TYPE_ROW,
            'rule_config' => [
                'type' => 'row',
                'model' => 'App\\Models\\Post',
                'conditions' => [
                    'user_id' => 1,
                    'status' => 'draft',
                ],
                'operator' => 'AND',
            ],
        ]);

        $model = (object) [
            'user_id' => 1,
            'status' => 'draft',
        ];

        $this->assertTrue($rule->evaluate($model));
    }

    /**
     * Test evaluateRowRule with non-matching conditions (AND operator).
     */
    public function test_evaluate_row_rule_with_non_matching_conditions_and_operator(): void
    {
        $rule = PermissionRule::factory()->create([
            'rule_type' => PermissionRule::TYPE_ROW,
            'rule_config' => [
                'type' => 'row',
                'model' => 'App\\Models\\Post',
                'conditions' => [
                    'user_id' => 1,
                    'status' => 'draft',
                ],
                'operator' => 'AND',
            ],
        ]);

        $model = (object) [
            'user_id' => 1,
            'status' => 'published', // Different status
        ];

        $this->assertFalse($rule->evaluate($model));
    }

    /**
     * Test evaluateRowRule with matching conditions (OR operator).
     */
    public function test_evaluate_row_rule_with_matching_conditions_or_operator(): void
    {
        $rule = PermissionRule::factory()->create([
            'rule_type' => PermissionRule::TYPE_ROW,
            'rule_config' => [
                'type' => 'row',
                'model' => 'App\\Models\\Post',
                'conditions' => [
                    'user_id' => 1,
                    'status' => 'draft',
                ],
                'operator' => 'OR',
            ],
        ]);

        $model = (object) [
            'user_id' => 2, // Different user
            'status' => 'draft', // But matching status
        ];

        $this->assertTrue($rule->evaluate($model));
    }

    /**
     * Test evaluateRowRule with no matching conditions (OR operator).
     */
    public function test_evaluate_row_rule_with_no_matching_conditions_or_operator(): void
    {
        $rule = PermissionRule::factory()->create([
            'rule_type' => PermissionRule::TYPE_ROW,
            'rule_config' => [
                'type' => 'row',
                'model' => 'App\\Models\\Post',
                'conditions' => [
                    'user_id' => 1,
                    'status' => 'draft',
                ],
                'operator' => 'OR',
            ],
        ]);

        $model = (object) [
            'user_id' => 2,
            'status' => 'published',
        ];

        $this->assertFalse($rule->evaluate($model));
    }

    /**
     * Test evaluateRowRule with empty conditions.
     */
    public function test_evaluate_row_rule_with_empty_conditions(): void
    {
        $rule = PermissionRule::factory()->create([
            'rule_type' => PermissionRule::TYPE_ROW,
            'rule_config' => [
                'type' => 'row',
                'model' => 'App\\Models\\Post',
                'conditions' => [],
            ],
        ]);

        $model = (object) ['user_id' => 1];

        $this->assertTrue($rule->evaluate($model));
    }

    /**
     * Test evaluateRowRule with numeric string comparison.
     */
    public function test_evaluate_row_rule_with_numeric_string_comparison(): void
    {
        $rule = PermissionRule::factory()->create([
            'rule_type' => PermissionRule::TYPE_ROW,
            'rule_config' => [
                'type' => 'row',
                'model' => 'App\\Models\\Post',
                'conditions' => [
                    'user_id' => '1', // String
                ],
            ],
        ]);

        $model = (object) [
            'user_id' => 1, // Integer
        ];

        $this->assertTrue($rule->evaluate($model));
    }

    /**
     * Test evaluateRowRule with null value comparison.
     */
    public function test_evaluate_row_rule_with_null_value_comparison(): void
    {
        $rule = PermissionRule::factory()->create([
            'rule_type' => PermissionRule::TYPE_ROW,
            'rule_config' => [
                'type' => 'row',
                'model' => 'App\\Models\\Post',
                'conditions' => [
                    'deleted_at' => null,
                ],
            ],
        ]);

        $model = (object) [
            'deleted_at' => null,
        ];

        $this->assertTrue($rule->evaluate($model));
    }

    /**
     * Test evaluateRowRule with missing field on model.
     */
    public function test_evaluate_row_rule_with_missing_field_on_model(): void
    {
        $rule = PermissionRule::factory()->create([
            'rule_type' => PermissionRule::TYPE_ROW,
            'rule_config' => [
                'type' => 'row',
                'model' => 'App\\Models\\Post',
                'conditions' => [
                    'nonexistent_field' => 'value',
                ],
            ],
        ]);

        $model = (object) ['user_id' => 1];

        $this->assertFalse($rule->evaluate($model));
    }

    // ========================================
    // Column-Level Rule Evaluation Tests
    // ========================================

    /**
     * Test evaluateColumnRule with whitelist mode and allowed column.
     */
    public function test_evaluate_column_rule_with_whitelist_mode_and_allowed_column(): void
    {
        $rule = PermissionRule::factory()->create([
            'rule_type' => PermissionRule::TYPE_COLUMN,
            'rule_config' => [
                'type' => 'column',
                'model' => 'App\\Models\\Post',
                'mode' => 'whitelist',
                'allowed_columns' => ['title', 'content', 'excerpt'],
            ],
        ]);

        $model = (object) [];

        $this->assertTrue($rule->evaluate($model, 'title'));
        $this->assertTrue($rule->evaluate($model, 'content'));
        $this->assertFalse($rule->evaluate($model, 'status'));
    }

    /**
     * Test evaluateColumnRule with blacklist mode and denied column.
     */
    public function test_evaluate_column_rule_with_blacklist_mode_and_denied_column(): void
    {
        $rule = PermissionRule::factory()->create([
            'rule_type' => PermissionRule::TYPE_COLUMN,
            'rule_config' => [
                'type' => 'column',
                'model' => 'App\\Models\\Post',
                'mode' => 'blacklist',
                'denied_columns' => ['status', 'featured'],
            ],
        ]);

        $model = (object) [];

        $this->assertTrue($rule->evaluate($model, 'title'));
        $this->assertTrue($rule->evaluate($model, 'content'));
        $this->assertFalse($rule->evaluate($model, 'status'));
        $this->assertFalse($rule->evaluate($model, 'featured'));
    }

    /**
     * Test evaluateColumnRule with null field.
     */
    public function test_evaluate_column_rule_with_null_field(): void
    {
        $rule = PermissionRule::factory()->create([
            'rule_type' => PermissionRule::TYPE_COLUMN,
            'rule_config' => [
                'type' => 'column',
                'model' => 'App\\Models\\Post',
                'mode' => 'whitelist',
                'allowed_columns' => ['title'],
            ],
        ]);

        $model = (object) [];

        $this->assertFalse($rule->evaluate($model, null));
    }

    /**
     * Test evaluateColumnRule with empty allowed columns in whitelist mode.
     */
    public function test_evaluate_column_rule_with_empty_allowed_columns_in_whitelist_mode(): void
    {
        $rule = PermissionRule::factory()->create([
            'rule_type' => PermissionRule::TYPE_COLUMN,
            'rule_config' => [
                'type' => 'column',
                'model' => 'App\\Models\\Post',
                'mode' => 'whitelist',
                'allowed_columns' => [],
            ],
        ]);

        $model = (object) [];

        $this->assertFalse($rule->evaluate($model, 'title'));
    }

    /**
     * Test evaluateColumnRule with empty denied columns in blacklist mode.
     */
    public function test_evaluate_column_rule_with_empty_denied_columns_in_blacklist_mode(): void
    {
        $rule = PermissionRule::factory()->create([
            'rule_type' => PermissionRule::TYPE_COLUMN,
            'rule_config' => [
                'type' => 'column',
                'model' => 'App\\Models\\Post',
                'mode' => 'blacklist',
                'denied_columns' => [],
            ],
        ]);

        $model = (object) [];

        $this->assertTrue($rule->evaluate($model, 'title'));
        $this->assertTrue($rule->evaluate($model, 'any_column'));
    }

    // ========================================
    // JSON Attribute Rule Evaluation Tests
    // ========================================

    /**
     * Test evaluateJsonAttributeRule with exact path match.
     */
    public function test_evaluate_json_attribute_rule_with_exact_path_match(): void
    {
        $rule = PermissionRule::factory()->create([
            'rule_type' => PermissionRule::TYPE_JSON_ATTRIBUTE,
            'rule_config' => [
                'type' => 'json_attribute',
                'model' => 'App\\Models\\Post',
                'json_column' => 'metadata',
                'allowed_paths' => ['seo.title', 'seo.description'],
                'path_separator' => '.',
            ],
        ]);

        $model = (object) [];

        $this->assertTrue($rule->evaluate($model, 'metadata.seo.title'));
        $this->assertTrue($rule->evaluate($model, 'metadata.seo.description'));
        $this->assertFalse($rule->evaluate($model, 'metadata.featured'));
    }

    /**
     * Test evaluateJsonAttributeRule with wildcard path match.
     */
    public function test_evaluate_json_attribute_rule_with_wildcard_path_match(): void
    {
        $rule = PermissionRule::factory()->create([
            'rule_type' => PermissionRule::TYPE_JSON_ATTRIBUTE,
            'rule_config' => [
                'type' => 'json_attribute',
                'model' => 'App\\Models\\Post',
                'json_column' => 'metadata',
                'allowed_paths' => ['seo.*'],
                'path_separator' => '.',
            ],
        ]);

        $model = (object) [];

        $this->assertTrue($rule->evaluate($model, 'metadata.seo.title'));
        $this->assertTrue($rule->evaluate($model, 'metadata.seo.description'));
        $this->assertTrue($rule->evaluate($model, 'metadata.seo.keywords'));
        $this->assertFalse($rule->evaluate($model, 'metadata.featured'));
    }

    /**
     * Test evaluateJsonAttributeRule with denied paths taking precedence.
     */
    public function test_evaluate_json_attribute_rule_with_denied_paths_precedence(): void
    {
        $rule = PermissionRule::factory()->create([
            'rule_type' => PermissionRule::TYPE_JSON_ATTRIBUTE,
            'rule_config' => [
                'type' => 'json_attribute',
                'model' => 'App\\Models\\Post',
                'json_column' => 'metadata',
                'allowed_paths' => ['seo.*'],
                'denied_paths' => ['seo.internal'],
                'path_separator' => '.',
            ],
        ]);

        $model = (object) [];

        $this->assertTrue($rule->evaluate($model, 'metadata.seo.title'));
        $this->assertFalse($rule->evaluate($model, 'metadata.seo.internal'));
    }

    /**
     * Test evaluateJsonAttributeRule with null field.
     */
    public function test_evaluate_json_attribute_rule_with_null_field(): void
    {
        $rule = PermissionRule::factory()->create([
            'rule_type' => PermissionRule::TYPE_JSON_ATTRIBUTE,
            'rule_config' => [
                'type' => 'json_attribute',
                'model' => 'App\\Models\\Post',
                'json_column' => 'metadata',
                'allowed_paths' => ['seo.*'],
            ],
        ]);

        $model = (object) [];

        $this->assertFalse($rule->evaluate($model, null));
    }

    /**
     * Test evaluateJsonAttributeRule with empty allowed paths.
     */
    public function test_evaluate_json_attribute_rule_with_empty_allowed_paths(): void
    {
        $rule = PermissionRule::factory()->create([
            'rule_type' => PermissionRule::TYPE_JSON_ATTRIBUTE,
            'rule_config' => [
                'type' => 'json_attribute',
                'model' => 'App\\Models\\Post',
                'json_column' => 'metadata',
                'allowed_paths' => [],
                'denied_paths' => ['internal'],
            ],
        ]);

        $model = (object) [];

        // Empty allowed paths means allow all except denied
        $this->assertTrue($rule->evaluate($model, 'metadata.seo.title'));
        $this->assertFalse($rule->evaluate($model, 'metadata.internal'));
    }

    /**
     * Test evaluateJsonAttributeRule with path without json column prefix.
     */
    public function test_evaluate_json_attribute_rule_with_path_without_json_column_prefix(): void
    {
        $rule = PermissionRule::factory()->create([
            'rule_type' => PermissionRule::TYPE_JSON_ATTRIBUTE,
            'rule_config' => [
                'type' => 'json_attribute',
                'model' => 'App\\Models\\Post',
                'json_column' => 'metadata',
                'allowed_paths' => ['seo.title'],
            ],
        ]);

        $model = (object) [];

        // Path without json column prefix should still work
        $this->assertTrue($rule->evaluate($model, 'seo.title'));
    }

    // ========================================
    // Conditional Rule Evaluation Tests
    // ========================================

    /**
     * Test evaluateConditionalRule with simple equality.
     */
    public function test_evaluate_conditional_rule_with_simple_equality(): void
    {
        $rule = PermissionRule::factory()->create([
            'rule_type' => PermissionRule::TYPE_CONDITIONAL,
            'rule_config' => [
                'type' => 'conditional',
                'model' => 'App\\Models\\Post',
                'condition' => "status === 'draft'",
            ],
        ]);

        $model = (object) ['status' => 'draft'];

        $this->assertTrue($rule->evaluate($model));
    }

    /**
     * Test evaluateConditionalRule with inequality.
     */
    public function test_evaluate_conditional_rule_with_inequality(): void
    {
        $rule = PermissionRule::factory()->create([
            'rule_type' => PermissionRule::TYPE_CONDITIONAL,
            'rule_config' => [
                'type' => 'conditional',
                'model' => 'App\\Models\\Post',
                'condition' => "status !== 'published'",
            ],
        ]);

        $model = (object) ['status' => 'draft'];

        $this->assertTrue($rule->evaluate($model));
    }

    /**
     * Test evaluateConditionalRule with greater than.
     */
    public function test_evaluate_conditional_rule_with_greater_than(): void
    {
        $rule = PermissionRule::factory()->create([
            'rule_type' => PermissionRule::TYPE_CONDITIONAL,
            'rule_config' => [
                'type' => 'conditional',
                'model' => 'App\\Models\\Post',
                'condition' => 'views > 100',
            ],
        ]);

        $model = (object) ['views' => 150];

        $this->assertTrue($rule->evaluate($model));
    }

    /**
     * Test evaluateConditionalRule with less than.
     */
    public function test_evaluate_conditional_rule_with_less_than(): void
    {
        $rule = PermissionRule::factory()->create([
            'rule_type' => PermissionRule::TYPE_CONDITIONAL,
            'rule_config' => [
                'type' => 'conditional',
                'model' => 'App\\Models\\Post',
                'condition' => 'views < 100',
            ],
        ]);

        $model = (object) ['views' => 50];

        $this->assertTrue($rule->evaluate($model));
    }

    /**
     * Test evaluateConditionalRule with greater than or equal.
     */
    public function test_evaluate_conditional_rule_with_greater_than_or_equal(): void
    {
        $rule = PermissionRule::factory()->create([
            'rule_type' => PermissionRule::TYPE_CONDITIONAL,
            'rule_config' => [
                'type' => 'conditional',
                'model' => 'App\\Models\\Post',
                'condition' => 'views >= 100',
            ],
        ]);

        $model = (object) ['views' => 100];

        $this->assertTrue($rule->evaluate($model));
    }

    /**
     * Test evaluateConditionalRule with less than or equal.
     */
    public function test_evaluate_conditional_rule_with_less_than_or_equal(): void
    {
        $rule = PermissionRule::factory()->create([
            'rule_type' => PermissionRule::TYPE_CONDITIONAL,
            'rule_config' => [
                'type' => 'conditional',
                'model' => 'App\\Models\\Post',
                'condition' => 'views <= 100',
            ],
        ]);

        $model = (object) ['views' => 100];

        $this->assertTrue($rule->evaluate($model));
    }

    /**
     * Test evaluateConditionalRule with AND operator.
     */
    public function test_evaluate_conditional_rule_with_and_operator(): void
    {
        $rule = PermissionRule::factory()->create([
            'rule_type' => PermissionRule::TYPE_CONDITIONAL,
            'rule_config' => [
                'type' => 'conditional',
                'model' => 'App\\Models\\Post',
                'condition' => "status === 'draft' AND views < 100",
            ],
        ]);

        $model = (object) [
            'status' => 'draft',
            'views' => 50,
        ];

        $this->assertTrue($rule->evaluate($model));
    }

    /**
     * Test evaluateConditionalRule with OR operator.
     */
    public function test_evaluate_conditional_rule_with_or_operator(): void
    {
        $rule = PermissionRule::factory()->create([
            'rule_type' => PermissionRule::TYPE_CONDITIONAL,
            'rule_config' => [
                'type' => 'conditional',
                'model' => 'App\\Models\\Post',
                'condition' => "status === 'draft' OR views > 1000",
            ],
        ]);

        $model = (object) [
            'status' => 'published',
            'views' => 1500,
        ];

        $this->assertTrue($rule->evaluate($model));
    }

    /**
     * Test evaluateConditionalRule with NOT operator.
     */
    public function test_evaluate_conditional_rule_with_not_operator(): void
    {
        $rule = PermissionRule::factory()->create([
            'rule_type' => PermissionRule::TYPE_CONDITIONAL,
            'rule_config' => [
                'type' => 'conditional',
                'model' => 'App\\Models\\Post',
                'condition' => "NOT status === 'published'",
            ],
        ]);

        $model = (object) ['status' => 'draft'];

        $this->assertTrue($rule->evaluate($model));
    }

    /**
     * Test evaluateConditionalRule with 'in' operator.
     */
    public function test_evaluate_conditional_rule_with_in_operator(): void
    {
        $rule = PermissionRule::factory()->create([
            'rule_type' => PermissionRule::TYPE_CONDITIONAL,
            'rule_config' => [
                'type' => 'conditional',
                'model' => 'App\\Models\\Post',
                'condition' => "status in ['draft', 'pending']",
            ],
        ]);

        $model = (object) ['status' => 'draft'];

        $this->assertTrue($rule->evaluate($model));
    }

    /**
     * Test evaluateConditionalRule with 'not_in' operator.
     */
    public function test_evaluate_conditional_rule_with_not_in_operator(): void
    {
        $rule = PermissionRule::factory()->create([
            'rule_type' => PermissionRule::TYPE_CONDITIONAL,
            'rule_config' => [
                'type' => 'conditional',
                'model' => 'App\\Models\\Post',
                'condition' => "status not_in ['published', 'archived']",
            ],
        ]);

        $model = (object) ['status' => 'draft'];

        $this->assertTrue($rule->evaluate($model));
    }

    /**
     * Test evaluateConditionalRule with empty condition.
     */
    public function test_evaluate_conditional_rule_with_empty_condition(): void
    {
        $rule = PermissionRule::factory()->create([
            'rule_type' => PermissionRule::TYPE_CONDITIONAL,
            'rule_config' => [
                'type' => 'conditional',
                'model' => 'App\\Models\\Post',
                'condition' => '',
            ],
        ]);

        $model = (object) [];

        $this->assertTrue($rule->evaluate($model));
    }

    /**
     * Test evaluateConditionalRule with boolean value.
     */
    public function test_evaluate_conditional_rule_with_boolean_value(): void
    {
        $rule = PermissionRule::factory()->create([
            'rule_type' => PermissionRule::TYPE_CONDITIONAL,
            'rule_config' => [
                'type' => 'conditional',
                'model' => 'App\\Models\\Post',
                'condition' => 'is_featured === true',
            ],
        ]);

        $model = (object) ['is_featured' => true];

        $this->assertTrue($rule->evaluate($model));
    }

    /**
     * Test evaluateConditionalRule with null value.
     */
    public function test_evaluate_conditional_rule_with_null_value(): void
    {
        $rule = PermissionRule::factory()->create([
            'rule_type' => PermissionRule::TYPE_CONDITIONAL,
            'rule_config' => [
                'type' => 'conditional',
                'model' => 'App\\Models\\Post',
                'condition' => 'deleted_at === null',
            ],
        ]);

        $model = (object) ['deleted_at' => null];

        $this->assertTrue($rule->evaluate($model));
    }

    /**
     * Test evaluateConditionalRule with numeric value.
     */
    public function test_evaluate_conditional_rule_with_numeric_value(): void
    {
        $rule = PermissionRule::factory()->create([
            'rule_type' => PermissionRule::TYPE_CONDITIONAL,
            'rule_config' => [
                'type' => 'conditional',
                'model' => 'App\\Models\\Post',
                'condition' => 'views === 100',
            ],
        ]);

        $model = (object) ['views' => 100];

        $this->assertTrue($rule->evaluate($model));
    }

    /**
     * Test evaluateConditionalRule with float value.
     */
    public function test_evaluate_conditional_rule_with_float_value(): void
    {
        $rule = PermissionRule::factory()->create([
            'rule_type' => PermissionRule::TYPE_CONDITIONAL,
            'rule_config' => [
                'type' => 'conditional',
                'model' => 'App\\Models\\Post',
                'condition' => 'rating >= 4.5',
            ],
        ]);

        $model = (object) ['rating' => 4.8];

        $this->assertTrue($rule->evaluate($model));
    }

    // ========================================
    // Edge Cases and Error Handling Tests
    // ========================================

    /**
     * Test evaluate with invalid rule type.
     */
    public function test_evaluate_with_invalid_rule_type(): void
    {
        $rule = PermissionRule::factory()->create([
            'rule_type' => 'invalid_type',
            'rule_config' => [],
        ]);

        $model = (object) [];

        $this->assertFalse($rule->evaluate($model));
    }

    /**
     * Test evaluateConditionalRule with multiple AND conditions.
     */
    public function test_evaluate_conditional_rule_with_multiple_and_conditions(): void
    {
        $rule = PermissionRule::factory()->create([
            'rule_type' => PermissionRule::TYPE_CONDITIONAL,
            'rule_config' => [
                'type' => 'conditional',
                'model' => 'App\\Models\\Post',
                'condition' => "status === 'draft' AND views < 100 AND is_featured === false",
            ],
        ]);

        // Test all conditions match
        $model1 = (object) [
            'status' => 'draft',
            'views' => 50,
            'is_featured' => false,
        ];
        $this->assertTrue($rule->evaluate($model1));

        // Test one condition doesn't match
        $model2 = (object) [
            'status' => 'draft',
            'views' => 150, // Doesn't match
            'is_featured' => false,
        ];
        $this->assertFalse($rule->evaluate($model2));
    }

    /**
     * Test evaluateConditionalRule with multiple OR conditions.
     */
    public function test_evaluate_conditional_rule_with_multiple_or_conditions(): void
    {
        $rule = PermissionRule::factory()->create([
            'rule_type' => PermissionRule::TYPE_CONDITIONAL,
            'rule_config' => [
                'type' => 'conditional',
                'model' => 'App\\Models\\Post',
                'condition' => "status === 'draft' OR status === 'pending' OR is_featured === true",
            ],
        ]);

        // Test first condition matches
        $model1 = (object) [
            'status' => 'draft',
            'is_featured' => false,
        ];
        $this->assertTrue($rule->evaluate($model1));

        // Test last condition matches
        $model2 = (object) [
            'status' => 'published',
            'is_featured' => true,
        ];
        $this->assertTrue($rule->evaluate($model2));

        // Test no conditions match
        $model3 = (object) [
            'status' => 'published',
            'is_featured' => false,
        ];
        $this->assertFalse($rule->evaluate($model3));
    }

    /**
     * Test matchesPath with exact match.
     */
    public function test_matches_path_with_exact_match(): void
    {
        $rule = PermissionRule::factory()->create([
            'rule_type' => PermissionRule::TYPE_JSON_ATTRIBUTE,
            'rule_config' => [
                'type' => 'json_attribute',
                'model' => 'App\\Models\\Post',
                'json_column' => 'metadata',
                'allowed_paths' => ['seo.title'],
            ],
        ]);

        $model = (object) [];

        $this->assertTrue($rule->evaluate($model, 'metadata.seo.title'));
        $this->assertFalse($rule->evaluate($model, 'metadata.seo.description'));
    }

    /**
     * Test matchesPath with wildcard at end.
     */
    public function test_matches_path_with_wildcard_at_end(): void
    {
        $rule = PermissionRule::factory()->create([
            'rule_type' => PermissionRule::TYPE_JSON_ATTRIBUTE,
            'rule_config' => [
                'type' => 'json_attribute',
                'model' => 'App\\Models\\Post',
                'json_column' => 'metadata',
                'allowed_paths' => ['seo.*'],
            ],
        ]);

        $model = (object) [];

        $this->assertTrue($rule->evaluate($model, 'metadata.seo.title'));
        $this->assertTrue($rule->evaluate($model, 'metadata.seo.description'));
        $this->assertTrue($rule->evaluate($model, 'metadata.seo.keywords'));
        $this->assertFalse($rule->evaluate($model, 'metadata.social.title'));
    }

    /**
     * Test matchesPath with wildcard matching prefix only.
     */
    public function test_matches_path_with_wildcard_matching_prefix_only(): void
    {
        $rule = PermissionRule::factory()->create([
            'rule_type' => PermissionRule::TYPE_JSON_ATTRIBUTE,
            'rule_config' => [
                'type' => 'json_attribute',
                'model' => 'App\\Models\\Post',
                'json_column' => 'metadata',
                'allowed_paths' => ['seo.*'],
            ],
        ]);

        $model = (object) [];

        // Should match the prefix itself
        $this->assertTrue($rule->evaluate($model, 'metadata.seo'));
    }

    /**
     * Test parseValue with string values.
     */
    public function test_parse_value_with_string_values(): void
    {
        $rule = PermissionRule::factory()->create([
            'rule_type' => PermissionRule::TYPE_CONDITIONAL,
            'rule_config' => [
                'type' => 'conditional',
                'model' => 'App\\Models\\Post',
                'condition' => "status === 'draft'",
            ],
        ]);

        $model = (object) ['status' => 'draft'];

        $this->assertTrue($rule->evaluate($model));
    }

    /**
     * Test parseValue with double quoted strings.
     */
    public function test_parse_value_with_double_quoted_strings(): void
    {
        $rule = PermissionRule::factory()->create([
            'rule_type' => PermissionRule::TYPE_CONDITIONAL,
            'rule_config' => [
                'type' => 'conditional',
                'model' => 'App\\Models\\Post',
                'condition' => 'status === "draft"',
            ],
        ]);

        $model = (object) ['status' => 'draft'];

        $this->assertTrue($rule->evaluate($model));
    }

    /**
     * Test parseValue with boolean true.
     */
    public function test_parse_value_with_boolean_true(): void
    {
        $rule = PermissionRule::factory()->create([
            'rule_type' => PermissionRule::TYPE_CONDITIONAL,
            'rule_config' => [
                'type' => 'conditional',
                'model' => 'App\\Models\\Post',
                'condition' => 'is_active === true',
            ],
        ]);

        $model = (object) ['is_active' => true];

        $this->assertTrue($rule->evaluate($model));
    }

    /**
     * Test parseValue with boolean false.
     */
    public function test_parse_value_with_boolean_false(): void
    {
        $rule = PermissionRule::factory()->create([
            'rule_type' => PermissionRule::TYPE_CONDITIONAL,
            'rule_config' => [
                'type' => 'conditional',
                'model' => 'App\\Models\\Post',
                'condition' => 'is_active === false',
            ],
        ]);

        $model = (object) ['is_active' => false];

        $this->assertTrue($rule->evaluate($model));
    }

    /**
     * Test parseValue with integer.
     */
    public function test_parse_value_with_integer(): void
    {
        $rule = PermissionRule::factory()->create([
            'rule_type' => PermissionRule::TYPE_CONDITIONAL,
            'rule_config' => [
                'type' => 'conditional',
                'model' => 'App\\Models\\Post',
                'condition' => 'views === 100',
            ],
        ]);

        $model = (object) ['views' => 100];

        $this->assertTrue($rule->evaluate($model));
    }

    /**
     * Test parseValue with float.
     */
    public function test_parse_value_with_float(): void
    {
        $rule = PermissionRule::factory()->create([
            'rule_type' => PermissionRule::TYPE_CONDITIONAL,
            'rule_config' => [
                'type' => 'conditional',
                'model' => 'App\\Models\\Post',
                'condition' => 'rating === 4.5',
            ],
        ]);

        $model = (object) ['rating' => 4.5];

        $this->assertTrue($rule->evaluate($model));
    }

    /**
     * Test parseValue with array for 'in' operator.
     */
    public function test_parse_value_with_array_for_in_operator(): void
    {
        $rule = PermissionRule::factory()->create([
            'rule_type' => PermissionRule::TYPE_CONDITIONAL,
            'rule_config' => [
                'type' => 'conditional',
                'model' => 'App\\Models\\Post',
                'condition' => "status in ['draft', 'pending', 'review']",
            ],
        ]);

        $model = (object) ['status' => 'pending'];

        $this->assertTrue($rule->evaluate($model));
    }
}
