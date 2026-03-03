<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Table;

use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Test for StateManager integration with TableBuilder.
 *
 * Verifies that StateManager is properly integrated and used
 * for configuration management in TableBuilder.
 *
 * @covers \Canvastack\Canvastack\Components\Table\TableBuilder
 * @covers \Canvastack\Canvastack\Components\Table\State\StateManager
 */
class StateManagerIntegrationTest extends TestCase
{
    protected TableBuilder $table;

    protected function setUp(): void
    {
        parent::setUp();
        $this->table = $this->app->make(TableBuilder::class);
    }

    /**
     * Test that StateManager property is initialized.
     */
    public function test_state_manager_property_is_initialized(): void
    {
        // Assert
        $reflection = new \ReflectionClass($this->table);
        $property = $reflection->getProperty('stateManager');
        $property->setAccessible(true);
        $stateManager = $property->getValue($this->table);

        $this->assertInstanceOf(
            \Canvastack\Canvastack\Components\Table\State\StateManager::class,
            $stateManager,
            'StateManager should be initialized in TableBuilder constructor'
        );
    }

    /**
     * Test that clearVar() uses StateManager.
     */
    public function test_clear_var_uses_state_manager(): void
    {
        // Arrange
        $this->table->setName('users');
        $this->table->setFields(['name:Name', 'email:Email']);
        
        // Set some configuration
        $this->table->fixedColumns(2, 1);
        $this->table->setHiddenColumns(['id']);

        // Act - Clear fixed columns
        $this->table->clearVar('fixed_columns');

        // Assert - Fixed columns should be cleared
        $reflection = new \ReflectionClass($this->table);
        
        $fixedLeftProp = $reflection->getProperty('fixedLeft');
        $fixedLeftProp->setAccessible(true);
        $this->assertNull($fixedLeftProp->getValue($this->table));
        
        $fixedRightProp = $reflection->getProperty('fixedRight');
        $fixedRightProp->setAccessible(true);
        $this->assertNull($fixedRightProp->getValue($this->table));
    }

    /**
     * Test that clearOnLoad() uses StateManager.
     */
    public function test_clear_on_load_uses_state_manager(): void
    {
        // Arrange
        $this->table->setName('users');
        $this->table->setFields(['name:Name', 'email:Email']);
        
        // Set various configurations
        $this->table->fixedColumns(2, 1);
        $this->table->setHiddenColumns(['id']);
        $this->table->setRightColumns(['email']);
        $this->table->displayRowsLimitOnLoad(50);

        // Act - Clear all configuration
        $this->table->clearOnLoad();

        // Assert - All clearable configuration should be reset
        $reflection = new \ReflectionClass($this->table);
        
        // Check fixed columns cleared
        $fixedLeftProp = $reflection->getProperty('fixedLeft');
        $fixedLeftProp->setAccessible(true);
        $this->assertNull($fixedLeftProp->getValue($this->table));
        
        // Check hidden columns cleared
        $hiddenColumnsProp = $reflection->getProperty('hiddenColumns');
        $hiddenColumnsProp->setAccessible(true);
        $this->assertEmpty($hiddenColumnsProp->getValue($this->table));
        
        // Check alignments cleared
        $alignmentsProp = $reflection->getProperty('columnAlignments');
        $alignmentsProp->setAccessible(true);
        $this->assertEmpty($alignmentsProp->getValue($this->table));
        
        // Check display limit reset to default
        $displayLimitProp = $reflection->getProperty('displayLimit');
        $displayLimitProp->setAccessible(true);
        $this->assertEquals(10, $displayLimitProp->getValue($this->table));
    }

    /**
     * Test that clearFixedColumns() uses StateManager.
     */
    public function test_clear_fixed_columns_uses_state_manager(): void
    {
        // Arrange
        $this->table->setName('users');
        $this->table->setFields(['name:Name', 'email:Email']);
        $this->table->fixedColumns(2, 1);

        // Act
        $this->table->clearFixedColumns();

        // Assert
        $reflection = new \ReflectionClass($this->table);
        
        $fixedLeftProp = $reflection->getProperty('fixedLeft');
        $fixedLeftProp->setAccessible(true);
        $this->assertNull($fixedLeftProp->getValue($this->table));
        
        $fixedRightProp = $reflection->getProperty('fixedRight');
        $fixedRightProp->setAccessible(true);
        $this->assertNull($fixedRightProp->getValue($this->table));
    }

