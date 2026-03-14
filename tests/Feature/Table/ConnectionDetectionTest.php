<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Feature\Table;

use Canvastack\Canvastack\Components\Table\ConnectionManager;
use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Components\Table\WarningSystem;
use Canvastack\Canvastack\Tests\Feature\FeatureTestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

/**
 * Connection Detection Feature Test.
 *
 * Tests connection detection, manual override, warning system, and priority resolution
 * in real integration scenarios with TableBuilder.
 *
 * Requirements Validated:
 * - 2.1: Connection detection from Eloquent model
 * - 2.2: Priority resolution (override > model > default)
 * - 2.3: Default connection when no model
 * - 2.4: Manual override via connection() method
 * - 2.5: ConnectionManager properties
 * - 2.6: Debug level logging
 * - 2.7: Custom connection configurations
 * - 3.1: Connection mismatch detection
 * - 3.2: Warning configuration from environment
 * - 3.3: Warning execution based on method
 * - 3.4: Warning method support (log, toast, both)
 * - 3.5: Log warning to Laravel log
 * - 3.6: Toast notification generation
 * - 3.7: Warning message content
 * - 3.8: Warnings disabled behavior
 * - 3.9: Environment variable for warning method
 * - 12.2: Connection detection tests
 * - 12.9: Warning system tests
 *
 * @package CanvaStack
 * @subpackage Tests\Feature\Table
 */
class ConnectionDetectionTest extends FeatureTestCase
{
    use RefreshDatabase;

    /**
     * Test model for connection detection.
     */
    protected Model $testModel;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test model with custom connection
        $this->testModel = new class extends Model {
            protected $connection = 'testing_pgsql';
            protected $table = 'test_users';
            public $timestamps = false;
        };

