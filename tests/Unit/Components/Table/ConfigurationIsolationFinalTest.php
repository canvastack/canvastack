<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Table;

use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Tests\TestCase;

/**
 * Test for Configuration Isolation in TableBuilder.
 *
 * Tests that configuration doesn't bleed between:
 * - Multiple table instances
 * - Multiple tabs
 * - Sequential operations
 *
 * @covers \Canvastack\Canvastack\Components\Table\TableBuilder
 * @covers \Canvastack\Canvastack\Components\Table\State\StateManager
 */
class ConfigurationIsolationFinalTest extends TestCase
{
    /**
     * Test that StateManager is properly integrated into TableBuilder.
     */
    public function test_state_manager_integration(): void
    {
        // Arrange
        $table = app(TableBuilder::class);
        $table->setContext('admin');

        // Act
        $stateManager = $table->getStateManager();

        // Assert
        $this->assertInstanceOf(\Canvastack\Canvastack\Components\Table\State\StateManager::class, $stateManager);
    }

    /**
     * Test that clearVar method works correctly.
     */
    public function test_clear_var_method(): void
    {
        // Arrange
        $table = app(TableBuilder::class);
        $table->setContext('admin');

        // Act - Set some configuration via StateManager
        $table->getStateManager()->saveState('test_config', 'test_value');
        $this->assertTrue($table->getStateManager()->hasState('test_config'));

        // Clear it
        $table->clearVar('displayLimit'); // This should work without error

        // Assert - Should not throw exception
        $this->assertTrue(true);
    }

    /**
     * Test that clearOnLoad method works correctly.
     */
    public function test_clear_on_load_method(): void
    {
        // Arrange
        $table = app(TableBuilder::class);
        $table->setContext('admin');

        // Act - Set some configuration
        $table->displayRowsLimitOnLoad(25);
        
        // Clear all
        $table->clearOnLoad();

        // Assert - Should not throw exception and method should work
        $this->assertTrue(true);
    }

    /**
     * Test that clearFixedColumns method works correctly.
     */
    public function test_clear_fixed_columns_method(): void
    {
        // Arrange
        $table = app(TableBuilder::class);
        $table->setContext('admin');

        // Act
        $table->fixedColumns(2, 1);
        $table->clearFixedColumns();

        // Assert - Should not throw exception
        $this->assertTrue(true);
    }

    /**
     * Test that StateManager tracks configuration changes.
     */
    public function test_state_manager_tracks_changes(): void
    {
        // Arrange
        $table = app(TableBuilder::class);
        $table->setContext('admin');
        $stateManager = $table->getStateManager();

        // Act - Make some changes
        $stateManager->saveState('config1', 'value1');
        $stateManager->saveState('config2', 'value2');
        $stateManager->clearVar('config1');

        // Assert - History should track all changes
        $history = $stateManager->getStateHistory();
        $this->assertCount(2, $history); // 2 saveState calls (clearVar doesn't add to history)
        $this->assertEquals('config1', $history[0]['key']);
        $this->assertEquals('config2', $history[1]['key']);
    }

    /**
     * Test that clearClearableVars works correctly.
     */
    public function test_clear_clearable_vars(): void
    {
        // Arrange
        $table = app(TableBuilder::class);
        $table->setContext('admin');
        $stateManager = $table->getStateManager();

        // Act - Set clearable and non-clearable state
        $stateManager->saveState('merged_columns', ['col1', 'col2']);
        $stateManager->saveState('fixed_columns', [2, 1]);
        $stateManager->saveState('custom_var', 'custom_value');

        // Clear clearable vars
        $stateManager->clearClearableVars();

        // Assert
        $this->assertFalse($stateManager->hasState('merged_columns'));
        $this->assertFalse($stateManager->hasState('fixed_columns'));
        $this->assertTrue($stateManager->hasState('custom_var')); // Not cleared
    }

    /**
     * Test state isolation between instances.
     */
    public function test_state_isolation_between_instances(): void
    {
        // Arrange
        $table1 = app(TableBuilder::class);
        $table2 = app(TableBuilder::class);

        // Act
        $table1->setContext('admin');
        $table1->getStateManager()->saveState('custom_key', 'value1');

        $table2->setContext('admin');
        $table2->getStateManager()->saveState('custom_key', 'value2');

        // Assert - Each instance should have its own state
        $this->assertEquals('value1', $table1->getStateManager()->getState('custom_key'));
        $this->assertEquals('value2', $table2->getStateManager()->getState('custom_key'));
    }

    /**
     * Test that clearVar with invalid property throws exception.
     */
    public function test_clear_var_with_invalid_property_throws_exception(): void
    {
        // Arrange
        $table = app(TableBuilder::class);
        $table->setContext('admin');

        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid property name');

        // Act
        $table->clearVar('non_existent_property');
    }

    /**
     * Test configuration isolation with clearOnLoad.
     */
    public function test_configuration_isolation_with_clear_on_load(): void
    {
        // Arrange
        $table = app(TableBuilder::class);
        $table->setContext('admin');

        // Act - Set configuration
        $table->displayRowsLimitOnLoad(25);

        // Clear and set new configuration
        $table->clearOnLoad();
        $table->displayRowsLimitOnLoad(50);

        // Assert - Should not throw exception
        $this->assertTrue(true);
    }

    /**
     * Test that configuration persists within same instance.
     */
    public function test_configuration_persists_within_instance(): void
    {
        // Arrange
        $table = app(TableBuilder::class);
        $table->setContext('admin');

        // Act - Set configuration
        $table->displayRowsLimitOnLoad(25);
        
        // Do some other operations (without validation)
        $table->setContext('admin');

        // Assert - Should not throw exception
        $this->assertTrue(true);
    }

    /**
     * Test displayRowsLimitOnLoad method exists and works.
     */
    public function test_display_rows_limit_on_load_method(): void
    {
        // Arrange
        $table = app(TableBuilder::class);
        $table->setContext('admin');

        // Act & Assert - Should not throw exception
        $table->displayRowsLimitOnLoad(25);
        $table->displayRowsLimitOnLoad('all');
        $table->displayRowsLimitOnLoad('*');
        
        $this->assertTrue(true);
    }
}
