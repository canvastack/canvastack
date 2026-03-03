<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Auth\RBAC;

use Canvastack\Canvastack\Auth\RBAC\PermissionManager;
use Canvastack\Canvastack\Auth\RBAC\PermissionRuleManager;
use Canvastack\Canvastack\Auth\RBAC\RoleManager;
use Canvastack\Canvastack\Auth\RBAC\TemplateVariableResolver;
use Canvastack\Canvastack\Models\Permission;
use Canvastack\Canvastack\Models\PermissionRule;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Support\Facades\Cache;

/**
 * Test for conditional permission rules.
 */
class ConditionalRuleTest extends TestCase
{
    protected PermissionRuleManager $ruleManager;

    protected PermissionManager $permissionManager;

    protected RoleManager $roleManager;

    protected TemplateVariableResolver $templateResolver;

    /**
     * Setup test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create managers
        $this->roleManager = new RoleManager();
        $this->permissionManager = new PermissionManager($this->roleManager);
        $this->templateResolver = new TemplateVariableResolver();
        $this->ruleManager = new PermissionRuleManager(
            $this->roleManager,
            $this->permissionManager,
            $this->templateResolver
        );

        // Clear cache
        Cache::flush();
    }

    /**
     * Test that conditional rule can be added.
     */
    public function test_conditional_rule_can_be_added(): void
    {
        // Create permission
        $permission = Permission::create([
            'name' => 'posts.edit',
            'display_name' => 'Edit Posts',
            'description' => 'Edit posts',
        ]);

        // Add conditional rule
        $rule = $this->ruleManager->addConditionalRule(
            $permission->id,
            'App\\Models\\Post',
            "status === 'draft' AND user_id === {{auth.id}}"
        );

        // Assert rule was created
        $this->assertInstanceOf(PermissionRule::class, $rule);
        $this->assertEquals('conditional', $rule->rule_type);
        $this->assertEquals('App\\Models\\Post', $rule->rule_config['model']);
        $this->assertEquals("status === 'draft' AND user_id === {{auth.id}}", $rule->rule_config['condition']);
    }

    /**
     * Test that empty condition throws exception.
     */
    public function test_empty_condition_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Condition cannot be empty');

        $permission = Permission::create([
            'name' => 'posts.edit',
            'display_name' => 'Edit Posts',
            'description' => 'Edit posts',
        ]);

