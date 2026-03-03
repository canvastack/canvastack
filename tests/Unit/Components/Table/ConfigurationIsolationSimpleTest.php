<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Table;

use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Tests\TestCase;

/**
 * Simple test for Configuration Isolation in TableBuilder.
 *
 * Tests configuration isolation without complex validation requirements.
 *
 * @covers \Canvastack\Canvastack\Components\Table\TableBuilder
 * @covers \Canvastack\Canvastack\Components\Table\State\StateManager
 */
class ConfigurationIsolationSimpleTest extends TestCase
{
    /**
     * Test that configuration doesn't bleed between table instances.
     */
    public function test_configuration_isolation_between_instances(): void
    {
        // Arrange
        $table1 = app(TableBuilder::class);
        $table2 = app(TableBuilder::class);

        // Act - Configure table1
        $table1->setContext('admin');
        $table1->fixedColumns(2, 1);
        $table1->setHiddenColumns(['password']);
        $table1->displayRowsLimitOnLoad(25);

        // Configure table2 differently
        $table2->setContext('admin');
        $table2->fixedColumns(1, 0);
        $table2->displayRowsLimitOnLoad(50);

        // Assert - table1 configuration unchanged
        $this->assertEquals(2, $table1->getFixedLeft());
        $this->assertEquals(1, $table1->getFixedRight());
        $this->assertContains('password', $table1->getHiddenColumns());
        $this->assertEquals(25, $table1->getDisplayLimit());

        // Assert - table2 has its own configuration
        $this->assertEquals(1, $table2->getFixedLeft());
        $this->assertEquals(0, $table2->getFixedRight());
        $this->assertEmpty($table2->getHiddenColumns());
        $this->assertEquals(50, $table2->getDisplayLimit());
    }

    /**
     * Test that clearVar clears specific configuration without affecting others.
     */
    public function test_clear_var_isolates_specific_config(): void
    {
        // Arrange
        $table = app(TableBuilder::class);
        $table->setContext('admin');

        // Set multiple configurations
        $table->fixedColumns(2, 1);
        $table->setHiddenColumns(['password']);
        $table->displayRowsLimitOnLoad(25);

        // Act - Clear only fixed columns
        $table->clearVar('fixed_columns');

        // Assert
        $this->assertNull($table->getFixedLeft());
        $this->assertNull($table->getFixedRight());
        $this->assertContains('password', $table->getHiddenColumns()); // Not cleared
        $this->assertEquals(25, $table->getDisplayLimit()); // Not cleared
    }

    /**
     * Test that clearOnLoad clears all clearable configuration.
     */
    public function test_clear_on_load_clears_all_clearable_config(): void
    {
        // Arrange
        $table = app(TableBuilder::class);
        $table->setContext('admin');

        // Set dummy data to avoid column validation errors
        $table->setData([
            ['password' => 'test', 'price' => 100],
        ]);

        // Set multiple configurations
        $table->fixedColumns(2, 1);
        $table->setHiddenColumns(['password']);
        $table->setRightColumns(['price']);
        $table->displayRowsLimitOnLoad(25);

        // Act
        $table->clearOnLoad();

        // Assert - All clearable configs should be cleared
        $config = $table->toArray();
        $this->assertNull($config['fixedLeft']);
        $this->assertNull($config['fixedRight']);
        $this->assertEmpty($config['hiddenColumns']);
        $this->assertEmpty($config['columnAlignments']);
        $this->assertEquals(10, $config['displayLimit']); // Reset to default
    }

    /**
     * Test that clearFixedColumns only clears fixed column configuration.
     */
    public function test_clear_fixed_columns_isolates_fixed_config(): void
    {
        // Arrange
        $table = app(TableBuilder::class);
        $table->setContext('admin');

        // Set multiple configurations
        $table->fixedColumns(2, 1);
        $table->setHiddenColumns(['password']);
        $table->displayRowsLimitOnLoad(25);

        // Act
        $table->clearFixedColumns();

        // Assert
        $this->assertNull($table->getFixedLeft());
        $this->assertNull($table->getFixedRight());
        $this->assertContains('password', $table->getHiddenColumns()); // Not cleared
        $this->assertEquals(25, $table->getDisplayLimit()); // Not cleared
    }

    /**
     * Test that StateManager tracks configuration changes.
     */
    public function test_state_manager_tracks_configuration_changes(): void
    {
        // Arrange
        $table = app(TableBuilder::class);
        $table->setContext('admin');
        $stateManager = $table->getStateManager();

        // Act - Make configuration changes
        $table->fixedColumns(2, 1);
        $table->setHiddenColumns(['password']);
        $table->clearVar('fixed_columns');

        // Assert - State history should track changes
        $history = $stateManager->getStateHistory();
        $this->assertNotEmpty($history);
    }

