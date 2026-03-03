<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Table;

use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Tests\TestCase;

/**
 * Test for Configuration Isolation in TableBuilder.
 *
 * This test ensures that configuration doesn't bleed between:
 * - Multiple table instances
 * - Multiple tabs
 * - Multiple calls to lists()
 *
 * @covers \Canvastack\Canvastack\Components\Table\TableBuilder
 * @covers \Canvastack\Canvastack\Components\Table\State\StateManager
 */
class ConfigurationIsolationTest extends TestCase
{
    /**
     * Test that configuration doesn't bleed between table instances.
     */
    public function test_configuration_isolation_between_instances(): void
    {
        // Arrange
        $table1 = app(TableBuilder::class);
        $table2 = app(TableBuilder::class);

        // Act - Configure table1 with dummy data
        $table1->setContext('admin');
        $table1->setData([
            ['col1' => 'test', 'col2' => 'test', 'password' => 'test', 'price' => 100],
        ]);
        $table1->mergeColumns('Merged', ['col1', 'col2']);
        $table1->fixedColumns(2, 1);
        $table1->setHiddenColumns(['password']);

        // Configure table2 differently with its own data
        $table2->setContext('admin');
        $table2->setData([
            ['col3' => 'test', 'col4' => 'test'],
        ]);
        $table2->mergeColumns('Different', ['col3', 'col4']);
        $table2->fixedColumns(1, 0);

        // Assert - table1 configuration unchanged
        $this->assertNotEmpty($table1->getMergedColumns());
        $this->assertEquals(2, $table1->getFixedLeft());
        $this->assertEquals(1, $table1->getFixedRight());
        $this->assertContains('password', $table1->getHiddenColumns());

        // Assert - table2 has its own configuration
        $this->assertNotEmpty($table2->getMergedColumns());
        $this->assertEquals(1, $table2->getFixedLeft());
        $this->assertEquals(0, $table2->getFixedRight());
        $this->assertEmpty($table2->getHiddenColumns());
    }