    /**
     * Test that configuration capture uses StateManager.
     */
    public function test_configuration_capture_uses_state_manager(): void
    {
        // Arrange
        $this->table->setName('users');
        $this->table->setFields(['name:Name', 'email:Email']);
        $this->table->fixedColumns(2, 1);
        $this->table->setHiddenColumns(['id']);
        $this->table->displayRowsLimitOnLoad(25);

        // Act - Capture configuration (called internally by format())
        $reflection = new \ReflectionClass($this->table);
        $method = $reflection->getMethod('captureCurrentConfig');
        $method->setAccessible(true);
        $config = $method->invoke($this->table);

        // Assert - Configuration should be captured
        $this->assertIsArray($config);
        $this->assertArrayHasKey('fixedColumns', $config);
        $this->assertArrayHasKey('hiddenColumns', $config);
        $this->assertArrayHasKey('displayLimit', $config);
        
        $this->assertEquals(2, $config['fixedColumns']['left']);
        $this->assertEquals(1, $config['fixedColumns']['right']);
        $this->assertEquals(['id'], $config['hiddenColumns']);
        $this->assertEquals(25, $config['displayLimit']);
    }

    /**
     * Test that StateManager prevents configuration bleeding between operations.
     */
    public function test_state_manager_prevents_config_bleeding(): void
    {
        // Arrange - First configuration
        $this->table->setName('users');
        $this->table->setFields(['name:Name', 'email:Email']);
        $this->table->fixedColumns(2, 1);
        $this->table->setHiddenColumns(['id']);

        // Capture first config
        $reflection = new \ReflectionClass($this->table);
        $method = $reflection->getMethod('captureCurrentConfig');
        $method->setAccessible(true);
        $config1 = $method->invoke($this->table);

        // Act - Clear and set new configuration
        $this->table->clearOnLoad();
        $this->table->fixedColumns(1, 0);
        $this->table->setHiddenColumns(['email']);

        // Capture second config
        $config2 = $method->invoke($this->table);

        // Assert - Configurations should be different
        $this->assertNotEquals($config1['fixedColumns'], $config2['fixedColumns']);
        $this->assertNotEquals($config1['hiddenColumns'], $config2['hiddenColumns']);
        
        $this->assertEquals(2, $config1['fixedColumns']['left']);
        $this->assertEquals(1, $config2['fixedColumns']['left']);
        
        $this->assertEquals(['id'], $config1['hiddenColumns']);
        $this->assertEquals(['email'], $config2['hiddenColumns']);
    }

    /**
     * Test that clearVar() handles multiple variable types.
     */
    public function test_clear_var_handles_multiple_types(): void
    {
        // Arrange
        $this->table->setName('users');
        $this->table->setFields(['name:Name', 'email:Email']);
        $this->table->fixedColumns(2, 1);
        $this->table->setHiddenColumns(['id']);
        $this->table->setRightColumns(['email']);

        // Act - Clear different types of variables
        $this->table->clearVar('fixed_columns');
        $this->table->clearVar('hidden_columns');
        $this->table->clearVar('columnAlignments');

        // Assert
        $reflection = new \ReflectionClass($this->table);
        
        $fixedLeftProp = $reflection->getProperty('fixedLeft');
        $fixedLeftProp->setAccessible(true);
        $this->assertNull($fixedLeftProp->getValue($this->table));
        
        $hiddenColumnsProp = $reflection->getProperty('hiddenColumns');
        $hiddenColumnsProp->setAccessible(true);
        $this->assertEmpty($hiddenColumnsProp->getValue($this->table));
        
        $alignmentsProp = $reflection->getProperty('columnAlignments');
        $alignmentsProp->setAccessible(true);
        $this->assertEmpty($alignmentsProp->getValue($this->table));
    }

    /**
     * Test that StateManager maintains state isolation.
     */
    public function test_state_manager_maintains_isolation(): void
    {
        // Arrange - Create two table instances
        $table1 = $this->app->make(TableBuilder::class);
        $table2 = $this->app->make(TableBuilder::class);

        $table1->setName('users');
        $table1->setFields(['name:Name']);
        $table1->fixedColumns(2, 1);

        $table2->setName('users');
        $table2->setFields(['email:Email']);
        $table2->fixedColumns(1, 0);

        // Act - Get configurations
        $reflection = new \ReflectionClass($table1);
        $method = $reflection->getMethod('captureCurrentConfig');
        $method->setAccessible(true);
        
        $config1 = $method->invoke($table1);
        $config2 = $method->invoke($table2);

        // Assert - Configurations should be independent
        $this->assertNotEquals($config1['fixedColumns'], $config2['fixedColumns']);
        $this->assertEquals(2, $config1['fixedColumns']['left']);
        $this->assertEquals(1, $config2['fixedColumns']['left']);
    }
}