    /**
     * Test configuration reset after clearOnLoad.
     */
    public function test_configuration_reset_after_clear_on_load(): void
    {
        // Arrange
        $table = app(TableBuilder::class);
        $table->setContext('admin');

        // Set dummy data to avoid column validation errors
        $table->setData([
            ['password' => 'test', 'secret' => 'test', 'price' => 100, 'total' => 200, 'status' => 'active'],
        ]);

        // Set extensive configuration
        $table->fixedColumns(2, 1);
        $table->setHiddenColumns(['password', 'secret']);
        $table->setRightColumns(['price', 'total']);
        $table->setCenterColumns(['status']);
        $table->displayRowsLimitOnLoad(25);

        // Act
        $table->clearOnLoad();

        // Assert - All should be reset to defaults
        $config = $table->toArray();
        $this->assertNull($config['fixedLeft']);
        $this->assertNull($config['fixedRight']);
        $this->assertEmpty($config['hiddenColumns']);
        $this->assertEmpty($config['columnAlignments']);
        $this->assertEquals(10, $config['displayLimit']);
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
        $table->fixedColumns(2, 1);
        $table->setHiddenColumns(['password']);
        $table->displayRowsLimitOnLoad(25);

        // Do some other operations
        $table->orderBy('created_at', 'desc');

        // Assert - Configuration should still be there
        $this->assertEquals(2, $table->getFixedLeft());
        $this->assertEquals(1, $table->getFixedRight());
        $this->assertContains('password', $table->getHiddenColumns());
        $this->assertEquals(25, $table->getDisplayLimit());
    }

    /**
     * Test clearVar with invalid property name throws exception.
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
     * Test that configuration doesn't leak between sequential operations.
     */
    public function test_no_configuration_leak_in_sequential_operations(): void
    {
        // Arrange
        $table = app(TableBuilder::class);
        $table->setContext('admin');

        // Operation 1
        $table->fixedColumns(2, 1);
        $table->setHiddenColumns(['password']);
        $snapshot1 = [
            'fixedLeft' => $table->getFixedLeft(),
            'fixedRight' => $table->getFixedRight(),
            'hidden' => $table->getHiddenColumns(),
        ];

        // Clear
        $table->clearOnLoad();

        // Operation 2
        $table->setHiddenColumns(['secret']);
        $table->displayRowsLimitOnLoad(50);
        $snapshot2 = [
            'fixedLeft' => $table->getFixedLeft(),
            'fixedRight' => $table->getFixedRight(),
            'hidden' => $table->getHiddenColumns(),
            'limit' => $table->getDisplayLimit(),
        ];

        // Assert - Operation 1 config should not leak to Operation 2
        $this->assertNotNull($snapshot1['fixedLeft']);
        $this->assertNull($snapshot2['fixedLeft']);
        $this->assertContains('password', $snapshot1['hidden']);
        $this->assertContains('secret', $snapshot2['hidden']);
        $this->assertNotContains('password', $snapshot2['hidden']);
        $this->assertEquals(50, $snapshot2['limit']);
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
        $table1->fixedColumns(2, 1);
        $table1->getStateManager()->saveState('custom_key', 'value1');

        $table2->setContext('admin');
        $table2->fixedColumns(3, 0);
        $table2->getStateManager()->saveState('custom_key', 'value2');

        // Assert
        $this->assertEquals('value1', $table1->getStateManager()->getState('custom_key'));
        $this->assertEquals('value2', $table2->getStateManager()->getState('custom_key'));
        $this->assertEquals(2, $table1->getFixedLeft());
        $this->assertEquals(3, $table2->getFixedLeft());
    }

    /**
     * Test clearClearableVars only clears defined clearable variables.
     */
    public function test_clear_clearable_vars_selective(): void
    {
        // Arrange
        $table = app(TableBuilder::class);
        $table->setContext('admin');
        $stateManager = $table->getStateManager();

        // Set clearable and non-clearable state
        $stateManager->saveState('merged_columns', ['col1', 'col2']);
        $stateManager->saveState('fixed_columns', [2, 1]);
        $stateManager->saveState('custom_var', 'custom_value');

        // Act
        $stateManager->clearClearableVars();

        // Assert
        $this->assertFalse($stateManager->hasState('merged_columns'));
        $this->assertFalse($stateManager->hasState('fixed_columns'));
        $this->assertTrue($stateManager->hasState('custom_var')); // Not cleared
    }
}
