<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Auth\RBAC;

use Canvastack\Canvastack\Auth\RBAC\TemplateVariableResolver;
use Canvastack\Canvastack\Tests\Fixtures\User;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Support\Facades\Auth;

/**
 * Test for TemplateVariableResolver.
 */
class TemplateVariableResolverTest extends TestCase
{
    protected TemplateVariableResolver $resolver;

    protected static $authGuard = null;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup auth guard mock ONCE for all tests
        if (self::$authGuard === null) {
            self::$authGuard = new class () {
                protected $user = null;

                public function user()
                {
                    return $this->user;
                }

                public function id()
                {
                    return $this->user?->id;
                }

                public function setUser($user)
                {
                    $this->user = $user;
                }

                public function check()
                {
                    return $this->user !== null;
                }
            };
        }

        // Bind to container
        $app = \Illuminate\Container\Container::getInstance();
        $app->singleton('auth', function () {
            return self::$authGuard;
        });

        // Create resolver AFTER auth guard is set up
        $this->resolver = new TemplateVariableResolver();
    }

    /**
     * Set authenticated user.
     */
    protected function actingAs(User $user): void
    {
        self::$authGuard->setUser($user);
    }

    /**
     * Create a test user.
     */
    protected function createUser(array $attributes = []): User
    {
        // Set defaults
        $defaults = [
            'id' => 1,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ];

        $data = array_merge($defaults, $attributes);

        // Create user
        $user = new User();
        $user->incrementing = false; // Disable auto-increment
        $user->exists = true; // Mark as existing

        // Force fill all attributes (bypasses fillable)
        $user->forceFill($data);

        $user->syncOriginal();

        return $user;
    }

    /**
     * Test that default variables are registered.
     *
     * @return void
     */
    public function test_default_variables_are_registered(): void
    {
        $this->assertTrue($this->resolver->has('auth.id'));
        $this->assertTrue($this->resolver->has('auth.role'));
        $this->assertTrue($this->resolver->has('auth.department'));
        $this->assertTrue($this->resolver->has('auth.email'));
        $this->assertTrue($this->resolver->has('auth.name'));

        $this->assertTrue($this->resolver->has('user.id'));
        $this->assertTrue($this->resolver->has('user.role'));
        $this->assertTrue($this->resolver->has('user.department'));
        $this->assertTrue($this->resolver->has('user.email'));
        $this->assertTrue($this->resolver->has('user.name'));

        $this->assertTrue($this->resolver->has('now'));
        $this->assertTrue($this->resolver->has('today'));
        $this->assertTrue($this->resolver->has('year'));
        $this->assertTrue($this->resolver->has('month'));
        $this->assertTrue($this->resolver->has('day'));
    }

    /**
     * Test that custom variables can be registered.
     *
     * @return void
     */
    public function test_custom_variables_can_be_registered(): void
    {
        $this->resolver->register('custom.var', fn () => 'custom_value');

        $this->assertTrue($this->resolver->has('custom.var'));
    }

    /**
     * Test that variables can be unregistered.
     *
     * @return void
     */
    public function test_variables_can_be_unregistered(): void
    {
        $this->resolver->register('temp.var', fn () => 'temp_value');
        $this->assertTrue($this->resolver->has('temp.var'));

        $this->resolver->unregister('temp.var');
        $this->assertFalse($this->resolver->has('temp.var'));
    }

    /**
     * Test that all variables can be retrieved.
     *
     * @return void
     */
    public function test_all_variables_can_be_retrieved(): void
    {
        $variables = $this->resolver->all();

        $this->assertIsArray($variables);
        $this->assertArrayHasKey('auth.id', $variables);
        $this->assertArrayHasKey('user.id', $variables);
        $this->assertArrayHasKey('now', $variables);
    }

    /**
     * Test that simple template variables are resolved.
     *
     * @return void
     */
    public function test_simple_template_variables_are_resolved(): void
    {
        // Create authenticated user
        $user = $this->createUser([
            'id' => 123,
            'role' => 'admin',
            'department_id' => 456,
            'email' => 'test@example.com',
            'name' => 'Test User',
        ]);

        $this->actingAs($user);

        // Test auth.id
        $result = $this->resolver->resolve('{{auth.id}}');
        $this->assertEquals(123, $result);

        // Test auth.role
        $result = $this->resolver->resolve('{{auth.role}}');
        $this->assertEquals('admin', $result);

        // Test auth.department
        $result = $this->resolver->resolve('{{auth.department}}');
        $this->assertEquals(456, $result);

        // Test auth.email
        $result = $this->resolver->resolve('{{auth.email}}');
        $this->assertEquals('test@example.com', $result);

        // Test auth.name
        $result = $this->resolver->resolve('{{auth.name}}');
        $this->assertEquals('Test User', $result);
    }

    /**
     * Test that non-template strings are returned unchanged.
     *
     * @return void
     */
    public function test_non_template_strings_are_returned_unchanged(): void
    {
        $result = $this->resolver->resolve('plain_string');
        $this->assertEquals('plain_string', $result);

        $result = $this->resolver->resolve('123');
        $this->assertEquals('123', $result);

        $result = $this->resolver->resolve('');
        $this->assertEquals('', $result);
    }

    /**
     * Test that non-string values are returned unchanged.
     *
     * @return void
     */
    public function test_non_string_values_are_returned_unchanged(): void
    {
        $result = $this->resolver->resolve(123);
        $this->assertEquals(123, $result);

        $result = $this->resolver->resolve(true);
        $this->assertTrue($result);

        $result = $this->resolver->resolve(null);
        $this->assertNull($result);

        $result = $this->resolver->resolve(['array']);
        $this->assertEquals(['array'], $result);
    }

    /**
     * Test that unregistered variables are returned unchanged.
     *
     * @return void
     */
    public function test_unregistered_variables_are_returned_unchanged(): void
    {
        $result = $this->resolver->resolve('{{unknown.var}}');
        $this->assertEquals('{{unknown.var}}', $result);
    }

    /**
     * Test that multiple variables in a string are resolved.
     *
     * @return void
     */
    public function test_multiple_variables_in_string_are_resolved(): void
    {
        $user = $this->createUser([
            'id' => 123,
            'name' => 'John Doe',
        ]);

        $this->actingAs($user);

        $result = $this->resolver->resolve('User {{auth.id}} is {{auth.name}}');
        $this->assertEquals('User 123 is John Doe', $result);
    }

    /**
     * Test that conditions array is resolved.
     *
     * @return void
     */
    public function test_conditions_array_is_resolved(): void
    {
        $user = $this->createUser([
            'id' => 123,
            'department_id' => 456,
        ]);

        $this->actingAs($user);

        $conditions = [
            'user_id' => '{{auth.id}}',
            'department_id' => '{{auth.department}}',
            'status' => 'active',
        ];

        $resolved = $this->resolver->resolveConditions($conditions);

        $this->assertEquals([
            'user_id' => 123,
            'department_id' => 456,
            'status' => 'active',
        ], $resolved);
    }

    /**
     * Test that multiple values are resolved.
     *
     * @return void
     */
    public function test_multiple_values_are_resolved(): void
    {
        $user = $this->createUser([
            'id' => 123,
            'role' => 'admin',
        ]);

        $this->actingAs($user);

        $values = [
            '{{auth.id}}',
            '{{auth.role}}',
            'static_value',
        ];

        $resolved = $this->resolver->resolveMany($values);

        $this->assertEquals([123, 'admin', 'static_value'], $resolved);
    }

    /**
     * Test that template variables are detected.
     *
     * @return void
     */
    public function test_template_variables_are_detected(): void
    {
        $this->assertTrue($this->resolver->hasTemplateVariables('{{auth.id}}'));
        $this->assertTrue($this->resolver->hasTemplateVariables('User {{auth.id}} is active'));
        $this->assertFalse($this->resolver->hasTemplateVariables('plain string'));
        $this->assertFalse($this->resolver->hasTemplateVariables(''));
    }

    /**
     * Test that template variables are extracted.
     *
     * @return void
     */
    public function test_template_variables_are_extracted(): void
    {
        $variables = $this->resolver->extractVariables('{{auth.id}}');
        $this->assertEquals(['auth.id'], $variables);

        $variables = $this->resolver->extractVariables('User {{auth.id}} in {{auth.department}}');
        $this->assertEquals(['auth.id', 'auth.department'], $variables);

        $variables = $this->resolver->extractVariables('plain string');
        $this->assertEquals([], $variables);
    }

    /**
     * Test that templates are validated.
     *
     * @return void
     */
    public function test_templates_are_validated(): void
    {
        $this->assertTrue($this->resolver->validateTemplate('{{auth.id}}'));
        $this->assertTrue($this->resolver->validateTemplate('User {{auth.id}} in {{auth.department}}'));
        $this->assertFalse($this->resolver->validateTemplate('{{unknown.var}}'));
        $this->assertTrue($this->resolver->validateTemplate('plain string'));
    }

    /**
     * Test that unregistered variables are detected.
     *
     * @return void
     */
    public function test_unregistered_variables_are_detected(): void
    {
        $unregistered = $this->resolver->getUnregisteredVariables('{{auth.id}}');
        $this->assertEquals([], $unregistered);

        $unregistered = $this->resolver->getUnregisteredVariables('{{unknown.var}}');
        $this->assertEquals(['unknown.var'], $unregistered);

        $unregistered = $this->resolver->getUnregisteredVariables('{{auth.id}} {{unknown.var}}');
        $this->assertEquals(['unknown.var'], $unregistered);
    }

    /**
     * Test that resolver can be reset.
     *
     * @return void
     */
    public function test_resolver_can_be_reset(): void
    {
        $this->resolver->register('custom.var', fn () => 'custom_value');
        $this->assertTrue($this->resolver->has('custom.var'));

        $this->resolver->reset();

        $this->assertFalse($this->resolver->has('custom.var'));
        $this->assertTrue($this->resolver->has('auth.id'));
    }

    /**
     * Test that system variables are resolved correctly.
     *
     * @return void
     */
    public function test_system_variables_are_resolved_correctly(): void
    {
        $now = now();

        $result = $this->resolver->resolve('{{today}}');
        $this->assertEquals($now->toDateString(), $result);

        $result = $this->resolver->resolve('{{year}}');
        $this->assertEquals($now->year, $result);

        $result = $this->resolver->resolve('{{month}}');
        $this->assertEquals($now->month, $result);

        $result = $this->resolver->resolve('{{day}}');
        $this->assertEquals($now->day, $result);
    }

    /**
     * Test that custom variables with complex logic work.
     *
     * @return void
     */
    public function test_custom_variables_with_complex_logic_work(): void
    {
        $this->resolver->register('custom.calculation', function () {
            return 10 + 20;
        });

        $result = $this->resolver->resolve('{{custom.calculation}}');
        $this->assertEquals(30, $result);
    }

    /**
     * Test that null values from variables are handled.
     *
     * @return void
     */
    public function test_null_values_from_variables_are_handled(): void
    {
        // Clear authenticated user
        self::$authGuard->setUser(null);

        // No authenticated user
        $result = $this->resolver->resolve('{{auth.id}}');
        $this->assertNull($result);

        $result = $this->resolver->resolve('{{auth.role}}');
        $this->assertNull($result);
    }

    /**
     * Test that whitespace in variable names is handled.
     *
     * @return void
     */
    public function test_whitespace_in_variable_names_is_handled(): void
    {
        $user = $this->createUser(['id' => 123]);
        $this->actingAs($user);

        $result = $this->resolver->resolve('{{ auth.id }}');
        $this->assertEquals(123, $result);

        $result = $this->resolver->resolve('{{  auth.id  }}');
        $this->assertEquals(123, $result);
    }

    /**
     * Test that user alias variables work.
     *
     * @return void
     */
    public function test_user_alias_variables_work(): void
    {
        $user = $this->createUser([
            'id' => 123,
            'role' => 'admin',
        ]);

        $this->actingAs($user);

        // user.* should work the same as auth.*
        $result = $this->resolver->resolve('{{user.id}}');
        $this->assertEquals(123, $result);

        $result = $this->resolver->resolve('{{user.role}}');
        $this->assertEquals('admin', $result);
    }

    /**
     * Test that resolver works with empty conditions.
     *
     * @return void
     */
    public function test_resolver_works_with_empty_conditions(): void
    {
        $resolved = $this->resolver->resolveConditions([]);
        $this->assertEquals([], $resolved);
    }

    /**
     * Test that resolver handles mixed template and non-template values.
     *
     * @return void
     */
    public function test_resolver_handles_mixed_values(): void
    {
        $user = $this->createUser(['id' => 123]);
        $this->actingAs($user);

        $conditions = [
            'user_id' => '{{auth.id}}',
            'status' => 'active',
            'count' => 10,
            'enabled' => true,
        ];

        $resolved = $this->resolver->resolveConditions($conditions);

        $this->assertEquals([
            'user_id' => 123,
            'status' => 'active',
            'count' => 10,
            'enabled' => true,
        ], $resolved);
    }

    /**
     * Test that configuration variables are loaded on construction.
     *
     * @return void
     */
    public function test_configuration_variables_are_loaded_on_construction(): void
    {
        // Set config variables
        config([
            'canvastack-rbac.fine_grained.row_level.template_variables' => [
                'config.var1' => fn () => 'config_value_1',
                'config.var2' => fn () => 'config_value_2',
            ],
        ]);

        // Create new resolver to load config
        $resolver = new TemplateVariableResolver();

        $this->assertTrue($resolver->has('config.var1'));
        $this->assertTrue($resolver->has('config.var2'));

        $result = $resolver->resolve('{{config.var1}}');
        $this->assertEquals('config_value_1', $result);

        $result = $resolver->resolve('{{config.var2}}');
        $this->assertEquals('config_value_2', $result);
    }

    /**
     * Test that configuration variables override default variables.
     *
     * @return void
     */
    public function test_configuration_variables_override_defaults(): void
    {
        // Override a default variable
        config([
            'canvastack-rbac.fine_grained.row_level.template_variables' => [
                'auth.id' => fn () => 999, // Override default auth.id
            ],
        ]);

        $resolver = new TemplateVariableResolver();

        $result = $resolver->resolve('{{auth.id}}');
        $this->assertEquals(999, $result);
    }

    /**
     * Test that non-callable config values are ignored.
     *
     * @return void
     */
    public function test_non_callable_config_values_are_ignored(): void
    {
        config([
            'canvastack-rbac.fine_grained.row_level.template_variables' => [
                'valid.var' => fn () => 'valid',
                'invalid.var' => 'not_callable', // Not callable
            ],
        ]);

        $resolver = new TemplateVariableResolver();

        $this->assertTrue($resolver->has('valid.var'));
        $this->assertFalse($resolver->has('invalid.var'));
    }

    /**
     * Test that invalid config structure is handled gracefully.
     *
     * @return void
     */
    public function test_invalid_config_structure_is_handled_gracefully(): void
    {
        // Set invalid config (not an array)
        config([
            'canvastack-rbac.fine_grained.row_level.template_variables' => 'invalid',
        ]);

        // Should not throw exception
        $resolver = new TemplateVariableResolver();

        // Default variables should still be loaded
        $this->assertTrue($resolver->has('auth.id'));
    }

    /**
     * Test that config can be reloaded at runtime.
     *
     * @return void
     */
    public function test_config_can_be_reloaded_at_runtime(): void
    {
        // Initial config
        config([
            'canvastack-rbac.fine_grained.row_level.template_variables' => [
                'runtime.var1' => fn () => 'value1',
            ],
        ]);

        $resolver = new TemplateVariableResolver();
        $this->assertTrue($resolver->has('runtime.var1'));

        // Change config
        config([
            'canvastack-rbac.fine_grained.row_level.template_variables' => [
                'runtime.var1' => fn () => 'value1',
                'runtime.var2' => fn () => 'value2',
            ],
        ]);

        // Reload config
        $resolver->reloadConfig();

        $this->assertTrue($resolver->has('runtime.var1'));
        $this->assertTrue($resolver->has('runtime.var2'));
    }

    /**
     * Test that variable count is correct.
     *
     * @return void
     */
    public function test_variable_count_is_correct(): void
    {
        $resolver = new TemplateVariableResolver();

        // Should have default variables
        $count = $resolver->count();
        $this->assertGreaterThan(0, $count);

        // Add custom variable
        $resolver->register('custom.var', fn () => 'value');
        $this->assertEquals($count + 1, $resolver->count());

        // Remove variable
        $resolver->unregister('custom.var');
        $this->assertEquals($count, $resolver->count());
    }

    /**
     * Test that variable names can be retrieved.
     *
     * @return void
     */
    public function test_variable_names_can_be_retrieved(): void
    {
        $resolver = new TemplateVariableResolver();

        $names = $resolver->getVariableNames();

        $this->assertIsArray($names);
        $this->assertContains('auth.id', $names);
        $this->assertContains('auth.role', $names);
        $this->assertContains('user.id', $names);
        $this->assertContains('now', $names);
    }

    /**
     * Test that custom config variables work with complex logic.
     *
     * @return void
     */
    public function test_custom_config_variables_work_with_complex_logic(): void
    {
        // Clear authenticated user first
        self::$authGuard->setUser(null);

        config([
            'canvastack-rbac.fine_grained.row_level.template_variables' => [
                'custom.calculation' => fn () => 10 * 5 + 3,
                'custom.date' => fn () => now()->addDays(7)->toDateString(),
                'custom.user_check' => function () {
                    $auth = app('auth');

                    return method_exists($auth, 'check') && $auth->check() ? 'authenticated' : 'guest';
                },
            ],
        ]);

        $resolver = new TemplateVariableResolver();

        $result = $resolver->resolve('{{custom.calculation}}');
        $this->assertEquals(53, $result);

        $result = $resolver->resolve('{{custom.date}}');
        $this->assertEquals(now()->addDays(7)->toDateString(), $result);

        $result = $resolver->resolve('{{custom.user_check}}');
        $this->assertEquals('guest', $result);
    }

    /**
     * Test that config variables work in conditions array.
     *
     * @return void
     */
    public function test_config_variables_work_in_conditions_array(): void
    {
        config([
            'canvastack-rbac.fine_grained.row_level.template_variables' => [
                'custom.status' => fn () => 'active',
                'custom.priority' => fn () => 5,
            ],
        ]);

        $resolver = new TemplateVariableResolver();

        $conditions = [
            'status' => '{{custom.status}}',
            'priority' => '{{custom.priority}}',
        ];

        $resolved = $resolver->resolveConditions($conditions);

        $this->assertEquals([
            'status' => 'active',
            'priority' => 5,
        ], $resolved);
    }

    /**
     * Test that reset clears config variables.
     *
     * @return void
     */
    public function test_reset_clears_config_variables(): void
    {
        config([
            'canvastack-rbac.fine_grained.row_level.template_variables' => [
                'config.var' => fn () => 'config_value',
            ],
        ]);

        $resolver = new TemplateVariableResolver();
        $this->assertTrue($resolver->has('config.var'));

        $resolver->reset();

        // Config variables should be cleared
        $this->assertFalse($resolver->has('config.var'));

        // Default variables should still exist
        $this->assertTrue($resolver->has('auth.id'));
    }

    /**
     * Test that config variables are included in all() method.
     *
     * @return void
     */
    public function test_config_variables_are_included_in_all_method(): void
    {
        config([
            'canvastack-rbac.fine_grained.row_level.template_variables' => [
                'config.var1' => fn () => 'value1',
                'config.var2' => fn () => 'value2',
            ],
        ]);

        $resolver = new TemplateVariableResolver();
        $all = $resolver->all();

        $this->assertArrayHasKey('config.var1', $all);
        $this->assertArrayHasKey('config.var2', $all);
        $this->assertArrayHasKey('auth.id', $all);
    }
}
