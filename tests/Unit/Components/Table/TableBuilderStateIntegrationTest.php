<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Table;

use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Tests\TestCase;

/**
 * Test TableBuilder integration with StateManager (Task 1.2.2).
 *
 * Verifies that:
 * - StateManager is properly initialized
 * - clearVar() integrates with StateManager
 * - clearOnLoad() integrates with StateManager
 * - clearFixedColumns() integrates with StateManager
 * - captureCurrentConfig() saves to StateManager
 */
class TableBuilderStateIntegrationTest extends TestCase
{
    protected TableBuilder $table;

    protected function setUp(): void
    {
        parent::setUp();
        $this->table = app(TableBuilder::class);
        $this->table->setContext('admin');
    }

    /**
     * Test that StateManager is initialized in TableBuilder.
     */
    public function test_state_manager_is_initialized(): void
    {
        $stateManager = $this->table->getStateManager();

        $this->assertNotNull($stateManager);
        $this->assertInstanceOf(\Canvastack\Canvastack\Components\Table\State\StateManager::class, $stateManager);
    }

    /**
     * Test that clearVar() clears from StateManager.
     */
    public function test_clear_var_clears_from_state_manager(): void
    {
        $stateManager = $this->table->getStateManager();

        // Save some state
        $stateManager->saveState('merged_columns', ['col1', 'col2']);
        $this->assertTrue($stateManager->hasState('merged_columns'));

        // Clear via TableBuilder
        $this->table->clearVar('merged_columns');

        // Verify cleared from StateManager
        $this->assertFalse($stateManager->hasState('merged_columns'));
    }

    /**
     * Test that clearVar() clears fixed_columns from StateManager.
     */
    public function test_clear_var_fixed_columns_clears_from_state_manager(): void
    {
        $stateManager = $this->table->getStateManager();

        // Save some state
        $stateManager->saveState('fixed_columns', ['left' => 2, 'right' => 1]);
        $this->assertTrue($stateManager->hasState('fixed_columns'));

        // Clear via TableBuilder
        $this->table->clearVar('fixed_columns');

        // Verify cleared from StateManager
        $this->assertFalse($stateManager->hasState('fixed_columns'));
    }

    /**
     * Test that clearOnLoad() clears clearable vars from StateManager.
     */
    public function test_clear_on_load_clears_clearable_vars(): void
    {
        $stateManager = $this->table->getStateManager();

        // Save some clearable state
        $stateManager->saveState('merged_columns', ['col1', 'col2']);
        $stateManager->saveState('fixed_columns', ['left' => 2]);
        $stateManager->saveState('formats', ['col1' => 2]);

        // Clear via clearOnLoad
        $this->table->clearOnLoad();

        // Verify all clearable vars are cleared
        $this->assertFalse($stateManager->hasState('merged_columns'));
        $this->assertFalse($stateManager->hasState('fixed_columns'));
        $this->assertFalse($stateManager->hasState('formats'));
    }

    /**
     * Test that clearOnLoad() resets properties.
     */
    public function test_clear_on_load_resets_properties(): void
    {
        // Set some properties (without column validation)
        $this->table->fixedColumns(2, 1);
        $this->table->displayRowsLimitOnLoad(25);

        // Clear
        $this->table->clearOnLoad();

        // Verify properties are reset
        $this->assertEquals(10, $this->table->toArray()['displayLimit']);
        $this->assertEmpty($this->table->toArray()['mergedColumns']);
        $this->assertNull($this->table->toArray()['fixedLeft']);
        $this->assertNull($this->table->toArray()['fixedRight']);
    }

    /**
     * Test that clearFixedColumns() clears from StateManager.
     */
    public function test_clear_fixed_columns_clears_from_state_manager(): void
    {
        $stateManager = $this->table->getStateManager();

        // Set fixed columns
        $this->table->fixedColumns(2, 1);

        // Save to state
        $stateManager->saveState('fixed_columns', ['left' => 2, 'right' => 1]);
        $this->assertTrue($stateManager->hasState('fixed_columns'));

        // Clear
        $this->table->clearFixedColumns();

        // Verify cleared from StateManager
        $this->assertFalse($stateManager->hasState('fixed_columns'));
    }

    /**
     * Test that clearFixedColumns() resets properties.
     */
    public function test_clear_fixed_columns_resets_properties(): void
    {
        // Set fixed columns
        $this->table->fixedColumns(2, 1);

        // Clear
        $this->table->clearFixedColumns();

        // Verify properties are reset
        $config = $this->table->toArray();
        $this->assertNull($config['fixedLeft']);
        $this->assertNull($config['fixedRight']);
    }

    /**
     * Test that captureCurrentConfig() saves to StateManager.
     */
    public function test_capture_current_config_saves_to_state_manager(): void
    {
        $stateManager = $this->table->getStateManager();

        // Set some configuration (without column validation)
        $this->table->fixedColumns(2, 1);
        $this->table->displayRowsLimitOnLoad(25);

        // Open tab to trigger config capture (use existing table)
        $this->table->openTab('Test Tab');
        $this->table->lists('users', ['id', 'name'], false);

        // Verify config was saved to StateManager
        $this->assertTrue($stateManager->hasState('captured_config'));

        $capturedConfig = $stateManager->getState('captured_config');
        $this->assertIsArray($capturedConfig);
        $this->assertEquals(2, $capturedConfig['fixedColumns']['left']);
        $this->assertEquals(1, $capturedConfig['fixedColumns']['right']);
        $this->assertEquals(25, $capturedConfig['displayLimit']);
    }

    /**
     * Test that state history is tracked.
     */
    public function test_state_history_is_tracked(): void
    {
        $stateManager = $this->table->getStateManager();

        // Perform some operations that save state
        $this->table->fixedColumns(2, 1);
        
        // Manually save state to trigger history
        $stateManager->saveState('test_key', 'test_value');
        
        $this->table->clearFixedColumns();

        // Check history
        $history = $stateManager->getStateHistory();
        $this->assertNotEmpty($history);
        $this->assertGreaterThan(0, count($history));
    }

    /**
     * Test configuration isolation between operations.
     */
    public function test_configuration_isolation(): void
    {
        $stateManager = $this->table->getStateManager();

        // Set config for first operation
        $this->table->fixedColumns(2, 1);

        // Capture config (use existing table)
        $this->table->openTab('Tab 1');
        $this->table->lists('users', ['id', 'name'], false);

        $config1 = $stateManager->getState('captured_config');

        // Clear and set different config
        $this->table->clearOnLoad();
        $this->table->fixedColumns(3, 0);

        // Capture new config (use existing table)
        $this->table->openTab('Tab 2');
        $this->table->lists('permissions', ['id', 'name'], false);

        $config2 = $stateManager->getState('captured_config');

        // Verify configs are different
        $this->assertNotEquals($config1['fixedColumns'], $config2['fixedColumns']);
        $this->assertEquals(2, $config1['fixedColumns']['left']);
        $this->assertEquals(3, $config2['fixedColumns']['left']);
    }
}