        $this->ruleManager->addConditionalRule(
            $permission->id,
            'App\\Models\\Post',
            ''
        );
    }

    /**
     * Test that dangerous code in condition throws exception.
     */
    public function test_dangerous_code_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Condition contains potentially dangerous code');

        $permission = Permission::create([
            'name' => 'posts.edit',
            'display_name' => 'Edit Posts',
            'description' => 'Edit posts',
        ]);

        $this->ruleManager->addConditionalRule(
            $permission->id,
            'App\\Models\\Post',
            'eval("malicious code")'
        );
    }

    /**
     * Test that simple equality condition evaluates correctly.
     */
    public function test_simple_equality_condition(): void
    {
        // Create test model
        $model = new class () {
            public string $status = 'draft';
        };

        // Use reflection to test protected method
        $reflection = new \ReflectionClass($this->ruleManager);
        $method = $reflection->getMethod('evaluateCondition');
        $method->setAccessible(true);

        // Test equality
        $result = $method->invoke($this->ruleManager, "status === 'draft'", $model);
        $this->assertTrue($result);

        // Test inequality
        $result = $method->invoke($this->ruleManager, "status === 'published'", $model);
        $this->assertFalse($result);
    }

    /**
     * Test that strict inequality condition evaluates correctly.
     */
    public function test_strict_inequality_condition(): void
    {
        $model = new class () {
            public string $status = 'draft';
        };

        $reflection = new \ReflectionClass($this->ruleManager);
        $method = $reflection->getMethod('evaluateCondition');
        $method->setAccessible(true);

        // Test not equal
        $result = $method->invoke($this->ruleManager, "status !== 'published'", $model);
        $this->assertTrue($result);

        $result = $method->invoke($this->ruleManager, "status !== 'draft'", $model);
        $this->assertFalse($result);
    }

    /**
     * Test that numeric comparison conditions evaluate correctly.
     */
    public function test_numeric_comparison_conditions(): void
    {
        $model = new class () {
            public int $views = 100;
        };

        $reflection = new \ReflectionClass($this->ruleManager);
        $method = $reflection->getMethod('evaluateCondition');
        $method->setAccessible(true);

        // Greater than
        $result = $method->invoke($this->ruleManager, 'views > 50', $model);
        $this->assertTrue($result);

        $result = $method->invoke($this->ruleManager, 'views > 150', $model);
        $this->assertFalse($result);

        // Less than
        $result = $method->invoke($this->ruleManager, 'views < 150', $model);
        $this->assertTrue($result);

        $result = $method->invoke($this->ruleManager, 'views < 50', $model);
        $this->assertFalse($result);

        // Greater than or equal
        $result = $method->invoke($this->ruleManager, 'views >= 100', $model);
        $this->assertTrue($result);

        $result = $method->invoke($this->ruleManager, 'views >= 101', $model);
        $this->assertFalse($result);

        // Less than or equal
        $result = $method->invoke($this->ruleManager, 'views <= 100', $model);
        $this->assertTrue($result);

        $result = $method->invoke($this->ruleManager, 'views <= 99', $model);
        $this->assertFalse($result);
    }

    /**
     * Test that AND condition evaluates correctly.
     */
    public function test_and_condition(): void
    {
        $model = new class () {
            public string $status = 'draft';

            public int $user_id = 1;
        };

        $reflection = new \ReflectionClass($this->ruleManager);
        $method = $reflection->getMethod('evaluateCondition');
        $method->setAccessible(true);

        // Both conditions true
        $result = $method->invoke($this->ruleManager, "status === 'draft' AND user_id === 1", $model);
        $this->assertTrue($result);

        // First condition false
        $result = $method->invoke($this->ruleManager, "status === 'published' AND user_id === 1", $model);
        $this->assertFalse($result);

        // Second condition false
        $result = $method->invoke($this->ruleManager, "status === 'draft' AND user_id === 2", $model);
        $this->assertFalse($result);

        // Both conditions false
        $result = $method->invoke($this->ruleManager, "status === 'published' AND user_id === 2", $model);
        $this->assertFalse($result);
    }

    /**
     * Test that OR condition evaluates correctly.
     */
    public function test_or_condition(): void
    {
        $model = new class () {
            public string $status = 'draft';

            public int $user_id = 1;
        };

        $reflection = new \ReflectionClass($this->ruleManager);
        $method = $reflection->getMethod('evaluateCondition');
        $method->setAccessible(true);

        // Both conditions true
        $result = $method->invoke($this->ruleManager, "status === 'draft' OR user_id === 1", $model);
        $this->assertTrue($result);

        // First condition true
        $result = $method->invoke($this->ruleManager, "status === 'draft' OR user_id === 2", $model);
        $this->assertTrue($result);

        // Second condition true
        $result = $method->invoke($this->ruleManager, "status === 'published' OR user_id === 1", $model);
        $this->assertTrue($result);

        // Both conditions false
        $result = $method->invoke($this->ruleManager, "status === 'published' OR user_id === 2", $model);
        $this->assertFalse($result);
    }

    /**
     * Test that NOT condition evaluates correctly.
     */
    public function test_not_condition(): void
    {
        $model = new class () {
            public string $status = 'draft';
        };

        $reflection = new \ReflectionClass($this->ruleManager);
        $method = $reflection->getMethod('evaluateCondition');
        $method->setAccessible(true);

        // NOT with true condition
        $result = $method->invoke($this->ruleManager, "NOT status === 'published'", $model);
        $this->assertTrue($result);

        // NOT with false condition
        $result = $method->invoke($this->ruleManager, "NOT status === 'draft'", $model);
        $this->assertFalse($result);
    }

    /**
     * Test that IN operator evaluates correctly.
     */
    public function test_in_operator(): void
    {
        $model = new class () {
            public string $status = 'draft';
        };

        $reflection = new \ReflectionClass($this->ruleManager);
        $method = $reflection->getMethod('evaluateCondition');
        $method->setAccessible(true);

        // Value in array
        $result = $method->invoke($this->ruleManager, "status in ['draft', 'pending', 'review']", $model);
        $this->assertTrue($result);

        // Value not in array
        $result = $method->invoke($this->ruleManager, "status in ['published', 'archived']", $model);
        $this->assertFalse($result);
    }

    /**
     * Test that NOT_IN operator evaluates correctly.
     */
    public function test_not_in_operator(): void
    {
        $model = new class () {
            public string $status = 'draft';
        };

        $reflection = new \ReflectionClass($this->ruleManager);
        $method = $reflection->getMethod('evaluateCondition');
        $method->setAccessible(true);

        // Value not in array
        $result = $method->invoke($this->ruleManager, "status not_in ['published', 'archived']", $model);
        $this->assertTrue($result);

        // Value in array
        $result = $method->invoke($this->ruleManager, "status not_in ['draft', 'pending']", $model);
        $this->assertFalse($result);
    }

    /**
     * Test that complex condition with multiple operators evaluates correctly.
     */
    public function test_complex_condition(): void
    {
        $model = new class () {
            public string $status = 'draft';

            public int $user_id = 1;

            public int $views = 100;
        };

        $reflection = new \ReflectionClass($this->ruleManager);
        $method = $reflection->getMethod('evaluateCondition');
        $method->setAccessible(true);

        // Complex condition: (status === 'draft' AND user_id === 1) OR views > 1000
        $result = $method->invoke(
            $this->ruleManager,
            "status === 'draft' AND user_id === 1 OR views > 1000",
            $model
        );
        $this->assertTrue($result); // First part is true

        // Change status to make first part false
        $model->status = 'published';
        $result = $method->invoke(
            $this->ruleManager,
            "status === 'draft' AND user_id === 1 OR views > 1000",
            $model
        );
        $this->assertFalse($result); // Both parts are false

        // Increase views to make second part true
        $model->views = 1500;
        $result = $method->invoke(
            $this->ruleManager,
            "status === 'draft' AND user_id === 1 OR views > 1000",
            $model
        );
        $this->assertTrue($result); // Second part is true
    }

    /**
     * Test that boolean field check evaluates correctly.
     */
    public function test_boolean_field_check(): void
    {
        $model = new class () {
            public bool $is_active = true;

            public bool $is_deleted = false;
        };

        $reflection = new \ReflectionClass($this->ruleManager);
        $method = $reflection->getMethod('evaluateCondition');
        $method->setAccessible(true);

        // True boolean field
        $result = $method->invoke($this->ruleManager, 'is_active', $model);
        $this->assertTrue($result);

        // False boolean field
        $result = $method->invoke($this->ruleManager, 'is_deleted', $model);
        $this->assertFalse($result);
    }

    /**
     * Test that relationship field access works.
     */
    public function test_relationship_field_access(): void
    {
        $model = new class () {
            public object $user;

            public function __construct()
            {
                $this->user = new class () {
                    public int $id = 1;

                    public string $role = 'admin';
                };
            }
        };

        $reflection = new \ReflectionClass($this->ruleManager);
        $method = $reflection->getMethod('evaluateCondition');
        $method->setAccessible(true);

        // Access relationship field
        $result = $method->invoke($this->ruleManager, 'user.role === "admin"', $model);
        $this->assertTrue($result);

        $result = $method->invoke($this->ruleManager, 'user.id === 1', $model);
        $this->assertTrue($result);
    }

    /**
     * Test that null value comparison works.
     */
    public function test_null_value_comparison(): void
    {
        $model = new class () {
            public ?string $deleted_at = null;
        };

        $reflection = new \ReflectionClass($this->ruleManager);
        $method = $reflection->getMethod('evaluateCondition');
        $method->setAccessible(true);

        // Check null value
        $result = $method->invoke($this->ruleManager, 'deleted_at === null', $model);
        $this->assertTrue($result);

        // Check not null
        $result = $method->invoke($this->ruleManager, 'deleted_at !== null', $model);
        $this->assertFalse($result);
    }

    /**
     * Test that condition evaluation fails safely on error.
     */
    public function test_condition_evaluation_fails_safely(): void
    {
        $model = new class () {
            public string $status = 'draft';
        };

        $reflection = new \ReflectionClass($this->ruleManager);
        $method = $reflection->getMethod('evaluateCondition');
        $method->setAccessible(true);

        // Invalid field access should return false (fail-safe)
        $result = $method->invoke($this->ruleManager, 'nonexistent_field === "value"', $model);
        $this->assertFalse($result);
    }

    /**
     * Test that template variables are resolved in conditions.
     */
    public function test_template_variables_are_resolved(): void
    {
        // Skip this test for now - requires full Laravel auth setup
        $this->markTestSkipped('Requires full Laravel authentication setup');
    }

    /**
     * Test that validation prevents SQL injection.
     */
    public function test_validation_prevents_sql_injection(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $permission = Permission::create([
            'name' => 'posts.edit',
            'display_name' => 'Edit Posts',
            'description' => 'Edit posts',
        ]);

        $this->ruleManager->addConditionalRule(
            $permission->id,
            'App\\Models\\Post',
            "status === 'draft'; DROP TABLE users; --"
        );
    }

    /**
     * Test that validation prevents code execution.
     */
    public function test_validation_prevents_code_execution(): void
    {
        $permission = Permission::create([
            'name' => 'posts.edit',
            'display_name' => 'Edit Posts',
            'description' => 'Edit posts',
        ]);

        $dangerousCodes = [
            'eval("malicious")',
            'exec("rm -rf /")',
            'system("cat /etc/passwd")',
            'shell_exec("ls")',
            '`whoami`',
            'include("malicious.php")',
            'require("malicious.php")',
            '$var = "value"',
        ];

        foreach ($dangerousCodes as $code) {
            try {
                $this->ruleManager->addConditionalRule(
                    $permission->id,
                    'App\\Models\\Post',
                    $code
                );
                $this->fail("Expected exception for dangerous code: {$code}");
            } catch (\InvalidArgumentException $e) {
                $this->assertStringContainsString('dangerous', $e->getMessage());
            }
        }
    }
}
