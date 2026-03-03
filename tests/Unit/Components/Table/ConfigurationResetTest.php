<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Table;

use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Tests\TestCase;

/**
 * Test configuration reset functionality.
 *
 * Tests Requirements:
 * - 1.2.3: Implement configuration reset
 * - Verify resetConfiguration() resets all properties to defaults
 * - Verify clearOnLoad() delegates to resetConfiguration()
 * - Verify StateManager integration
 * - Verify tab configuration clearing
 */
class ConfigurationResetTest extends TestCase
{
    protected TableBuilder $table;

    protected function setUp(): void
    {
        parent::setUp();
        $this->table = app(TableBuilder::class);
        $this->table->setContext('admin');
        
        // Set dummy data to avoid validation errors
        $this->table->setData([
            [
                'id' => 1,
                'name' => 'Test',
                'email' => 'test@example.com',
                'status' => 'active',
                'price' => 100,
                'total' => 200,
                'col1' => 'test',
                'col2' => 'test',
                'password' => 'secret',
            ],
        ]);
    }

    /**
     * Test that resetConfiguration() resets display options.
     */
    public function test_reset_configuration_resets_display_options(): void
    {
        // Arrange - Set non-default display options
        $this->table->displayRowsLimitOnLoad(50);
        $this->table->setDatatableType(false);
        $this->table->setServerSide(false);
        
        // Act
        $this->table->resetConfiguration();
        
        // Assert - All display options should be reset to defaults
        $config = $this->table->toArray();
        $this->assertEquals(10, $config['displayLimit']);
        $this->assertTrue($config['isDatatable']);
        $this->assertTrue($config['serverSide']);
    }

    /**
     * Test that resetConfiguration() resets column configuration.
     */
    public function test_reset_configuration_resets_column_configuration(): void
    {
        // Arrange - Set column configuration
        $this->table->mergeColumns('Merged', ['col1', 'col2']);
        $this->table->fixedColumns(2, 1);
        $this->table->setHiddenColumns(['password']);
        $this->table->setRightColumns(['price', 'total']);
        $this->table->setCenterColumns(['status']);
        $this->table->setBackgroundColor('#ff0000', '#ffffff', ['status']);
        
        // Act
        $this->table->resetConfiguration();
        
        // Assert - All column configuration should be reset
        $config = $this->table->toArray();
        $this->assertEmpty($config['mergedColumns']);
        $this->assertNull($config['fixedLeft']);
        $this->assertNull($config['fixedRight']);
        $this->assertEmpty($config['hiddenColumns']);
        $this->assertEmpty($config['columnAlignments']);
        $this->assertEmpty($config['columnColors']);
    }

    /**
     * Test that resetConfiguration() resets formatting and conditions.
     */
    public function test_reset_configuration_resets_formatting_and_conditions(): void
    {
        // Arrange - Set formatting and conditions
        $this->table->columnCondition('status', 'cell', '==', 'active', 'css style', ['class' => 'badge-success']);
        
        // Act
        $this->table->resetConfiguration();
        
        // Assert - All formatting should be reset
        $config = $this->table->toArray();
        $this->assertEmpty($config['columnConditions']);
        $this->assertEmpty($config['formats']);
    }

    /**
     * Test that resetConfiguration() resets filters and conditions.
     */
    public function test_reset_configuration_resets_filters_and_conditions(): void
    {
        // Arrange - Set filters and conditions
        $this->table->where('status', '=', 'active');
        $this->table->whereIn('id', [1, 2, 3]);
        $this->table->addFilters(['category' => 'test']);
        
        // Act
        $this->table->resetConfiguration();
        
        // Assert - All filters should be reset
        $config = $this->table->toArray();
        $this->assertEmpty($config['filterGroups']);
        $this->assertEmpty($config['filters']);
        // Note: whereConditions is not exposed in toArray(), but it's reset internally
    }

    /**
     * Test that resetConfiguration() resets actions.
     */
    public function test_reset_configuration_resets_actions(): void
    {
        // Arrange - Set actions
        $this->table->addAction('view', '/users/:id', 'eye', 'View');
        $this->table->addAction('edit', '/users/:id/edit', 'edit', 'Edit');
        $this->table->removeButtons(['delete']);
        
        // Act
        $this->table->resetConfiguration();
        
        // Assert - All actions should be reset
        $config = $this->table->toArray();
        $this->assertEmpty($config['actions']);
        $this->assertEmpty($config['removedButtons']);
    }