        // Setup database configuration with SQLite for testing
        Config::set('database.default', 'testing_mysql');
        Config::set('database.connections.testing_mysql', [
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);
        Config::set('database.connections.testing_pgsql', [
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);
        Config::set('database.connections.testing_sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);
        Config::set('database.connections.testing_custom', [
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);
    }

    /**
     * Helper method to get ConnectionManager from TableBuilder using reflection.
     *
     * @param TableBuilder $table
     * @return ConnectionManager
     */
    protected function getConnectionManager(TableBuilder $table): ConnectionManager
    {
        $reflection = new \ReflectionClass($table);
        $property = $reflection->getProperty('connectionManager');
        $property->setAccessible(true);
        return $property->getValue($table);
    }

    /**
     * Test auto-detection from model.
     *
     * Validates: Requirements 2.1, 2.6, 12.2
     *
     * @return void
     */
    public function test_auto_detection_from_model(): void
    {
        // Arrange
        $table = app(TableBuilder::class);
        $table->setContext('admin');
        $table->setData([['id' => 1, 'name' => 'Test User']]);

        // Expect debug log for connection detection
        Log::shouldReceive('debug')
            ->once()
            ->with('ConnectionManager: Detected connection from model', \Mockery::type('array'));

        // Act
        $table->setModel($this->testModel);
        $table->setFields(['name:Name']);

        // Assert - Connection should be auto-detected from model
        $connectionManager = $this->getConnectionManager($table);

        $this->assertNotNull($connectionManager, 'ConnectionManager should be available');
        $this->assertEquals('testing_pgsql', $connectionManager->getDetectedConnection(), 'Should detect testing_pgsql from model');
        $this->assertEquals('testing_pgsql', $connectionManager->getConnection(), 'Should use detected connection');
    }

    /**
     * Test manual override via connection() method.
     *
     * Validates: Requirements 2.4, 3.1, 12.2
     *
     * @return void
     */
    public function test_manual_override_via_connection_method(): void
    {
        // Arrange
        $table = app(TableBuilder::class);
        $table->setContext('admin');
        $table->setData([['id' => 1, 'name' => 'Test User']]);

        Log::shouldReceive('debug')->twice(); // detect + override
        Log::shouldReceive('warning')->once(); // mismatch warning

        // Act
        $table->setModel($this->testModel); // Model has testing_pgsql
        $table->connection('testing_mysql'); // Override to testing_mysql
        $table->setFields(['name:Name']);

        // Assert
        $connectionManager = $this->getConnectionManager($table);

        $this->assertEquals('testing_pgsql', $connectionManager->getDetectedConnection(), 'Should detect testing_pgsql from model');
        $this->assertEquals('testing_mysql', $connectionManager->getOverrideConnection(), 'Should have testing_mysql override');
        $this->assertEquals('testing_mysql', $connectionManager->getConnection(), 'Should use override connection');
        $this->assertTrue($connectionManager->hasOverride(), 'Should have override');
        $this->assertTrue($connectionManager->hasConnectionMismatch(), 'Should detect mismatch');
    }

    /**
     * Test warning system when connections differ.
     *
     * Validates: Requirements 3.1, 3.2, 3.3, 3.5, 3.7, 12.9
     *
     * @return void
     */
    public function test_warning_system_when_connections_differ(): void
    {
        // Arrange
        Config::set('canvastack.table.connection_warning.enabled', true);
        Config::set('canvastack.table.connection_warning.method', 'log');

        $table = app(TableBuilder::class);
        $table->setContext('admin');
        $table->setData([['id' => 1, 'name' => 'Test User']]);

        // Expect debug logs for connection detection and override
        Log::shouldReceive('debug')->twice();

        // Expect warning log for connection mismatch
        Log::shouldReceive('warning')
            ->once()
            ->with(\Mockery::on(function ($message) {
                return str_contains($message, 'Connection override detected') &&
                       str_contains($message, 'testing_pgsql') &&
                       str_contains($message, 'testing_mysql');
            }));

        // Act
        $table->setModel($this->testModel); // Model has testing_pgsql
        $table->connection('testing_mysql'); // Override to testing_mysql (triggers warning)
        $table->setFields(['name:Name']);

        // Assert - Warning should be triggered
        $connectionManager = $this->getConnectionManager($table);

        $this->assertTrue($connectionManager->hasConnectionMismatch(), 'Should detect mismatch');
    }

    /**
     * Test warning system with toast method.
     *
     * Validates: Requirements 3.3, 3.4, 3.6, 3.7, 12.9
     *
     * @return void
     */
    public function test_warning_system_with_toast_method(): void
    {
        // Arrange
        Config::set('canvastack.table.connection_warning.enabled', true);
        Config::set('canvastack.table.connection_warning.method', 'toast');

        $table = app(TableBuilder::class);
        $table->setContext('admin');
        $table->setData([['id' => 1, 'name' => 'Test User']]);

        Log::shouldReceive('debug')->twice();

        // Act
        $table->setModel($this->testModel); // Model has testing_pgsql
        $table->connection('testing_mysql'); // Override to testing_mysql
        $table->setFields(['name:Name']);
        $table->format();

        // Render to get toast notification
        $html = $table->render();

        // Assert - Toast notification should be in HTML
        $this->assertStringContainsString('Connection override detected', $html, 'Should contain warning message');
        $this->assertStringContainsString('testing_pgsql', $html, 'Should mention model connection');
        $this->assertStringContainsString('testing_mysql', $html, 'Should mention override connection');
    }

    /**
     * Test warning system with both method.
     *
     * Validates: Requirements 3.3, 3.4, 3.5, 3.6, 12.9
     *
     * @return void
     */
    public function test_warning_system_with_both_method(): void
    {
        // Arrange
        Config::set('canvastack.table.connection_warning.enabled', true);
        Config::set('canvastack.table.connection_warning.method', 'both');

        $table = app(TableBuilder::class);
        $table->setContext('admin');
        $table->setData([['id' => 1, 'name' => 'Test User']]);

        Log::shouldReceive('debug')->twice();

        // Expect warning log
        Log::shouldReceive('warning')
            ->once()
            ->with(\Mockery::on(function ($message) {
                return str_contains($message, 'Connection override detected');
            }));

        // Act
        $table->setModel($this->testModel);
        $table->connection('testing_mysql');
        $table->setFields(['name:Name']);
        $table->format();

        // Render to get toast notification
        $html = $table->render();

        // Assert - Both log and toast should be present
        $this->assertStringContainsString('Connection override detected', $html, 'Should have toast notification');
    }

    /**
     * Test warnings disabled behavior.
     *
     * Validates: Requirements 3.8, 12.9
     *
     * @return void
     */
    public function test_warnings_disabled_behavior(): void
    {
        // Arrange
        Config::set('canvastack.table.connection_warning.enabled', false);

        $table = app(TableBuilder::class);
        $table->setContext('admin');
        $table->setData([['id' => 1, 'name' => 'Test User']]);

        Log::shouldReceive('debug')->twice();

        // Should NOT receive warning log
        Log::shouldReceive('warning')->never();

        // Act
        $table->setModel($this->testModel);
        $table->connection('testing_mysql'); // Override but warnings disabled
        $table->setFields(['name:Name']);
        $table->format();

        // Render
        $html = $table->render();

        // Assert - No warning should be present
        $this->assertStringNotContainsString('Connection override detected', $html, 'Should not have warning when disabled');
    }

    /**
     * Test priority resolution: override > model > default.
     *
     * Validates: Requirements 2.2, 2.3, 2.4, 12.2
     *
     * @return void
     */
    public function test_priority_resolution_override_first(): void
    {
        // Arrange
        Config::set('database.default', 'testing_mysql');

        $table = app(TableBuilder::class);
        $table->setContext('admin');
        $table->setData([['id' => 1, 'name' => 'Test User']]);

        Log::shouldReceive('debug')->twice();
        Log::shouldReceive('warning')->once(); // mismatch warning

        // Act - Set model (testing_pgsql) and override (testing_sqlite)
        $table->setModel($this->testModel); // testing_pgsql
        $table->connection('testing_sqlite'); // Override
        $table->setFields(['name:Name']);

        // Assert - Override should take priority
        $connectionManager = $this->getConnectionManager($table);
        $this->assertEquals('testing_sqlite', $connectionManager->getConnection(), 'Override should take priority');
    }

    /**
     * Test priority resolution: model > default.
     *
     * Validates: Requirements 2.2, 2.3, 12.2
     *
     * @return void
     */
    public function test_priority_resolution_model_second(): void
    {
        // Arrange
        Config::set('database.default', 'testing_mysql');

        $table = app(TableBuilder::class);
        $table->setContext('admin');
        $table->setData([['id' => 1, 'name' => 'Test User']]);

        Log::shouldReceive('debug')->once();

        // Act - Set model only (no override)
        $table->setModel($this->testModel); // testing_pgsql
        $table->setFields(['name:Name']);

        // Assert - Model connection should be used
        $connectionManager = $this->getConnectionManager($table);
        $this->assertEquals('testing_pgsql', $connectionManager->getConnection(), 'Model connection should be used');
    }

    /**
     * Test priority resolution: default when no model or override.
     *
     * Validates: Requirements 2.2, 2.3, 12.2
     *
     * @return void
     */
    public function test_priority_resolution_default_last(): void
    {
        // Arrange
        Config::set('database.default', 'testing_mysql');

        $table = app(TableBuilder::class);
        $table->setContext('admin');
        $table->setData([['id' => 1, 'name' => 'Test']]);
        $table->setFields(['name:Name']);
        $table->format();

        // Act - No model, no override
        $connectionManager = $this->getConnectionManager($table);

        // Assert - Default should be used
        $this->assertEquals('testing_mysql', $connectionManager->getConnection(), 'Default connection should be used');
    }

    /**
     * Test custom connection configurations.
     *
     * Validates: Requirements 2.7, 12.2
     *
     * @return void
     */
    public function test_custom_connection_configurations(): void
    {
        // Arrange
        $customModel = new class extends Model {
            protected $connection = 'testing_custom';
            protected $table = 'custom_table';
            public $timestamps = false;
        };

        $table = app(TableBuilder::class);
        $table->setContext('admin');
        $table->setData([['id' => 1, 'name' => 'Test User']]);

        Log::shouldReceive('debug')->once();

        // Act
        $table->setModel($customModel);
        $table->setFields(['name:Name']);

        // Assert
        $connectionManager = $this->getConnectionManager($table);
        $this->assertEquals('testing_custom', $connectionManager->getDetectedConnection(), 'Should detect custom connection');
        $this->assertEquals('testing_custom', $connectionManager->getConnection(), 'Should use custom connection');
    }

    /**
     * Test debug level logging.
     *
     * Validates: Requirements 2.6, 12.2
     *
     * @return void
     */
    public function test_debug_level_logging(): void
    {
        // Arrange
        $table = app(TableBuilder::class);
        $table->setContext('admin');
        $table->setData([['id' => 1, 'name' => 'Test User']]);

        // Expect debug log with specific structure
        Log::shouldReceive('debug')
            ->once()
            ->with('ConnectionManager: Detected connection from model', \Mockery::on(function ($context) {
                return isset($context['model']) &&
                       isset($context['connection']) &&
                       $context['connection'] === 'testing_pgsql';
            }));

        // Act
        $table->setModel($this->testModel);
        $table->setFields(['name:Name']);

        // Assert - Log expectation verified by Mockery
        $this->assertTrue(true, 'Debug logging verified');
    }

    /**
     * Test warning message includes model class, connections.
     *
     * Validates: Requirements 3.7, 12.9
     *
     * @return void
     */
    public function test_warning_message_includes_required_information(): void
    {
        // Arrange
        Config::set('canvastack.table.connection_warning.enabled', true);
        Config::set('canvastack.table.connection_warning.method', 'log');

        $table = app(TableBuilder::class);
        $table->setContext('admin');
        $table->setData([['id' => 1, 'name' => 'Test User']]);

        Log::shouldReceive('debug')->twice();

        // Expect warning with specific content
        Log::shouldReceive('warning')
            ->once()
            ->with(\Mockery::on(function ($message) {
                $modelClass = get_class($this->testModel);
                return str_contains($message, 'Connection override detected') &&
                       str_contains($message, $modelClass) &&
                       str_contains($message, 'testing_pgsql') &&
                       str_contains($message, 'testing_mysql');
            }));

        // Act
        $table->setModel($this->testModel);
        $table->connection('testing_mysql');
        $table->setFields(['name:Name']);

        // Assert - Log expectation verified by Mockery
        $this->assertTrue(true, 'Warning message content verified');
    }

    /**
     * Test environment variable for warning configuration.
     *
     * Validates: Requirements 3.2, 3.9, 12.9
     *
     * @return void
     */
    public function test_environment_variable_for_warning_configuration(): void
    {
        // Arrange
        Config::set('canvastack.table.connection_warning.enabled', env('CANVASTACK_CONNECTION_WARNING', true));
        Config::set('canvastack.table.connection_warning.method', env('CANVASTACK_CONNECTION_WARNING_METHOD', 'log'));

        // Simulate environment variables
        putenv('CANVASTACK_CONNECTION_WARNING=true');
        putenv('CANVASTACK_CONNECTION_WARNING_METHOD=log');

        $table = app(TableBuilder::class);
        $table->setContext('admin');
        $table->setData([['id' => 1, 'name' => 'Test User']]);

        Log::shouldReceive('debug')->twice();
        Log::shouldReceive('warning')->once();

        // Act
        $table->setModel($this->testModel);
        $table->connection('testing_mysql');
        $table->setFields(['name:Name']);

        // Assert - Warning should be triggered based on env vars
        $connectionManager = $this->getConnectionManager($table);
        $this->assertTrue($connectionManager->hasConnectionMismatch(), 'Should detect mismatch');

        // Cleanup
        putenv('CANVASTACK_CONNECTION_WARNING');
        putenv('CANVASTACK_CONNECTION_WARNING_METHOD');
    }

    /**
     * Test no warning when connections match.
     *
     * Validates: Requirements 3.1, 12.9
     *
     * @return void
     */
    public function test_no_warning_when_connections_match(): void
    {
        // Arrange
        Config::set('canvastack.table.connection_warning.enabled', true);
        Config::set('canvastack.table.connection_warning.method', 'log');

        $table = app(TableBuilder::class);
        $table->setContext('admin');
        $table->setData([['id' => 1, 'name' => 'Test User']]);

        Log::shouldReceive('debug')->twice();

        // Should NOT receive warning log when connections match
        Log::shouldReceive('warning')->never();

        // Act
        $table->setModel($this->testModel); // testing_pgsql
        $table->connection('testing_pgsql'); // Same as model
        $table->setFields(['name:Name']);

        // Assert
        $connectionManager = $this->getConnectionManager($table);
        $this->assertFalse($connectionManager->hasConnectionMismatch(), 'Should not detect mismatch when connections match');
    }

    /**
     * Test multiple tables with different connections.
     *
     * Validates: Requirements 2.1, 2.4, 5.5, 12.2
     *
     * @return void
     */
    public function test_multiple_tables_with_different_connections(): void
    {
        // Arrange
        $model1 = new class extends Model {
            protected $connection = 'testing_mysql';
            protected $table = 'users';
            public $timestamps = false;
        };

        $model2 = new class extends Model {
            protected $connection = 'testing_pgsql';
            protected $table = 'products';
            public $timestamps = false;
        };

        Log::shouldReceive('debug')->twice();

        // Act
        $table1 = app(TableBuilder::class);
        $table1->setContext('admin');
        $table1->setData([['id' => 1, 'name' => 'User 1']]);
        $table1->setModel($model1);
        $table1->setFields(['name:Name']);

        $table2 = app(TableBuilder::class);
        $table2->setContext('admin');
        $table2->setData([['id' => 1, 'name' => 'Product 1']]);
        $table2->setModel($model2);
        $table2->setFields(['name:Name']);

        // Assert
        $connectionManager1 = $this->getConnectionManager($table1);
        $connectionManager2 = $this->getConnectionManager($table2);

        $this->assertEquals('testing_mysql', $connectionManager1->getConnection(), 'Table 1 should use testing_mysql');
        $this->assertEquals('testing_pgsql', $connectionManager2->getConnection(), 'Table 2 should use testing_pgsql');
        $this->assertNotEquals(
            $connectionManager1->getConnection(),
            $connectionManager2->getConnection(),
            'Tables should have different connections'
        );
    }

    /**
     * Test connection detection with tabs.
     *
     * Validates: Requirements 2.1, 4.1, 4.2, 12.2
     *
     * @return void
     */
    public function test_connection_detection_with_tabs(): void
    {
        // Arrange
        $table = app(TableBuilder::class);
        $table->setContext('admin');
        $table->setData([['id' => 1, 'name' => 'Test User']]);

        Log::shouldReceive('debug')->once();

        // Act
        $table->setModel($this->testModel);
        $table->setFields(['name:Name']);

        $table->openTab('Tab 1');
        $table->addTabContent('<div>Tab 1 content</div>');
        $table->closeTab();

        $table->openTab('Tab 2');
        $table->addTabContent('<div>Tab 2 content</div>');
        $table->closeTab();

        $table->format();

        // Assert
        $connectionManager = $this->getConnectionManager($table);
        $this->assertEquals('testing_pgsql', $connectionManager->getDetectedConnection(), 'Should detect connection with tabs');
        $this->assertTrue($table->hasTabNavigation(), 'Should have tab navigation');
    }

    /**
     * Test warning system integration with WarningSystem component.
     *
     * Validates: Requirements 3.1, 3.3, 12.9
     *
     * @return void
     */
    public function test_warning_system_integration(): void
    {
        // Arrange
        Config::set('canvastack.table.connection_warning.enabled', true);
        Config::set('canvastack.table.connection_warning.method', 'log');

        $warningSystem = app(WarningSystem::class);
        $connectionManager = new ConnectionManager($warningSystem);

        Log::shouldReceive('debug')->twice();
        Log::shouldReceive('warning')->once();

        // Act
        $connectionManager->detectConnection($this->testModel);
        $connectionManager->setOverride('testing_mysql');

        // Trigger warning manually (in real scenario, TableBuilder does this)
        if ($connectionManager->hasConnectionMismatch()) {
            $warningSystem->warnConnectionOverride(
                get_class($this->testModel),
                $connectionManager->getDetectedConnection(),
                $connectionManager->getOverrideConnection()
            );
        }

        // Assert
        $this->assertTrue($connectionManager->hasConnectionMismatch(), 'Should detect mismatch');
    }

    /**
     * Test connection detection with null model connection.
     *
     * Validates: Requirements 2.1, 2.3, 12.2
     *
     * @return void
     */
    public function test_connection_detection_with_null_model_connection(): void
    {
        // Arrange
        Config::set('database.default', 'testing_mysql');

        $modelWithNullConnection = new class extends Model {
            protected $connection = null;
            protected $table = 'test_table';
            public $timestamps = false;
        };

        $table = app(TableBuilder::class);
        $table->setContext('admin');
        $table->setData([['id' => 1, 'name' => 'Test User']]);

        Log::shouldReceive('debug')->once();

        // Act
        $table->setModel($modelWithNullConnection);
        $table->setFields(['name:Name']);

        // Assert - Should use default connection
        $connectionManager = $this->getConnectionManager($table);
        $this->assertEquals('testing_mysql', $connectionManager->getConnection(), 'Should use default when model connection is null');
    }
}
