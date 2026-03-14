<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Table;

use Canvastack\Canvastack\Components\Table\ConnectionManager;
use Canvastack\Canvastack\Components\Table\WarningSystem;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Mockery;

/**
 * Test for ConnectionManager component.
 * 
 * Tests connection detection, priority resolution, override detection,
 * and mismatch detection functionality.
 */
class ConnectionManagerTest extends TestCase
{
    /**
     * ConnectionManager instance.
     * 
     * @var ConnectionManager
     */
    protected ConnectionManager $manager;

    /**
     * Mock WarningSystem instance.
     * 
     * @var WarningSystem
     */
    protected $warningSystem;

    /**
     * Setup test environment.
     * 
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Mock WarningSystem
        $this->warningSystem = Mockery::mock(WarningSystem::class);

        // Create ConnectionManager instance
        $this->manager = new ConnectionManager($this->warningSystem);
    }

    /**
     * Teardown test environment.
     * 
     * @return void
     */
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test that detectConnection() returns default when no model provided.
     * 
     * @return void
     */
    public function test_detect_connection_returns_default_when_no_model(): void
    {
        // Arrange
        config(['database.default' => 'mysql']);
        Log::shouldReceive('debug')
            ->once()
            ->with('ConnectionManager: No model provided, using default connection', [
                'connection' => 'mysql',
            ]);

        // Act
        $connection = $this->manager->detectConnection();

        // Assert
        $this->assertEquals('mysql', $connection);
        $this->assertEquals('mysql', $this->manager->getDetectedConnection());
    }

    /**
     * Test that detectConnection() gets connection from model.
     * 
     * @return void
     */
    public function test_detect_connection_gets_connection_from_model(): void
    {
        // Arrange
        $model = Mockery::mock(Model::class);
        $model->shouldReceive('getConnectionName')
            ->once()
            ->andReturn('pgsql');

        Log::shouldReceive('debug')
            ->once()
            ->with('ConnectionManager: Detected connection from model', [
                'model' => get_class($model),
                'connection' => 'pgsql',
            ]);

        // Act
        $connection = $this->manager->detectConnection($model);

        // Assert
        $this->assertEquals('pgsql', $connection);
        $this->assertEquals('pgsql', $this->manager->getDetectedConnection());
        $this->assertSame($model, $this->manager->getModel());
    }

    /**
     * Test that detectConnection() logs at debug level.
     * 
     * @return void
     */
    public function test_detect_connection_logs_at_debug_level(): void
    {
        // Arrange
        $model = Mockery::mock(Model::class);
        $model->shouldReceive('getConnectionName')
            ->once()
            ->andReturn('sqlite');

        // Expect debug log
        Log::shouldReceive('debug')
            ->once()
            ->with('ConnectionManager: Detected connection from model', Mockery::type('array'));

        // Act
        $connection = $this->manager->detectConnection($model);

        // Assert - Log expectation verified by Mockery
        $this->assertEquals('sqlite', $connection);
        $this->assertEquals('sqlite', $this->manager->getDetectedConnection());
    }

    /**
     * Test that setOverride() sets override connection.
     * 
     * @return void
     */
    public function test_set_override_sets_override_connection(): void
    {
        // Arrange
        Log::shouldReceive('debug')
            ->once()
            ->with('ConnectionManager: Connection override set', [
                'override' => 'custom',
                'detected' => null,
            ]);

        // Act
        $result = $this->manager->setOverride('custom');

        // Assert
        $this->assertSame($this->manager, $result); // Method chaining
        $this->assertEquals('custom', $this->manager->getOverrideConnection());
        $this->assertTrue($this->manager->hasOverride());
    }