    /**
     * Test that clearVar clears specific configuration without affecting others.
     */
    public function test_clear_var_isolates_specific_config(): void
    {
        // Arrange
        $table = app(TableBuilder::class);
        $table->setContext('admin');

        // Set dummy data first
        $table->setData([
            ['col1' => 'test', 'col2' => 'test', 'password' => 'test', 'price' => 100],
        ]);

        // Set multiple configurations
        $table->mergeColumns('Merged', ['col1', 'col2']);
        $table->fixedColumns(2, 1);
        $table->setHiddenColumns(['password']);

        // Act - Clear only merged columns
        $table->clearVar('merged_columns');

        // Assert
        $this->assertEmpty($table->getMergedColumns());
        $this->assertEquals(2, $table->getFixedLeft()); // Not cleared
        $this->assertContains('password', $table->getHiddenColumns()); // Not cleared
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
            ['col1' => 'test', 'col2' => 'test', 'password' => 'test', 'price' => 100, 'status' => 'active'],
        ]);

        // Set multiple configurations
        $table->mergeColumns('Merged', ['col1', 'col2']);
        $table->fixedColumns(2, 1);
        $table->setHiddenColumns(['password']);
        $table->columnCondition('status', 'cell', '==', 'active', 'css style', ['class' => 'badge-success']);
        $table->setRightColumns(['price']);

        // Act
        $table->clearOnLoad();

        // Assert - All clearable configs should be cleared
        $config = $table->toArray();
        $this->assertEmpty($config['mergedColumns']);
        $this->assertNull($config['fixedLeft']);
        $this->assertNull($config['fixedRight']);
        $this->assertEmpty($config['hiddenColumns']);
        $this->assertEmpty($config['columnConditions']);
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

        // Set dummy data first
        $table->setData([
            ['col1' => 'test', 'col2' => 'test', 'password' => 'test'],
        ]);

        // Set multiple configurations
        $table->mergeColumns('Merged', ['col1', 'col2']);
        $table->fixedColumns(2, 1);
        $table->setHiddenColumns(['password']);

        // Act
        $table->clearFixedColumns();

        // Assert
        $this->assertNull($table->getFixedLeft());
        $this->assertNull($table->getFixedRight());
        $this->assertNotEmpty($table->getMergedColumns()); // Not cleared
        $this->assertContains('password', $table->getHiddenColumns()); // Not cleared
    }

    /**
     * Test configuration isolation between tabs.
     */
    public function test_configuration_isolation_between_tabs(): void
    {
        // Arrange
        $table = app(TableBuilder::class);
        $table->setContext('admin');

        // Set dummy data first
        $table->setData([
            ['col1' => 'test', 'col2' => 'test', 'col3' => 'test', 'col4' => 'test', 'password' => 'test', 'secret' => 'test'],
        ]);

        // Tab 1 configuration
        $table->openTab('Summary');
        $table->mergeColumns('Summary Merged', ['col1', 'col2']);
        $table->fixedColumns(2, 0);
        $table->setHiddenColumns(['password']);
        $table->setFields(['col1', 'col2']); // Set fields instead of lists()
        $table->closeTab();

        // Tab 2 configuration (different)
        $table->openTab('Detail');
        $table->mergeColumns('Detail Merged', ['col3', 'col4']);
        $table->fixedColumns(1, 1);
        $table->setHiddenColumns(['secret']);
        $table->setFields(['col3', 'col4']); // Set fields instead of lists()
        $table->closeTab();

        // Act - Get tabs
        $tabs = $table->getTabManager()->getTabs();

        // Assert - Each tab should have its own configuration
        $this->assertCount(2, $tabs);
        // Tab IDs are lowercase versions of names
        $this->assertArrayHasKey('summary', $tabs);
        $this->assertArrayHasKey('detail', $tabs);

        // Tab configurations should be isolated
        $summaryConfig = $tabs['summary']->getConfig();
        $detailConfig = $tabs['detail']->getConfig();

        $this->assertNotEquals($summaryConfig, $detailConfig);
    }

    /**
     * Test that clearOnLoad between tabs prevents config bleeding.
     */
    public function test_clear_on_load_prevents_tab_config_bleeding(): void
    {
        // Arrange
        $table = app(TableBuilder::class);
        $table->setContext('admin');

        // Set dummy data to avoid column validation errors
        $table->setData([
            ['col1' => 'test', 'col2' => 'test', 'col3' => 'test', 'col4' => 'test'],
        ]);

        // Tab 1 with configuration
        $table->openTab('Summary');
        $table->mergeColumns('Summary Merged', ['col1', 'col2']);
        $table->fixedColumns(2, 0);
        $table->closeTab();

        // Clear configuration before Tab 2
        $table->clearOnLoad();

        // Tab 2 with different configuration
        $table->openTab('Detail');
        $table->mergeColumns('Detail Merged', ['col3', 'col4']);
        $table->fixedColumns(1, 1);
        $table->closeTab();

        // Assert - clearOnLoad should have cleared the config
        // So the second tab should not have the first tab's config
        $config = $table->toArray();
        
        // After clearOnLoad, mergedColumns should only have Detail's config
        $this->assertNotEmpty($config['mergedColumns']);
        
        // Fixed columns should be from Detail tab (1, 1) not Summary (2, 0)
        $this->assertEquals(1, $config['fixedLeft']);
        $this->assertEquals(1, $config['fixedRight']);
    }

    /**
     * Test configuration isolation with multiple lists() calls.
     */
    public function test_configuration_isolation_with_multiple_lists(): void
    {
        // Arrange
        $table = app(TableBuilder::class);
        $table->setContext('admin');

        // Set dummy data first
        $table->setData([
            ['col1' => 'test', 'col2' => 'test', 'col3' => 'test', 'col4' => 'test', 'id' => 1, 'name' => 'test', 'email' => 'test@test.com'],
        ]);

        // First table configuration
        $table->openTab('Tab1');
        $table->mergeColumns('Merged1', ['col1', 'col2']);
        $table->setFields(['id', 'name']); // Use setFields instead of lists()
        $table->closeTab();

        // Clear and configure second table
        $table->clearOnLoad();
        $table->openTab('Tab2');
        $table->mergeColumns('Merged2', ['col3', 'col4']);
        $table->setFields(['id', 'email']); // Use setFields instead of lists()
        $table->closeTab();

        // Act - Get tabs
        $tabs = $table->getTabManager()->getTabs();

        // Assert - Each tab should have its own table configuration
        $this->assertCount(2, $tabs);

        // Verify tabs exist (tab IDs are lowercase)
        $this->assertArrayHasKey('tab1', $tabs);
        $this->assertArrayHasKey('tab2', $tabs);

        // Configurations should be different
        $tab1Config = $tabs['tab1']->getConfig();
        $tab2Config = $tabs['tab2']->getConfig();

        $this->assertNotEquals($tab1Config, $tab2Config);
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

        // Set dummy data first
        $table->setData([
            ['col1' => 'test', 'col2' => 'test'],
        ]);

        // Act - Make configuration changes using StateManager directly
        $stateManager->saveState('merged_columns', [['label' => 'Merged', 'columns' => ['col1', 'col2']]]);
        $stateManager->saveState('fixed_columns', ['left' => 2, 'right' => 1]);
        $stateManager->saveState('test_config', 'test_value');

        // Assert - State history should track changes
        $history = $stateManager->getStateHistory();
        $this->assertNotEmpty($history);
        $this->assertCount(3, $history);
        
        // Verify history contains our changes
        $this->assertEquals('merged_columns', $history[0]['key']);
        $this->assertEquals('fixed_columns', $history[1]['key']);
        $this->assertEquals('test_config', $history[2]['key']);
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
            [
                'col1' => 'test',
                'col2' => 'test',
                'password' => 'test',
                'secret' => 'test',
                'price' => 100,
                'quantity' => 10,
                'status' => 'active',
                'total' => 200,
            ],
        ]);

        // Set extensive configuration
        $table->mergeColumns('Merged', ['col1', 'col2']);
        $table->fixedColumns(2, 1);
        $table->setHiddenColumns(['password', 'secret']);
        $table->columnCondition('status', 'cell', '==', 'active', 'css style', ['class' => 'badge-success']);
        $table->setRightColumns(['price', 'total']);
        $table->setCenterColumns(['status']);
        $table->displayRowsLimitOnLoad(25);

        // Act
        $table->clearOnLoad();

        // Assert - All should be reset to defaults
        $config = $table->toArray();
        $this->assertEmpty($config['mergedColumns']);
        $this->assertNull($config['fixedLeft']);
        $this->assertNull($config['fixedRight']);
        $this->assertEmpty($config['hiddenColumns']);
        $this->assertEmpty($config['columnConditions']);
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

        // Set dummy data first
        $table->setData([
            ['col1' => 'test', 'col2' => 'test', 'password' => 'test', 'id' => 1, 'name' => 'test', 'email' => 'test@test.com', 'created_at' => '2024-01-01'],
        ]);

        // Act - Set configuration
        $table->mergeColumns('Merged', ['col1', 'col2']);
        $table->fixedColumns(2, 1);
        $table->setHiddenColumns(['password']);

        // Do some other operations
        $table->setFields(['id', 'name', 'email']);
        $table->orderBy('created_at', 'desc');

        // Assert - Configuration should still be there
        $this->assertNotEmpty($table->getMergedColumns());
        $this->assertEquals(2, $table->getFixedLeft());
        $this->assertEquals(1, $table->getFixedRight());
        $this->assertContains('password', $table->getHiddenColumns());
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
     * Test configuration isolation in real-world scenario (Keren Pro style).
     */
    public function test_real_world_keren_pro_scenario(): void
    {
        // Arrange
        $table = app(TableBuilder::class);
        $table->setContext('admin');

        // Set dummy data first
        $table->setData([
            [
                'col1' => 'test',
                'col2' => 'test',
                'col3' => 'test',
                'id' => 1,
                'name' => 'test',
                'total' => 1000,
                'description' => 'test',
                'amount' => 500.50,
                'internal_id' => 'INT001',
                'month' => 'January',
                'year' => '2024',
                'monthly_total' => 5000,
            ],
        ]);

        // Summary Tab
        $table->openTab('Summary');
        $table->clearOnLoad();
        $table->mergeColumns('Summary Header', ['col1', 'col2', 'col3']);
        $table->setFields(['id', 'name', 'total']); // Use setFields instead of lists()
        $table->closeTab();

        // Detail Tab
        $table->openTab('Detail');
        $table->clearOnLoad();
        $table->clearVar('merged_columns'); // Clear merged columns from previous tab
        $table->setHiddenColumns(['internal_id']);
        $table->setFields(['id', 'description', 'amount']); // Use setFields instead of lists()
        $table->closeTab();

        // Monthly Tab
        $table->openTab('Monthly');
        $table->clearOnLoad();
        $table->fixedColumns(2, 0);
        $table->setFields(['month', 'year', 'monthly_total']); // Use setFields instead of lists()
        $table->closeTab();

        // Act - Get tabs
        $tabs = $table->getTabManager()->getTabs();

        // Assert - Each tab should have isolated configuration
        $this->assertCount(3, $tabs);

        // Verify all tabs exist (tab IDs are lowercase)
        $this->assertArrayHasKey('summary', $tabs);
        $this->assertArrayHasKey('detail', $tabs);
        $this->assertArrayHasKey('monthly', $tabs);

        // Each should have different configuration
        $summaryConfig = $tabs['summary']->getConfig();
        $detailConfig = $tabs['detail']->getConfig();
        $monthlyConfig = $tabs['monthly']->getConfig();

        $this->assertNotEquals($summaryConfig, $detailConfig);
        $this->assertNotEquals($detailConfig, $monthlyConfig);
        $this->assertNotEquals($summaryConfig, $monthlyConfig);

        // Summary should have merged columns
        $this->assertNotEmpty($summaryConfig['mergedColumns'] ?? []);

        // Detail should NOT have merged columns (cleared)
        $this->assertEmpty($detailConfig['mergedColumns'] ?? []);

        // Monthly should have fixed columns
        $this->assertNotNull($monthlyConfig['fixedColumns']['left'] ?? null);
    }

    /**
     * Test that configuration doesn't leak between sequential operations.
     */
    public function test_no_configuration_leak_in_sequential_operations(): void
    {
        // Arrange
        $table = app(TableBuilder::class);
        $table->setContext('admin');

        // Set dummy data first
        $table->setData([
            ['col1' => 'test', 'col2' => 'test', 'password' => 'test', 'price' => 100],
        ]);

        // Operation 1
        $table->mergeColumns('Merged', ['col1', 'col2']);
        $table->fixedColumns(2, 1);
        $snapshot1 = [
            'merged' => $table->getMergedColumns(),
            'fixedLeft' => $table->getFixedLeft(),
            'fixedRight' => $table->getFixedRight(),
        ];

        // Clear
        $table->clearOnLoad();

        // Operation 2
        $table->setHiddenColumns(['password']);
        $snapshot2 = [
            'merged' => $table->getMergedColumns(),
            'fixedLeft' => $table->getFixedLeft(),
            'fixedRight' => $table->getFixedRight(),
            'hidden' => $table->getHiddenColumns(),
        ];

        // Assert - Operation 1 config should not leak to Operation 2
        $this->assertNotEmpty($snapshot1['merged']);
        $this->assertEmpty($snapshot2['merged']);
        $this->assertNotNull($snapshot1['fixedLeft']);
        $this->assertNull($snapshot2['fixedLeft']);
        $this->assertNotEmpty($snapshot2['hidden']);
    }
}
