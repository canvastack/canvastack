<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Auth\RBAC;

use Canvastack\Canvastack\Auth\RBAC\ConfigValidator;
use Canvastack\Canvastack\Tests\TestCase;
use InvalidArgumentException;

/**
 * Test for ConfigValidator.
 */
class ConfigValidatorTest extends TestCase
{
    protected ConfigValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new ConfigValidator();
    }

    /**
     * Test that valid configuration passes validation.
     */
    public function test_valid_configuration_passes(): void
    {
        $config = $this->getValidConfig();

        // Should not throw exception
        $this->validator->validate($config);

        $this->assertTrue(true); // If we get here, validation passed
    }

    /**
     * Test that missing enabled key throws exception.
     */
    public function test_missing_enabled_key_throws_exception(): void
    {
        $config = $this->getValidConfig();
        unset($config['enabled']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Fine-grained permissions configuration must have an "enabled" key.');

        $this->validator->validate($config);
    }

    /**
     * Test that non-boolean enabled value throws exception.
     */
    public function test_non_boolean_enabled_throws_exception(): void
    {
        $config = $this->getValidConfig();
        $config['enabled'] = 'true'; // String instead of boolean

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Fine-grained permissions "enabled" must be a boolean value.');

        $this->validator->validate($config);
    }

    /**
     * Test that missing cache section throws exception.
     */
    public function test_missing_cache_section_throws_exception(): void
    {
        $config = $this->getValidConfig();
        unset($config['cache']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Fine-grained permissions configuration must have a "cache" section.');

        $this->validator->validate($config);
    }

    /**
     * Test that missing cache enabled key throws exception.
     */
    public function test_missing_cache_enabled_throws_exception(): void
    {
        $config = $this->getValidConfig();
        unset($config['cache']['enabled']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cache configuration must have an "enabled" key.');

        $this->validator->validate($config);
    }

    /**
     * Test that non-boolean cache enabled throws exception.
     */
    public function test_non_boolean_cache_enabled_throws_exception(): void
    {
        $config = $this->getValidConfig();
        $config['cache']['enabled'] = 1; // Integer instead of boolean

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cache "enabled" must be a boolean value.');

        $this->validator->validate($config);
    }

    /**
     * Test that missing cache TTL section throws exception.
     */
    public function test_missing_cache_ttl_throws_exception(): void
    {
        $config = $this->getValidConfig();
        unset($config['cache']['ttl']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cache configuration must have a "ttl" section.');

        $this->validator->validate($config);
    }

    /**
     * Test that missing cache TTL row key throws exception.
     */
    public function test_missing_cache_ttl_row_throws_exception(): void
    {
        $config = $this->getValidConfig();
        unset($config['cache']['ttl']['row']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cache TTL configuration must have a "row" key.');

        $this->validator->validate($config);
    }

    /**
     * Test that negative cache TTL throws exception.
     */
    public function test_negative_cache_ttl_throws_exception(): void
    {
        $config = $this->getValidConfig();
        $config['cache']['ttl']['row'] = -100;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cache TTL "row" must be a non-negative integer.');

        $this->validator->validate($config);
    }

    /**
     * Test that non-integer cache TTL throws exception.
     */
    public function test_non_integer_cache_ttl_throws_exception(): void
    {
        $config = $this->getValidConfig();
        $config['cache']['ttl']['column'] = 'invalid'; // Non-numeric string

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cache TTL "column" must be a non-negative integer.');

        $this->validator->validate($config);
    }

    /**
     * Test that string integer cache TTL is accepted (type coercion).
     */
    public function test_string_integer_cache_ttl_is_accepted(): void
    {
        $config = $this->getValidConfig();
        $config['cache']['ttl']['column'] = '3600'; // String integer

        // Should not throw exception
        $this->validator->validate($config);

        $this->assertTrue(true); // If we get here, validation passed
    }

    /**
     * Test that negative string integer cache TTL throws exception.
     */
    public function test_negative_string_integer_cache_ttl_throws_exception(): void
    {
        $config = $this->getValidConfig();
        $config['cache']['ttl']['row'] = '-100'; // Negative string integer

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cache TTL "row" must be a non-negative integer.');

        $this->validator->validate($config);
    }

    /**
     * Test that missing cache key_prefix throws exception.
     */
    public function test_missing_cache_key_prefix_throws_exception(): void
    {
        $config = $this->getValidConfig();
        unset($config['cache']['key_prefix']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cache configuration must have a "key_prefix" key.');

        $this->validator->validate($config);
    }

    /**
     * Test that empty cache key_prefix throws exception.
     */
    public function test_empty_cache_key_prefix_throws_exception(): void
    {
        $config = $this->getValidConfig();
        $config['cache']['key_prefix'] = '';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cache "key_prefix" must be a non-empty string.');

        $this->validator->validate($config);
    }

    /**
     * Test that missing cache tags throws exception.
     */
    public function test_missing_cache_tags_throws_exception(): void
    {
        $config = $this->getValidConfig();
        unset($config['cache']['tags']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cache configuration must have a "tags" section.');

        $this->validator->validate($config);
    }

    /**
     * Test that empty cache tags throws exception.
     */
    public function test_empty_cache_tags_throws_exception(): void
    {
        $config = $this->getValidConfig();
        $config['cache']['tags'] = [];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cache "tags" must be a non-empty array.');

        $this->validator->validate($config);
    }

    /**
     * Test that missing row_level section throws exception.
     */
    public function test_missing_row_level_section_throws_exception(): void
    {
        $config = $this->getValidConfig();
        unset($config['row_level']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Fine-grained permissions configuration must have a "row_level" section.');

        $this->validator->validate($config);
    }

    /**
     * Test that missing row_level enabled throws exception.
     */
    public function test_missing_row_level_enabled_throws_exception(): void
    {
        $config = $this->getValidConfig();
        unset($config['row_level']['enabled']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Row-level configuration must have an "enabled" key.');

        $this->validator->validate($config);
    }

    /**
     * Test that non-boolean row_level enabled throws exception.
     */
    public function test_non_boolean_row_level_enabled_throws_exception(): void
    {
        $config = $this->getValidConfig();
        $config['row_level']['enabled'] = 'yes';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Row-level "enabled" must be a boolean value.');

        $this->validator->validate($config);
    }

    /**
     * Test that missing template_variables throws exception.
     */
    public function test_missing_template_variables_throws_exception(): void
    {
        $config = $this->getValidConfig();
        unset($config['row_level']['template_variables']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Row-level configuration must have a "template_variables" section.');

        $this->validator->validate($config);
    }

    /**
     * Test that non-array template_variables throws exception.
     */
    public function test_non_array_template_variables_throws_exception(): void
    {
        $config = $this->getValidConfig();
        $config['row_level']['template_variables'] = 'invalid';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Row-level "template_variables" must be an array.');

        $this->validator->validate($config);
    }

    /**
     * Test that non-callable template variable throws exception.
     */
    public function test_non_callable_template_variable_throws_exception(): void
    {
        $config = $this->getValidConfig();
        $config['row_level']['template_variables']['invalid'] = 'not-callable';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Template variable "invalid" must be a callable.');

        $this->validator->validate($config);
    }

    /**
     * Test that missing column_level section throws exception.
     */
    public function test_missing_column_level_section_throws_exception(): void
    {
        $config = $this->getValidConfig();
        unset($config['column_level']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Fine-grained permissions configuration must have a "column_level" section.');

        $this->validator->validate($config);
    }

    /**
     * Test that missing column_level enabled throws exception.
     */
    public function test_missing_column_level_enabled_throws_exception(): void
    {
        $config = $this->getValidConfig();
        unset($config['column_level']['enabled']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Column-level configuration must have an "enabled" key.');

        $this->validator->validate($config);
    }

    /**
     * Test that missing column_level default_deny throws exception.
     */
    public function test_missing_column_level_default_deny_throws_exception(): void
    {
        $config = $this->getValidConfig();
        unset($config['column_level']['default_deny']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Column-level configuration must have a "default_deny" key.');

        $this->validator->validate($config);
    }

    /**
     * Test that non-boolean column_level default_deny throws exception.
     */
    public function test_non_boolean_column_level_default_deny_throws_exception(): void
    {
        $config = $this->getValidConfig();
        $config['column_level']['default_deny'] = 'false';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Column-level "default_deny" must be a boolean value.');

        $this->validator->validate($config);
    }

    /**
     * Test that missing json_attribute section throws exception.
     */
    public function test_missing_json_attribute_section_throws_exception(): void
    {
        $config = $this->getValidConfig();
        unset($config['json_attribute']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Fine-grained permissions configuration must have a "json_attribute" section.');

        $this->validator->validate($config);
    }

    /**
     * Test that missing json_attribute enabled throws exception.
     */
    public function test_missing_json_attribute_enabled_throws_exception(): void
    {
        $config = $this->getValidConfig();
        unset($config['json_attribute']['enabled']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('JSON attribute configuration must have an "enabled" key.');

        $this->validator->validate($config);
    }

    /**
     * Test that missing json_attribute path_separator throws exception.
     */
    public function test_missing_json_attribute_path_separator_throws_exception(): void
    {
        $config = $this->getValidConfig();
        unset($config['json_attribute']['path_separator']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('JSON attribute configuration must have a "path_separator" key.');

        $this->validator->validate($config);
    }

    /**
     * Test that empty json_attribute path_separator throws exception.
     */
    public function test_empty_json_attribute_path_separator_throws_exception(): void
    {
        $config = $this->getValidConfig();
        $config['json_attribute']['path_separator'] = '';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('JSON attribute "path_separator" must be a non-empty string.');

        $this->validator->validate($config);
    }

    /**
     * Test that multi-character path_separator throws exception.
     */
    public function test_multi_character_path_separator_throws_exception(): void
    {
        $config = $this->getValidConfig();
        $config['json_attribute']['path_separator'] = '::';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('JSON attribute "path_separator" must be a single character.');

        $this->validator->validate($config);
    }

    /**
     * Test that missing conditional section throws exception.
     */
    public function test_missing_conditional_section_throws_exception(): void
    {
        $config = $this->getValidConfig();
        unset($config['conditional']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Fine-grained permissions configuration must have a "conditional" section.');

        $this->validator->validate($config);
    }

    /**
     * Test that missing conditional enabled throws exception.
     */
    public function test_missing_conditional_enabled_throws_exception(): void
    {
        $config = $this->getValidConfig();
        unset($config['conditional']['enabled']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Conditional configuration must have an "enabled" key.');

        $this->validator->validate($config);
    }

    /**
     * Test that missing allowed_operators throws exception.
     */
    public function test_missing_allowed_operators_throws_exception(): void
    {
        $config = $this->getValidConfig();
        unset($config['conditional']['allowed_operators']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Conditional configuration must have an "allowed_operators" key.');

        $this->validator->validate($config);
    }

    /**
     * Test that empty allowed_operators throws exception.
     */
    public function test_empty_allowed_operators_throws_exception(): void
    {
        $config = $this->getValidConfig();
        $config['conditional']['allowed_operators'] = [];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Conditional "allowed_operators" must be a non-empty array.');

        $this->validator->validate($config);
    }

    /**
     * Test that non-string operator throws exception.
     */
    public function test_non_string_operator_throws_exception(): void
    {
        $config = $this->getValidConfig();
        $config['conditional']['allowed_operators'][] = 123;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('All allowed operators must be non-empty strings.');

        $this->validator->validate($config);
    }

    /**
     * Test that missing allowed_functions throws exception.
     */
    public function test_missing_allowed_functions_throws_exception(): void
    {
        $config = $this->getValidConfig();
        unset($config['conditional']['allowed_functions']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Conditional configuration must have an "allowed_functions" key.');

        $this->validator->validate($config);
    }

    /**
     * Test that non-array allowed_functions throws exception.
     */
    public function test_non_array_allowed_functions_throws_exception(): void
    {
        $config = $this->getValidConfig();
        $config['conditional']['allowed_functions'] = 'count';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Conditional "allowed_functions" must be an array.');

        $this->validator->validate($config);
    }

    /**
     * Test that non-string function throws exception.
     */
    public function test_non_string_function_throws_exception(): void
    {
        $config = $this->getValidConfig();
        $config['conditional']['allowed_functions'][] = null;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('All allowed functions must be non-empty strings.');

        $this->validator->validate($config);
    }

    /**
     * Test that missing audit section throws exception.
     */
    public function test_missing_audit_section_throws_exception(): void
    {
        $config = $this->getValidConfig();
        unset($config['audit']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Fine-grained permissions configuration must have an "audit" section.');

        $this->validator->validate($config);
    }

    /**
     * Test that missing audit enabled throws exception.
     */
    public function test_missing_audit_enabled_throws_exception(): void
    {
        $config = $this->getValidConfig();
        unset($config['audit']['enabled']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Audit configuration must have an "enabled" key.');

        $this->validator->validate($config);
    }

    /**
     * Test that missing audit log_denials throws exception.
     */
    public function test_missing_audit_log_denials_throws_exception(): void
    {
        $config = $this->getValidConfig();
        unset($config['audit']['log_denials']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Audit configuration must have a "log_denials" key.');

        $this->validator->validate($config);
    }

    /**
     * Test that non-boolean audit log_denials throws exception.
     */
    public function test_non_boolean_audit_log_denials_throws_exception(): void
    {
        $config = $this->getValidConfig();
        $config['audit']['log_denials'] = 1;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Audit "log_denials" must be a boolean value.');

        $this->validator->validate($config);
    }

    /**
     * Test that missing audit log_channel throws exception.
     */
    public function test_missing_audit_log_channel_throws_exception(): void
    {
        $config = $this->getValidConfig();
        unset($config['audit']['log_channel']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Audit configuration must have a "log_channel" key.');

        $this->validator->validate($config);
    }

    /**
     * Test that empty audit log_channel throws exception.
     */
    public function test_empty_audit_log_channel_throws_exception(): void
    {
        $config = $this->getValidConfig();
        $config['audit']['log_channel'] = '';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Audit "log_channel" must be a non-empty string.');

        $this->validator->validate($config);
    }

    /**
     * Get a valid configuration array for testing.
     *
     * @return array<string, mixed>
     */
    protected function getValidConfig(): array
    {
        return [
            'enabled' => true,
            'cache' => [
                'enabled' => true,
                'ttl' => [
                    'row' => 3600,
                    'column' => 3600,
                    'json_attribute' => 3600,
                    'conditional' => 1800,
                ],
                'key_prefix' => 'canvastack:rbac:rules:',
                'tags' => [
                    'rules' => 'rbac:rules',
                    'user' => 'rbac:user:{userId}',
                ],
            ],
            'row_level' => [
                'enabled' => true,
                'template_variables' => [
                    'auth.id' => fn () => 1,
                    'auth.role' => fn () => 'admin',
                ],
            ],
            'column_level' => [
                'enabled' => true,
                'default_deny' => false,
            ],
            'json_attribute' => [
                'enabled' => true,
                'path_separator' => '.',
            ],
            'conditional' => [
                'enabled' => true,
                'allowed_operators' => ['===', '!==', '>', '<', '>=', '<='],
                'allowed_functions' => ['count', 'sum'],
            ],
            'audit' => [
                'enabled' => true,
                'log_denials' => true,
                'log_channel' => 'rbac',
            ],
        ];
    }
}