    /**
     * Test that getConnection() follows priority: override > model > default.
     * 
     * @return void
     */
    public function test_get_connection_priority_override_first(): void
    {
        // Arrange
        config(['database.default' => 'mysql']);
        
        $model = Mockery::mock(Model::class);
        $model->shouldReceive('getConnectionName')->andReturn('pgsql');
        
        Log::shouldReceive('debug')->twice(); // detectConnection + setOverride

        $this->manager->detectConnection($model);
        $this->manager->setOverride('custom');

        // Act
        $connection = $this->manager->getConnection();

        // Assert - Override takes priority
        $this->assertEquals('custom', $connection);
    }

    /**
     * Test that getConnection() uses model connection when no override.
     * 
     * @return void
     */
    public function test_get_connection_priority_model_second(): void
    {
        // Arrange
        config(['database.default' => 'mysql']);
        
        $model = Mockery::mock(Model::class);
        $model->shouldReceive('getConnectionName')->andReturn('pgsql');
        
        Log::shouldReceive('debug')->once();

        $this->manager->detectConnection($model);

        // Act
        $connection = $this->manager->getConnection();

        // Assert - Model connection used
        $this->assertEquals('pgsql', $connection);
    }

    /**
     * Test that getConnection() uses default when no override or model.
     * 
     * @return void
     */
    public function test_get_connection_priority_default_last(): void
    {
        // Arrange
        config(['database.default' => 'mysql']);

        // Act
        $connection = $this->manager->getConnection();

        // Assert - Default used
        $this->assertEquals('mysql', $connection);
    }

    /**
     * Test that hasOverride() returns false when no override set.
     * 
     * @return void
     */
    public function test_has_override_returns_false_when_no_override(): void
    {
        // Act & Assert
        $this->assertFalse($this->manager->hasOverride());
    }

    /**
     * Test that hasOverride() returns true when override set.
     * 
     * @return void
     */
    public function test_has_override_returns_true_when_override_set(): void
    {
        // Arrange
        Log::shouldReceive('debug')->once();
        $this->manager->setOverride('custom');

        // Act & Assert
        $this->assertTrue($this->manager->hasOverride());
    }

    /**
     * Test that hasConnectionMismatch() returns false when no override.
     * 
     * @return void
     */
    public function test_has_connection_mismatch_returns_false_when_no_override(): void
    {
        // Arrange
        $model = Mockery::mock(Model::class);
        $model->shouldReceive('getConnectionName')->andReturn('pgsql');
        Log::shouldReceive('debug')->once();

        $this->manager->detectConnection($model);

        // Act & Assert
        $this->assertFalse($this->manager->hasConnectionMismatch());
    }

    /**
     * Test that hasConnectionMismatch() returns false when no detected connection.
     * 
     * @return void
     */
    public function test_has_connection_mismatch_returns_false_when_no_detected(): void
    {
        // Arrange
        Log::shouldReceive('debug')->once();
        $this->manager->setOverride('custom');

        // Act & Assert
        $this->assertFalse($this->manager->hasConnectionMismatch());
    }

    /**
     * Test that hasConnectionMismatch() returns false when connections match.
     * 
     * @return void
     */
    public function test_has_connection_mismatch_returns_false_when_connections_match(): void
    {
        // Arrange
        $model = Mockery::mock(Model::class);
        $model->shouldReceive('getConnectionName')->andReturn('pgsql');
        Log::shouldReceive('debug')->twice();

        $this->manager->detectConnection($model);
        $this->manager->setOverride('pgsql'); // Same as detected

        // Act & Assert
        $this->assertFalse($this->manager->hasConnectionMismatch());
    }

    /**
     * Test that hasConnectionMismatch() returns true when connections differ.
     * 
     * @return void
     */
    public function test_has_connection_mismatch_returns_true_when_connections_differ(): void
    {
        // Arrange
        $model = Mockery::mock(Model::class);
        $model->shouldReceive('getConnectionName')->andReturn('pgsql');
        Log::shouldReceive('debug')->twice();

        $this->manager->detectConnection($model);
        $this->manager->setOverride('mysql'); // Different from detected

        // Act & Assert
        $this->assertTrue($this->manager->hasConnectionMismatch());
    }

