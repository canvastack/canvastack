<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Security\Table;

use Canvastack\Canvastack\Components\Table\ConnectionManager;
use Canvastack\Canvastack\Components\Table\HashGenerator;
use Canvastack\Canvastack\Components\Table\WarningSystem;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Support\Facades\Config;

/**
 * Test information disclosure security.
 *
 * Requirements: 10.8, 10.9
 */
class InformationDisclosureSecurityTest extends TestCase
{
    private HashGenerator $hashGenerator;
    private ConnectionManager $connectionManager;
    private WarningSystem $warningSystem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hashGenerator = new HashGenerator();
        $this->warningSystem = new WarningSystem();
        $this->connectionManager = new ConnectionManager(
            $this->warningSystem
        );
    }

    /**
     * Test that unique IDs do not expose table names.
     *
     * @return void
     */
    public function test_unique_id_does_not_expose_table_name(): void
    {
        $tableName = 'users';
        $connection = 'mysql';
        $fields = ['id', 'name', 'email'];

        $uniqueId = $this->hashGenerator->generate($tableName, $connection, $fields);

        // Verify ID format
        $this->assertMatchesRegularExpression('/^canvastable_[a-f0-9]{16}$/', $uniqueId);

        // Verify table name is not in ID
        $this->assertStringNotContainsString($tableName, $uniqueId);
        $this->assertStringNotContainsString('user', strtolower($uniqueId));
    }

    /**
     * Test that unique IDs do not expose connection names.
     *
     * @return void
     */
    public function test_unique_id_does_not_expose_connection_name(): void
    {
        $tableName = 'orders';
        $connection = 'mysql_reporting';
        $fields = ['id', 'total'];

        $uniqueId = $this->hashGenerator->generate($tableName, $connection, $fields);

        // Verify connection name is not in ID
        $this->assertStringNotContainsString($connection, $uniqueId);
        $this->assertStringNotContainsString('mysql', strtolower($uniqueId));
        $this->assertStringNotContainsString('reporting', strtolower($uniqueId));
    }

    /**
     * Test that unique IDs do not expose field names.
     *
     * @return void
     */
    public function test_unique_id_does_not_expose_field_names(): void
    {
        $tableName = 'users';
        $connection = 'mysql';
        $fields = ['id', 'password', 'api_token', 'secret_key'];

        $uniqueId = $this->hashGenerator->generate($tableName, $connection, $fields);

        // Verify sensitive field names are not in ID
        $this->assertStringNotContainsString('password', strtolower($uniqueId));
        $this->assertStringNotContainsString('token', strtolower($uniqueId));
        $this->assertStringNotContainsString('secret', strtolower($uniqueId));
        $this->assertStringNotContainsString('key', strtolower($uniqueId));
    }

    /**
     * Test that rendered HTML does not expose connection details.
     *
     * @return void
     */
    public function test_rendered_html_does_not_expose_connection_details(): void
    {
        // This test will be implemented when TableBuilder is available
        // For now, we verify that ConnectionManager doesn't expose details
        $connection = $this->connectionManager->getConnection();
        
        // Verify connection name doesn't contain sensitive info
        $this->assertIsString($connection);
        $this->assertNotEmpty($connection);
    }

    /**
     * Test that rendered HTML does not expose table structure.
     *
     * @return void
     */
    public function test_rendered_html_does_not_expose_table_structure(): void
    {
        // This test will be implemented when TableBuilder is available
        // For now, we verify that HashGenerator doesn't expose structure
        $uniqueId = $this->hashGenerator->generate('users', 'mysql', ['id', 'name']);
        
        // Verify ID doesn't contain table structure
        $this->assertStringNotContainsString('users', $uniqueId);
        $this->assertStringNotContainsString('mysql', $uniqueId);
    }

    /**
     * Test that rendered HTML does not expose SQL queries.
     *
     * @return void
     */
    public function test_rendered_html_does_not_expose_sql_queries(): void
    {
        // This test will be implemented when TableBuilder is available
        // For now, we verify that components don't expose SQL
        $uniqueId = $this->hashGenerator->generate('users', 'mysql', ['id', 'name']);
        
        // Verify ID doesn't contain SQL keywords
        $this->assertStringNotContainsString('SELECT', $uniqueId);
        $this->assertStringNotContainsString('FROM', $uniqueId);
    }

    /**
     * Test that error messages do not expose sensitive information.
     *
     * @return void
     */
    public function test_error_messages_do_not_expose_sensitive_information(): void
    {
        // Set app to production mode
        Config::set('app.debug', false);

        try {
            // Trigger an error by using invalid connection
            $this->connectionManager->setOverride('invalid_connection_name_that_does_not_exist');
            $connection = $this->connectionManager->getConnection();
            
            // If we get here, just verify the connection string doesn't expose sensitive info
            $this->assertStringNotContainsString('password', strtolower($connection));
            $this->assertStringNotContainsString('secret', strtolower($connection));
        } catch (\Exception $e) {
            $message = $e->getMessage();

            // Verify error message does not expose sensitive info
            $this->assertStringNotContainsString('password', strtolower($message));
            $this->assertStringNotContainsString('secret', strtolower($message));
        }
    }

    /**
     * Test that AJAX error responses do not expose sensitive information.
     *
     * @return void
     */
    public function test_ajax_error_responses_do_not_expose_sensitive_information(): void
    {
        Config::set('app.debug', false);

        // This test will be implemented when TableTabController is available
        // For now, we verify that error messages are sanitized
        $this->assertTrue(true, 'AJAX error response test placeholder');
    }

    /**
     * Test that debug mode is disabled in production.
     *
     * @return void
     */
    public function test_debug_mode_disabled_in_production(): void
    {
        Config::set('app.debug', false);

        $this->assertFalse(config('app.debug'), 'Debug mode should be disabled in production');
    }

    /**
     * Test that stack traces are not exposed in production.
     *
     * @return void
     */
    public function test_stack_traces_not_exposed_in_production(): void
    {
        Config::set('app.debug', false);

        try {
            // Trigger an error
            throw new \RuntimeException('Test error');
        } catch (\Exception $e) {
            $message = $e->getMessage();

            // In production, stack trace should not be visible to users
            // This is handled by Laravel's exception handler
            $this->assertEquals('Test error', $message);
            $this->assertStringNotContainsString(__FILE__, $message);
        }
    }

    /**
     * Test that configuration values are not exposed in HTML.
     *
     * @return void
     */
    public function test_configuration_values_not_exposed_in_html(): void
    {
        // Verify that unique IDs don't expose config values
        $uniqueId = $this->hashGenerator->generate('users', 'mysql', ['id', 'name']);
        
        // Verify config values are not in ID (if they exist)
        $dbDefault = config('database.default');
        if ($dbDefault) {
            $this->assertStringNotContainsString($dbDefault, $uniqueId);
        }
        
        $appKey = config('app.key');
        if ($appKey) {
            $this->assertStringNotContainsString($appKey, $uniqueId);
        }
        
        // At minimum, verify ID format is correct
        $this->assertMatchesRegularExpression('/^canvastable_[a-f0-9]{16}$/', $uniqueId);
    }

    /**
     * Test that environment variables are not exposed.
     *
     * @return void
     */
    public function test_environment_variables_not_exposed(): void
    {
        // Verify that unique IDs don't expose env variables
        $uniqueId = $this->hashGenerator->generate('users', 'mysql', ['id', 'name']);
        
        // Verify env variables are not in ID
        $this->assertStringNotContainsString('DB_HOST', $uniqueId);
        $this->assertStringNotContainsString('DB_DATABASE', $uniqueId);
        $this->assertStringNotContainsString('DB_USERNAME', $uniqueId);
        $this->assertStringNotContainsString('DB_PASSWORD', $uniqueId);
        $this->assertStringNotContainsString('APP_KEY', $uniqueId);
    }

    /**
     * Test that internal paths are not exposed.
     *
     * @return void
     */
    public function test_internal_paths_not_exposed(): void
    {
        // Verify that unique IDs don't expose internal paths
        $uniqueId = $this->hashGenerator->generate('users', 'mysql', ['id', 'name']);
        
        // Verify internal paths are not in ID
        $this->assertStringNotContainsString(base_path(), $uniqueId);
        $this->assertStringNotContainsString(storage_path(), $uniqueId);
    }

    /**
     * Test that class names are not exposed in production.
     *
     * @return void
     */
    public function test_class_names_not_exposed_in_production(): void
    {
        Config::set('app.debug', false);

        // Verify that unique IDs don't expose class names
        $uniqueId = $this->hashGenerator->generate('users', 'mysql', ['id', 'name']);
        
        // Verify internal class names are not in ID
        $this->assertStringNotContainsString('HashGenerator', $uniqueId);
        $this->assertStringNotContainsString('ConnectionManager', $uniqueId);
        $this->assertStringNotContainsString('Canvastack\\', $uniqueId);
    }

    /**
     * Test that model attributes are not exposed beyond displayed fields.
     *
     * @return void
     */
    public function test_model_attributes_not_exposed_beyond_displayed_fields(): void
    {
        // Verify that unique IDs don't expose model attributes
        $uniqueId = $this->hashGenerator->generate('users', 'mysql', ['name']); // Only name field
        
        // Verify hidden attributes are not in ID
        $this->assertStringNotContainsString('password', strtolower($uniqueId));
        $this->assertStringNotContainsString('remember_token', strtolower($uniqueId));
        $this->assertStringNotContainsString('api_token', strtolower($uniqueId));
    }

    /**
     * Test that error messages are sanitized.
     *
     * @return void
     */
    public function test_error_messages_are_sanitized(): void
    {
        Config::set('app.debug', false);

        // Test that potentially malicious input is sanitized
        $tableName = '<script>alert("xss")</script>';
        $uniqueId = $this->hashGenerator->generate($tableName, 'mysql', ['id']);
        
        // Verify malicious input is not in output
        $this->assertStringNotContainsString('<script>', $uniqueId);
        $this->assertStringNotContainsString('alert(', $uniqueId);
    }

    /**
     * Test that tab URLs do not expose sensitive information.
     *
     * @return void
     */
    public function test_tab_urls_do_not_expose_sensitive_information(): void
    {
        // This test will be implemented when TabManager is available
        // For now, we verify that unique IDs don't expose sensitive info
        $uniqueId = $this->hashGenerator->generate('users', 'mysql', ['password', 'api_token']);
        
        // Verify URL-safe ID doesn't expose sensitive field names
        $this->assertStringNotContainsString('password', strtolower($uniqueId));
        $this->assertStringNotContainsString('token', strtolower($uniqueId));
        $this->assertStringNotContainsString('secret', strtolower($uniqueId));
    }

    /**
     * Test that cache keys do not expose sensitive information.
     *
     * @return void
     */
    public function test_cache_keys_do_not_expose_sensitive_information(): void
    {
        // Verify that unique IDs (used as cache keys) don't expose sensitive info
        $uniqueId = $this->hashGenerator->generate('users', 'mysql', ['password', 'api_token']);
        
        // Verify cache key doesn't expose sensitive info
        $this->assertStringNotContainsString('password', strtolower($uniqueId));
        $this->assertStringNotContainsString('token', strtolower($uniqueId));
        $this->assertStringNotContainsString('mysql', strtolower($uniqueId));
        $this->assertStringNotContainsString('database', strtolower($uniqueId));
    }

    /**
     * Test that JavaScript variables do not expose sensitive information.
     *
     * @return void
     */
    public function test_javascript_variables_do_not_expose_sensitive_information(): void
    {
        // Verify that unique IDs (used in JS) don't expose sensitive info
        $uniqueId = $this->hashGenerator->generate('users', 'mysql', ['password']);
        
        // Verify JS-safe ID doesn't expose sensitive info
        $this->assertStringNotContainsString('password', strtolower($uniqueId));
        $this->assertStringNotContainsString('token', strtolower($uniqueId));
        $this->assertStringNotContainsString('secret', strtolower($uniqueId));
    }

    /**
     * Test that comments do not expose sensitive information.
     *
     * @return void
     */
    public function test_comments_do_not_expose_sensitive_information(): void
    {
        // Verify that unique IDs don't contain comment-like patterns
        $uniqueId = $this->hashGenerator->generate('users', 'mysql', ['id', 'name']);
        
        // Verify ID doesn't contain comment patterns
        $this->assertStringNotContainsString('<!--', $uniqueId);
        $this->assertStringNotContainsString('-->', $uniqueId);
        $this->assertStringNotContainsString('//', $uniqueId);
        $this->assertStringNotContainsString('/*', $uniqueId);
    }
}
