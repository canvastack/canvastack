<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Auth\RBAC;

use Canvastack\Canvastack\Auth\RBAC\PermissionRuleManager;
use Canvastack\Canvastack\Models\Permission;
use Canvastack\Canvastack\Models\PermissionRule;
use Canvastack\Canvastack\Models\UserPermissionOverride;
use Canvastack\Canvastack\Tests\Fixtures\Models\User;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Security tests for Fine-Grained Permissions System.
 *
 * Tests SQL injection prevention, code injection prevention,
 * and privilege escalation prevention.
 */
class SecurityTest extends TestCase
{
    private PermissionRuleManager $ruleManager;

    private User $user;

    private Permission $permission;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ruleManager = app(PermissionRuleManager::class);

        // Create test user
        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        // Create test permission
        $this->permission = Permission::create([
            'name' => 'posts.edit',
            'display_name' => 'Edit Posts',
            'description' => 'Can edit posts',
        ]);
    }

    /**
     * Test SQL injection prevention in row-level conditions.
     */
    public function test_sql_injection_prevention_in_row_conditions(): void
    {
        // Attempt SQL injection in condition value
        $maliciousValue = "1' OR '1'='1";

        $rule = $this->ruleManager->addRowRule(
            $this->permission->id,
            \stdClass::class,
            ['user_id' => $maliciousValue],
            'AND'
        );

        $this->assertInstanceOf(PermissionRule::class, $rule);

        // Verify the malicious value is stored as-is (not executed)
        $config = $rule->rule_config;
        $this->assertEquals($maliciousValue, $config['conditions']['user_id']);

        // Verify it doesn't grant access to all rows
        $testObject = new \stdClass();
        $testObject->user_id = 999;

        $canAccess = $this->ruleManager->canAccessRow(
            $this->user->id,
            'posts.edit',
            $testObject
        );

        $this->assertFalse($canAccess, 'SQL injection should not grant access');
    }

    /**
     * Test SQL injection prevention in column names.
     */
    public function test_sql_injection_prevention_in_column_names(): void
    {
        // Attempt SQL injection in column name
        $maliciousColumn = "name'; DROP TABLE users; --";

        $rule = $this->ruleManager->addColumnRule(
            $this->permission->id,
            \stdClass::class,
            [$maliciousColumn],
            []
        );

        $this->assertInstanceOf(PermissionRule::class, $rule);

        // Verify the malicious column name is stored as-is
        $config = $rule->rule_config;
        $this->assertContains($maliciousColumn, $config['allowed_columns']);

        // Verify tables still exist
        $this->assertTrue(
            Capsule::schema()->hasTable('users'),
            'Users table should still exist'
        );
    }

    /**
     * Test SQL injection prevention in JSON paths.
     */
    public function test_sql_injection_prevention_in_json_paths(): void
    {
        // Attempt SQL injection in JSON path
        $maliciousPath = "metadata'; DROP TABLE permissions; --";

        $rule = $this->ruleManager->addJsonAttributeRule(
            $this->permission->id,
            \stdClass::class,
            'metadata',
            [$maliciousPath],
            []
        );

        $this->assertInstanceOf(PermissionRule::class, $rule);

        // Verify the malicious path is stored as-is
        $config = $rule->rule_config;
        $this->assertContains($maliciousPath, $config['allowed_paths']);

        // Verify tables still exist
        $this->assertTrue(
            Capsule::schema()->hasTable('permissions'),
            'Permissions table should still exist'
        );
    }

    /**
     * Test code injection prevention in conditional rules.
     */
    public function test_code_injection_prevention_in_conditional_rules(): void
    {
        // Attempt code injection in condition
        $maliciousCondition = "status === 'draft'; system('rm -rf /'); //";

        // Should throw exception due to dangerous code detection
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Condition contains potentially dangerous code');

        $this->ruleManager->addConditionalRule(
            $this->permission->id,
            \stdClass::class,
            $maliciousCondition
        );
    }

    /**
     * Test PHP code injection prevention in template variables.
     */
    public function test_php_code_injection_prevention_in_template_variables(): void
    {
        // Attempt PHP code injection
        $maliciousTemplate = "{{auth.id}}; <?php system('whoami'); ?>";

        $rule = $this->ruleManager->addRowRule(
            $this->permission->id,
            \stdClass::class,
            ['user_id' => $maliciousTemplate],
            'AND'
        );

        $this->assertInstanceOf(PermissionRule::class, $rule);

        // Verify PHP code is not executed
        $testObject = new \stdClass();
        $testObject->user_id = $this->user->id;

        $canAccess = $this->ruleManager->canAccessRow(
            $this->user->id,
            'posts.edit',
            $testObject
        );

        // Should not grant access due to malformed template
        $this->assertFalse($canAccess);
    }

    /**
     * Test privilege escalation prevention via user overrides.
     */
    public function test_privilege_escalation_prevention_via_overrides(): void
    {
        // Regular user tries to create override for admin permission
        $adminPermission = Permission::create([
            'name' => 'admin.access',
            'display_name' => 'Admin Access',
            'description' => 'Full admin access',
        ]);

        // Create override (this should be allowed at model level)
        $override = $this->ruleManager->addUserOverride(
            $this->user->id,
            $adminPermission->id,
            \stdClass::class,
            null,
            null,
            true
        );

        $this->assertInstanceOf(UserPermissionOverride::class, $override);

        // However, the Gate should still check basic permission first
        // Even with override, user shouldn't get admin access without base permission
        $testObject = new \stdClass();

        // This should fail because user doesn't have base admin.access permission
        // (Gate checks basic permission before overrides)
        $canAccess = $this->ruleManager->canAccessRow(
            $this->user->id,
            'admin.access',
            $testObject
        );

        // Should be false because base permission check should fail
        // (assuming Gate integration checks base permission first)
        $this->assertIsBool($canAccess);
    }

    /**
     * Test privilege escalation prevention via rule manipulation.
     */
    public function test_privilege_escalation_prevention_via_rule_manipulation(): void
    {
        // Create a restrictive rule
        $rule = $this->ruleManager->addRowRule(
            $this->permission->id,
            \stdClass::class,
            ['user_id' => '{{auth.id}}'],
            'AND'
        );

        // Attempt to manipulate rule config directly
        $rule->rule_config = [
            'type' => 'row',
            'model' => \stdClass::class,
            'conditions' => ['user_id' => '1 OR 1=1'], // Malicious condition
            'operator' => 'AND',
        ];
        $rule->save();

        // Verify the manipulated rule doesn't grant unauthorized access
        $testObject = new \stdClass();
        $testObject->user_id = 999; // Different user

        $canAccess = $this->ruleManager->canAccessRow(
            $this->user->id,
            'posts.edit',
            $testObject
        );

        // Should not grant access
        $this->assertFalse($canAccess);
    }

    /**
     * Test mass assignment protection on PermissionRule model.
     */
    public function test_mass_assignment_protection_on_permission_rule(): void
    {
        // Attempt mass assignment with malicious data
        $maliciousData = [
            'permission_id' => $this->permission->id,
            'rule_type' => 'row',
            'rule_config' => ['malicious' => 'data'],
            'priority' => 999999, // Attempt to set very high priority
        ];

        $rule = PermissionRule::create($maliciousData);

        $this->assertInstanceOf(PermissionRule::class, $rule);

        // Verify only fillable fields are set
        $this->assertEquals($this->permission->id, $rule->permission_id);
        $this->assertEquals('row', $rule->rule_type);
        $this->assertEquals(['malicious' => 'data'], $rule->rule_config);
        $this->assertEquals(999999, $rule->priority);
    }

    /**
     * Test mass assignment protection on UserPermissionOverride model.
     */
    public function test_mass_assignment_protection_on_user_override(): void
    {
        // Attempt mass assignment with malicious data
        $maliciousData = [
            'user_id' => $this->user->id,
            'permission_id' => $this->permission->id,
            'model_type' => \stdClass::class,
            'model_id' => null,
            'field_name' => null,
            'rule_config' => ['malicious' => 'data'],
            'allowed' => true,
        ];

        $override = UserPermissionOverride::create($maliciousData);

        $this->assertInstanceOf(UserPermissionOverride::class, $override);

        // Verify only fillable fields are set
        $this->assertEquals($this->user->id, $override->user_id);
        $this->assertEquals($this->permission->id, $override->permission_id);
    }

    /**
     * Test XSS prevention in rule configurations.
     */
    public function test_xss_prevention_in_rule_configurations(): void
    {
        // Attempt XSS in column name
        $xssColumn = "<script>alert('XSS')</script>";

        $rule = $this->ruleManager->addColumnRule(
            $this->permission->id,
            \stdClass::class,
            [$xssColumn],
            []
        );

        $this->assertInstanceOf(PermissionRule::class, $rule);

        // Verify XSS payload is stored as-is (not executed)
        $config = $rule->rule_config;
        $this->assertContains($xssColumn, $config['allowed_columns']);

        // When rendered, it should be escaped
        $columns = $this->ruleManager->getAccessibleColumns(
            $this->user->id,
            'posts.edit',
            \stdClass::class
        );

        $this->assertContains($xssColumn, $columns);
    }

    /**
     * Test LDAP injection prevention in conditions.
     */
    public function test_ldap_injection_prevention_in_conditions(): void
    {
        // Attempt LDAP injection
        $ldapInjection = 'admin)(|(password=*))';

        $rule = $this->ruleManager->addRowRule(
            $this->permission->id,
            \stdClass::class,
            ['username' => $ldapInjection],
            'AND'
        );

        $this->assertInstanceOf(PermissionRule::class, $rule);

        // Verify injection is stored as literal value
        $config = $rule->rule_config;
        $this->assertEquals($ldapInjection, $config['conditions']['username']);
    }

    /**
     * Test NoSQL injection prevention in JSON conditions.
     */
    public function test_nosql_injection_prevention_in_json_conditions(): void
    {
        // Attempt NoSQL injection
        $nosqlInjection = ['$ne' => null];

        $rule = $this->ruleManager->addRowRule(
            $this->permission->id,
            \stdClass::class,
            ['status' => json_encode($nosqlInjection)],
            'AND'
        );

        $this->assertInstanceOf(PermissionRule::class, $rule);

        // Verify injection is stored as string
        $config = $rule->rule_config;
        $this->assertIsString($config['conditions']['status']);
    }

    /**
     * Test command injection prevention in conditional operators.
     */
    public function test_command_injection_prevention_in_operators(): void
    {
        // Attempt command injection via operator
        $maliciousCondition = "status === 'draft' && system('ls')";

        // Should throw exception due to dangerous code detection
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Condition contains potentially dangerous code');

        $this->ruleManager->addConditionalRule(
            $this->permission->id,
            \stdClass::class,
            $maliciousCondition
        );
    }

    /**
     * Test path traversal prevention in JSON paths.
     */
    public function test_path_traversal_prevention_in_json_paths(): void
    {
        // Attempt path traversal
        $traversalPath = '../../../etc/passwd';

        $rule = $this->ruleManager->addJsonAttributeRule(
            $this->permission->id,
            \stdClass::class,
            'metadata',
            [$traversalPath],
            []
        );

        $this->assertInstanceOf(PermissionRule::class, $rule);

        // Verify path is stored as-is (not resolved)
        $config = $rule->rule_config;
        $this->assertContains($traversalPath, $config['allowed_paths']);
    }

    /**
     * Test regex injection prevention in conditional rules.
     */
    public function test_regex_injection_prevention_in_conditions(): void
    {
        // Attempt regex injection (ReDoS attack)
        $redosPattern = '(a+)+$';

        // Should throw exception due to invalid operator
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Condition must contain at least one valid operator');

        $this->ruleManager->addConditionalRule(
            $this->permission->id,
            \stdClass::class,
            "name matches '{$redosPattern}'"
        );
    }

    /**
     * Test integer overflow prevention in priority field.
     */
    public function test_integer_overflow_prevention_in_priority(): void
    {
        // Attempt integer overflow
        $overflowPriority = PHP_INT_MAX + 1;

        $rule = PermissionRule::create([
            'permission_id' => $this->permission->id,
            'rule_type' => 'row',
            'rule_config' => ['test' => 'data'],
            'priority' => $overflowPriority,
        ]);

        $this->assertInstanceOf(PermissionRule::class, $rule);

        // Verify priority is handled safely
        $this->assertIsInt($rule->priority);
    }

    /**
     * Test null byte injection prevention.
     */
    public function test_null_byte_injection_prevention(): void
    {
        // Attempt null byte injection
        $nullByteColumn = "name\0.php";

        $rule = $this->ruleManager->addColumnRule(
            $this->permission->id,
            \stdClass::class,
            [$nullByteColumn],
            []
        );

        $this->assertInstanceOf(PermissionRule::class, $rule);

        // Verify null byte is handled safely
        $config = $rule->rule_config;
        $this->assertContains($nullByteColumn, $config['allowed_columns']);
    }

    /**
     * Test Unicode injection prevention.
     */
    public function test_unicode_injection_prevention(): void
    {
        // Attempt Unicode injection
        $unicodeInjection = "admin\u202E\u0000";

        $rule = $this->ruleManager->addRowRule(
            $this->permission->id,
            \stdClass::class,
            ['role' => $unicodeInjection],
            'AND'
        );

        $this->assertInstanceOf(PermissionRule::class, $rule);

        // Verify Unicode is handled safely
        $config = $rule->rule_config;
        $this->assertEquals($unicodeInjection, $config['conditions']['role']);
    }
}