    /**
     * Test that reset() clears all connections and model.
     * 
     * @return void
     */
    public function test_reset_clears_all_connections_and_model(): void
    {
        // Arrange
        $model = Mockery::mock(Model::class);
        $model->shouldReceive('getConnectionName')->andReturn('pgsql');
        Log::shouldReceive('debug')->times(3); // detect + override + reset

        $this->manager->detectConnection($model);
        $this->manager->setOverride('custom');

        // Act
        $result = $this->manager->reset();

        // Assert
        $this->assertSame($this->manager, $result); // Method chaining
        $this->assertNull($this->manager->getDetectedConnection());
        $this->assertNull($this->manager->getOverrideConnection());
        $this->assertNull($this->manager->getModel());
        $this->assertFalse($this->manager->hasOverride());
        $this->assertFalse($this->manager->hasConnectionMismatch());
    }

    /**
     * Test that getWarningSystem() returns injected warning system.
     * 
     * @return void
     */
    public function test_get_warning_system_returns_injected_instance(): void
    {
        // Act
        $warningSystem = $this->manager->getWarningSystem();

        // Assert
        $this->assertSame($this->warningSystem, $warningSystem);
    }

    /**
     * Test that detectConnection() supports models with custom connection configurations.
     * 
     * @return void
     */
    public function test_detect_connection_supports_custom_model_connections(): void
    {
        // Arrange
        $model = Mockery::mock(Model::class);
        $model->shouldReceive('getConnectionName')
            ->once()
            ->andReturn('custom_connection');

        Log::shouldReceive('debug')->once();

        // Act
        $connection = $this->manager->detectConnection($model);

        // Assert
        $this->assertEquals('custom_connection', $connection);
    }

    /**
     * Test that detectConnection() can be called multiple times with different models.
     * 
     * @return void
     */
    public function test_detect_connection_can_be_called_multiple_times(): void
    {
        // Arrange
        $model1 = Mockery::mock(Model::class);
        $model1->shouldReceive('getConnectionName')->andReturn('mysql');

        $model2 = Mockery::mock(Model::class);
        $model2->shouldReceive('getConnectionName')->andReturn('pgsql');

        Log::shouldReceive('debug')->twice();

        // Act
        $connection1 = $this->manager->detectConnection($model1);
        $connection2 = $this->manager->detectConnection($model2);

        // Assert
        $this->assertEquals('mysql', $connection1);
        $this->assertEquals('pgsql', $connection2);
        $this->assertEquals('pgsql', $this->manager->getDetectedConnection()); // Latest
        $this->assertSame($model2, $this->manager->getModel()); // Latest
    }

    /**
     * Test that detectConnection() stores model reference for later use.
     * 
     * @return void
     */
    public function test_detect_connection_stores_model_reference(): void
    {
        // Arrange
        $model = Mockery::mock(Model::class);
        $model->shouldReceive('getConnectionName')->andReturn('mysql');
        Log::shouldReceive('debug')->once();

        // Act
        $this->manager->detectConnection($model);

        // Assert
        $this->assertSame($model, $this->manager->getModel());
    }

    /**
     * Test that detectConnection() uses default when model returns null.
     * 
     * @return void
     */
    public function test_detect_connection_uses_default_when_model_returns_null(): void
    {
        // Arrange
        config(['database.default' => 'mysql']);
        
        $model = Mockery::mock(Model::class);
        $model->shouldReceive('getConnectionName')
            ->once()
            ->andReturn(null);

        Log::shouldReceive('debug')
            ->once()
            ->with('ConnectionManager: Model has no connection, using default', [
                'model' => get_class($model),
                'connection' => 'mysql',
            ]);

        // Act
        $connection = $this->manager->detectConnection($model);

        // Assert
        $this->assertEquals('mysql', $connection);
        $this->assertEquals('mysql', $this->manager->getDetectedConnection());
        $this->assertSame($model, $this->manager->getModel());
    }
}