    /**
     * Test that resetConfiguration() resets sorting and searching.
     */
    public function test_reset_configuration_resets_sorting_and_searching(): void
    {
        // Arrange - Set sorting
        $this->table->orderBy('created_at', 'desc');
        
        // Act
        $this->table->resetConfiguration();
        
        // Assert - All sorting should be reset
        $config = $this->table->toArray();
        $this->assertNull($config['orderColumn']);
        $this->assertEquals('asc', $config['orderDirection']);
        $this->assertNull($config['sortableColumns']);
        $this->assertNull($config['searchableColumns']);
    }

    /**
     * Test that resetConfiguration() resets relations.
     */
    public function test_reset_configuration_resets_relations(): void
    {
        // Arrange - Set relations
        $this->table->eager(['user', 'category']);
        $this->table->with('tags');
        
        // Act
        $this->table->resetConfiguration();
        
        // Assert - All relations should be reset
        $this->assertEmpty($this->table->getEagerLoad());
    }

    /**
     * Test that resetConfiguration() integrates with StateManager.
     */
    public function test_reset_configuration_integrates_with_state_manager(): void
    {
        // Arrange - Set configuration that StateManager tracks
        $stateManager = $this->table->getStateManager();
        $stateManager->saveState('merged_columns', ['col1', 'col2']);
        $stateManager->saveState('fixed_columns', ['left' => 2, 'right' => 1]);
        $stateManager->saveState('formats', ['col1' => 2]);
        
        // Act
        $this->table->resetConfiguration();
        
        // Assert - StateManager should have cleared all clearable vars
        $this->assertNull($stateManager->getState('merged_columns'));
        $this->assertNull($stateManager->getState('fixed_columns'));
        $this->assertNull($stateManager->getState('formats'));
    }

    /**
     * Test that resetConfiguration() clears tab configuration.
     */
    public function test_reset_configuration_clears_tab_configuration(): void
    {
        // Arrange - Create tabs
        $this->table->openTab('Tab1');
        $this->table->mergeColumns('Merged1', ['col1', 'col2']);
        $this->table->closeTab();
        
        $this->table->openTab('Tab2');
        $this->table->fixedColumns(2, 0);
        $this->table->closeTab();
        
        // Act
        $this->table->resetConfiguration();
        
        // Assert - Tab configuration should be cleared
        $tabManager = $this->table->getTabManager();
        $this->assertFalse($tabManager->hasTabs());
    }

    /**
     * Test that clearOnLoad() clears configuration but preserves tabs.
     */
    public function test_clear_on_load_clears_configuration_but_preserves_tabs(): void
    {
        // Arrange - Create tabs with configuration
        $this->table->openTab('Tab1');
        $this->table->mergeColumns('Merged1', ['col1', 'col2']);
        $this->table->closeTab();
        
        $this->table->openTab('Tab2');
        $this->table->fixedColumns(2, 0);
        $this->table->closeTab();
        
        // Act
        $this->table->clearOnLoad();
        
        // Assert - Tabs should be preserved, but configuration cleared
        $tabManager = $this->table->getTabManager();
        $this->assertTrue($tabManager->hasTabs());
        $this->assertEquals(2, $tabManager->count());
        
        // Configuration should be cleared
        $config = $this->table->toArray();
        $this->assertEmpty($config['mergedColumns']);
        $this->assertNull($config['fixedLeft']);
    }

    /**
     * Test that clearOnLoad() clears all configuration properties.
     */
    public function test_clear_on_load_clears_all_configuration(): void
    {
        // Arrange - Set extensive configuration
        $this->table->mergeColumns('Merged', ['col1', 'col2']);
        $this->table->fixedColumns(2, 1);
        $this->table->setHiddenColumns(['password']);
        $this->table->displayRowsLimitOnLoad(25);
        $this->table->addAction('view', '/users/:id', 'eye', 'View');
        
        // Act
        $this->table->clearOnLoad();
        
        // Assert - All configuration should be cleared
        $config = $this->table->toArray();
        $this->assertEmpty($config['mergedColumns']);
        $this->assertNull($config['fixedLeft']);
        $this->assertNull($config['fixedRight']);
        $this->assertEmpty($config['hiddenColumns']);
        $this->assertEquals(10, $config['displayLimit']);
        $this->assertEmpty($config['actions']);
    }

    /**
     * Test that resetConfiguration() allows method chaining.
     */
    public function test_reset_configuration_allows_method_chaining(): void
    {
        // Arrange - Set configuration
        $this->table->mergeColumns('Merged', ['col1', 'col2']);
        
        // Act - Reset and configure fresh
        $result = $this->table->resetConfiguration()
            ->setFields(['id', 'name', 'email'])
            ->displayRowsLimitOnLoad(25);
        
        // Assert - Should return self for chaining
        $this->assertInstanceOf(TableBuilder::class, $result);
        $this->assertSame($this->table, $result);
        
        // Verify new configuration applied
        $config = $this->table->toArray();
        $this->assertEquals(25, $config['displayLimit']);
        $this->assertEmpty($config['mergedColumns']); // Old config cleared
    }

    /**
     * Test that resetConfiguration() resets backward compatibility properties.
     */
    public function test_reset_configuration_resets_backward_compatibility_properties(): void
    {
        // Arrange - Set properties via public properties (legacy API)
        $this->table->hidden_columns = ['password', 'secret'];
        $this->table->button_removed = ['delete'];
        $this->table->conditions = ['status' => 'active'];
        $this->table->formula = ['total' => 'price * quantity'];
        $this->table->useFieldTargetURL = 'slug';
        $this->table->search_columns = ['name', 'email'];
        
        // Act
        $this->table->resetConfiguration();
        
        // Assert - All public properties should be reset
        $this->assertEmpty($this->table->hidden_columns);
        $this->assertEmpty($this->table->button_removed);
        $this->assertEmpty($this->table->conditions);
        $this->assertEmpty($this->table->formula);
        $this->assertEquals('id', $this->table->useFieldTargetURL);
        $this->assertNull($this->table->search_columns);
    }

    /**
     * Test that resetConfiguration() can be called multiple times.
     */
    public function test_reset_configuration_can_be_called_multiple_times(): void
    {
        // Arrange & Act - Call reset multiple times
        $this->table->mergeColumns('Merged1', ['col1', 'col2']);
        $this->table->resetConfiguration();
        
        $this->table->mergeColumns('Merged2', ['col1', 'col2']);
        $this->table->resetConfiguration();
        
        $this->table->mergeColumns('Merged3', ['col1', 'col2']);
        $this->table->resetConfiguration();
        
        // Assert - Should work without errors
        $config = $this->table->toArray();
        $this->assertEmpty($config['mergedColumns']);
    }

    /**
     * Test that resetConfiguration() works with empty configuration.
     */
    public function test_reset_configuration_works_with_empty_configuration(): void
    {
        // Arrange - Fresh table with no configuration
        $freshTable = app(TableBuilder::class);
        $freshTable->setContext('admin');
        $freshTable->setData([['id' => 1, 'name' => 'Test']]);
        
        // Act - Reset empty configuration
        $freshTable->resetConfiguration();
        
        // Assert - Should work without errors
        $config = $freshTable->toArray();
        $this->assertEquals(10, $config['displayLimit']);
        $this->assertEmpty($config['mergedColumns']);
    }

    /**
     * Test configuration isolation after reset.
     */
    public function test_configuration_isolation_after_reset(): void
    {
        // Arrange - Set configuration
        $this->table->mergeColumns('Merged', ['col1', 'col2']);
        $this->table->fixedColumns(2, 1);
        $this->table->displayRowsLimitOnLoad(50);
        
        // Act - Reset and set new configuration
        $this->table->resetConfiguration();
        $this->table->displayRowsLimitOnLoad(25);
        $this->table->setHiddenColumns(['password']);
        
        // Assert - Only new configuration should be present
        $config = $this->table->toArray();
        $this->assertEquals(25, $config['displayLimit']);
        $this->assertEmpty($config['mergedColumns']); // Old config not present
        $this->assertNull($config['fixedLeft']); // Old config not present
        $this->assertEquals(['password'], $config['hiddenColumns']); // New config present
    }
}
