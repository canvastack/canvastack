<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Table\State;

use Canvastack\Canvastack\Components\Table\State\StateManager;
use Canvastack\Canvastack\Tests\TestCase;

/**
 * Test StateManager with unique ID-based state tracking.
 *
 * VALIDATES: Requirements 5.1, 5.2, 5.6
 */
class StateManagerTest extends TestCase
{
    protected StateManager $stateManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stateManager = new StateManager();
    }

    /**
     * Test basic state operations without table ID (backward compatibility).
     *
     * @return void
     */
    public function test_basic_state_operations_without_table_id(): void
    {
        // Save state
        $this->stateManager->saveState('key1', 'value1');
        $this->stateManager->saveState('key2', 'value2');

        // Get state
        $this->assertEquals('value1', $this->stateManager->getState('key1'));
        $this->assertEquals('value2', $this->stateManager->getState('key2'));

        // Has state
        $this->assertTrue($this->stateManager->hasState('key1'));
        $this->assertFalse($this->stateManager->hasState('key3'));

        // Get with default
        $this->assertEquals('default', $this->stateManager->getState('key3', 'default'));
    }

    /**
     * Test state operations with single table ID.
     *
     * VALIDATES: Requirement 5.6 - Separate state for each table instance
     *
     * @return void
     */
    public function test_state_operations_with_single_table_id(): void
    {
        $tableId = 'canvastable_abc123';

        // Set table ID
        $this->stateManager->setTableId($tableId);
        $this->assertEquals($tableId, $this->stateManager->getTableId());

        // Save state for this table
        $this->stateManager->saveState('filters', ['status' => 'active']);
        $this->stateManager->saveState('sorting', ['column' => 'name', 'direction' => 'asc']);

        // Get state
        $this->assertEquals(['status' => 'active'], $this->stateManager->getState('filters'));
        $this->assertEquals(['column' => 'name', 'direction' => 'asc'], $this->stateManager->getState('sorting'));

        // Get all state for current table
        $allState = $this->stateManager->getAllState();
        $this->assertArrayHasKey('filters', $allState);
        $this->assertArrayHasKey('sorting', $allState);
    }

    /**
     * Test state isolation between multiple table instances.
     *
     * VALIDATES: Requirement 5.1 - Support multiple instances on same page
     * VALIDATES: Requirement 5.2 - Unique IDs for each instance
     * VALIDATES: Requirement 5.6 - Separate state for each table instance
     *
     * @return void
     */
    public function test_state_isolation_between_multiple_tables(): void
    {
        $table1Id = 'canvastable_abc123';
        $table2Id = 'canvastable_def456';
        $table3Id = 'canvastable_ghi789';

        // Configure table 1
        $this->stateManager->setTableId($table1Id);
        $this->stateManager->saveState('filters', ['status' => 'active']);
        $this->stateManager->saveState('page', 1);

        // Configure table 2
        $this->stateManager->setTableId($table2Id);
        $this->stateManager->saveState('filters', ['role' => 'admin']);
        $this->stateManager->saveState('page', 2);

        // Configure table 3
        $this->stateManager->setTableId($table3Id);
        $this->stateManager->saveState('filters', ['archived' => true]);
        $this->stateManager->saveState('page', 3);

        // Verify table 1 state
        $this->stateManager->setTableId($table1Id);
        $this->assertEquals(['status' => 'active'], $this->stateManager->getState('filters'));
        $this->assertEquals(1, $this->stateManager->getState('page'));

        // Verify table 2 state
        $this->stateManager->setTableId($table2Id);
        $this->assertEquals(['role' => 'admin'], $this->stateManager->getState('filters'));
        $this->assertEquals(2, $this->stateManager->getState('page'));

        // Verify table 3 state
        $this->stateManager->setTableId($table3Id);
        $this->assertEquals(['archived' => true], $this->stateManager->getState('filters'));
        $this->assertEquals(3, $this->stateManager->getState('page'));

        // Verify states are completely isolated
        $this->stateManager->setTableId($table1Id);
        $this->assertNotEquals(['role' => 'admin'], $this->stateManager->getState('filters'));
        $this->assertNotEquals(2, $this->stateManager->getState('page'));
    }

    /**
     * Test getting state for specific table without changing current table ID.
     *
     * @return void
     */
    public function test_get_state_for_table_without_changing_current(): void
    {
        $table1Id = 'canvastable_abc123';
        $table2Id = 'canvastable_def456';

        // Setup table 1
        $this->stateManager->setTableId($table1Id);
        $this->stateManager->saveState('key1', 'value1');

        // Setup table 2
        $this->stateManager->setTableId($table2Id);
        $this->stateManager->saveState('key2', 'value2');

        // Current table is table2
        $this->assertEquals($table2Id, $this->stateManager->getTableId());

        // Get state for table1 without changing current
        $table1State = $this->stateManager->getStateForTable($table1Id);
        $this->assertEquals('value1', $table1State['key1']);

        // Verify current table ID hasn't changed
        $this->assertEquals($table2Id, $this->stateManager->getTableId());
    }

    /**
     * Test getting all table states.
     *
     * VALIDATES: Requirement 5.1 - Support multiple instances on same page
     *
     * @return void
     */
    public function test_get_all_table_states(): void
    {
        $table1Id = 'canvastable_abc123';
        $table2Id = 'canvastable_def456';

        // Setup table 1
        $this->stateManager->setTableId($table1Id);
        $this->stateManager->saveState('filters', ['status' => 'active']);

        // Setup table 2
        $this->stateManager->setTableId($table2Id);
        $this->stateManager->saveState('filters', ['role' => 'admin']);

        // Get all states
        $allStates = $this->stateManager->getAllTableStates();

        $this->assertArrayHasKey($table1Id, $allStates);
        $this->assertArrayHasKey($table2Id, $allStates);
        $this->assertEquals(['status' => 'active'], $allStates[$table1Id]['filters']);
        $this->assertEquals(['role' => 'admin'], $allStates[$table2Id]['filters']);
    }

    /**
     * Test state history tracking with table IDs.
     *
     * @return void
     */
    public function test_state_history_with_table_ids(): void
    {
        $table1Id = 'canvastable_abc123';
        $table2Id = 'canvastable_def456';

        // Make changes to table 1
        $this->stateManager->setTableId($table1Id);
        $this->stateManager->saveState('key1', 'value1');
        $this->stateManager->saveState('key1', 'value1_updated');

        // Make changes to table 2
        $this->stateManager->setTableId($table2Id);
        $this->stateManager->saveState('key2', 'value2');

        // Get complete history
        $history = $this->stateManager->getStateHistory();
        $this->assertCount(3, $history);

        // Verify history entries have table_id
        $this->assertEquals($table1Id, $history[0]['table_id']);
        $this->assertEquals($table1Id, $history[1]['table_id']);
        $this->assertEquals($table2Id, $history[2]['table_id']);

        // Get filtered history for table 1
        $table1History = $this->stateManager->getStateHistory($table1Id);
        $this->assertCount(2, $table1History);

        // Get filtered history for table 2
        $table2History = $this->stateManager->getStateHistory($table2Id);
        $this->assertCount(1, $table2History);
    }

    /**
     * Test clearing state for specific table.
     *
     * @return void
     */
    public function test_clear_state_for_specific_table(): void
    {
        $table1Id = 'canvastable_abc123';
        $table2Id = 'canvastable_def456';

        // Setup both tables
        $this->stateManager->setTableId($table1Id);
        $this->stateManager->saveState('key1', 'value1');

        $this->stateManager->setTableId($table2Id);
        $this->stateManager->saveState('key2', 'value2');

        // Clear table 1
        $this->stateManager->clearTableState($table1Id);

        // Verify table 1 is cleared
        $this->assertEmpty($this->stateManager->getStateForTable($table1Id));

        // Verify table 2 is not affected
        $table2State = $this->stateManager->getStateForTable($table2Id);
        $this->assertEquals('value2', $table2State['key2']);
    }

    /**
     * Test clearing current table state.
     *
     * @return void
     */
    public function test_clear_current_table_state(): void
    {
        $table1Id = 'canvastable_abc123';
        $table2Id = 'canvastable_def456';

        // Setup both tables
        $this->stateManager->setTableId($table1Id);
        $this->stateManager->saveState('key1', 'value1');

        $this->stateManager->setTableId($table2Id);
        $this->stateManager->saveState('key2', 'value2');

        // Clear current table (table2)
        $this->stateManager->clearAll();

        // Verify table 2 is cleared
        $this->assertEmpty($this->stateManager->getAllState());

        // Verify table 1 is not affected
        $table1State = $this->stateManager->getStateForTable($table1Id);
        $this->assertEquals('value1', $table1State['key1']);
    }

    /**
     * Test clearing specific variable for current table.
     *
     * @return void
     */
    public function test_clear_specific_variable(): void
    {
        $tableId = 'canvastable_abc123';

        $this->stateManager->setTableId($tableId);
        $this->stateManager->saveState('filters', ['status' => 'active']);
        $this->stateManager->saveState('sorting', ['column' => 'name']);

        // Clear only filters
        $this->stateManager->clearVar('filters');

        // Verify filters is cleared
        $this->assertNull($this->stateManager->getState('filters'));

        // Verify sorting is not affected
        $this->assertEquals(['column' => 'name'], $this->stateManager->getState('sorting'));
    }

    /**
     * Test clearing clearable variables.
     *
     * @return void
     */
    public function test_clear_clearable_variables(): void
    {
        $tableId = 'canvastable_abc123';

        $this->stateManager->setTableId($tableId);
        $this->stateManager->saveState('filters', ['status' => 'active']);
        $this->stateManager->saveState('hidden_columns', ['id']);
        $this->stateManager->saveState('custom_key', 'custom_value');

        // Clear clearable vars
        $this->stateManager->clearClearableVars();

        // Verify clearable vars are cleared
        $this->assertNull($this->stateManager->getState('filters'));
        $this->assertNull($this->stateManager->getState('hidden_columns'));

        // Verify non-clearable var is not affected
        $this->assertEquals('custom_value', $this->stateManager->getState('custom_key'));
    }

    /**
     * Test checking if table has state.
     *
     * @return void
     */
    public function test_has_table_state(): void
    {
        $table1Id = 'canvastable_abc123';
        $table2Id = 'canvastable_def456';

        // Table 1 has state
        $this->stateManager->setTableId($table1Id);
        $this->stateManager->saveState('key1', 'value1');

        // Table 2 has no state
        $this->assertTrue($this->stateManager->hasTableState($table1Id));
        $this->assertFalse($this->stateManager->hasTableState($table2Id));
    }

    /**
     * Test clearing and resetting table ID.
     *
     * @return void
     */
    public function test_clear_table_id(): void
    {
        $tableId = 'canvastable_abc123';

        $this->stateManager->setTableId($tableId);
        $this->assertEquals($tableId, $this->stateManager->getTableId());

        $this->stateManager->clearTableId();
        $this->assertNull($this->stateManager->getTableId());
    }

    /**
     * Test backward compatibility with global state.
     *
     * When no table ID is set, state should work as before.
     *
     * @return void
     */
    public function test_backward_compatibility_global_state(): void
    {
        // Don't set table ID - use global state
        $this->stateManager->saveState('key1', 'value1');
        $this->stateManager->saveState('key2', 'value2');

        // Get state
        $this->assertEquals('value1', $this->stateManager->getState('key1'));
        $this->assertEquals('value2', $this->stateManager->getState('key2'));

        // Clear all
        $this->stateManager->clearAll();
        $this->assertNull($this->stateManager->getState('key1'));
        $this->assertNull($this->stateManager->getState('key2'));
    }

    /**
     * Test state isolation between global and table-specific state.
     *
     * @return void
     */
    public function test_state_isolation_global_vs_table_specific(): void
    {
        $tableId = 'canvastable_abc123';

        // Set global state
        $this->stateManager->saveState('key1', 'global_value');

        // Set table-specific state
        $this->stateManager->setTableId($tableId);
        $this->stateManager->saveState('key1', 'table_value');

        // Verify table state
        $this->assertEquals('table_value', $this->stateManager->getState('key1'));

        // Switch to global state
        $this->stateManager->clearTableId();
        $this->assertEquals('global_value', $this->stateManager->getState('key1'));
    }

    /**
     * Test managing clearable variables list.
     *
     * @return void
     */
    public function test_manage_clearable_variables(): void
    {
        $clearableVars = $this->stateManager->getClearableVars();
        $this->assertContains('filters', $clearableVars);

        // Add new clearable var
        $this->stateManager->addClearableVar('custom_var');
        $clearableVars = $this->stateManager->getClearableVars();
        $this->assertContains('custom_var', $clearableVars);

        // Remove clearable var
        $this->stateManager->removeClearableVar('custom_var');
        $clearableVars = $this->stateManager->getClearableVars();
        $this->assertNotContains('custom_var', $clearableVars);
    }

    /**
     * Test state history clearing.
     *
     * @return void
     */
    public function test_clear_state_history(): void
    {
        $tableId = 'canvastable_abc123';

        $this->stateManager->setTableId($tableId);
        $this->stateManager->saveState('key1', 'value1');
        $this->stateManager->saveState('key2', 'value2');

        // Verify history exists
        $history = $this->stateManager->getStateHistory();
        $this->assertNotEmpty($history);

        // Clear history
        $this->stateManager->clearHistory();
        $history = $this->stateManager->getStateHistory();
        $this->assertEmpty($history);
    }

    /**
     * Test complex multi-table scenario with filters, sorting, and pagination.
     *
     * VALIDATES: Requirement 5.6 - Separate state for each table instance
     *
     * @return void
     */
    public function test_complex_multi_table_scenario(): void
    {
        $usersTableId = 'canvastable_users_abc';
        $postsTableId = 'canvastable_posts_def';
        $commentsTableId = 'canvastable_comments_ghi';

        // Configure users table
        $this->stateManager->setTableId($usersTableId);
        $this->stateManager->saveState('filters', ['status' => 'active', 'role' => 'admin']);
        $this->stateManager->saveState('sorting', ['column' => 'name', 'direction' => 'asc']);
        $this->stateManager->saveState('pagination', ['page' => 1, 'pageSize' => 10]);
        $this->stateManager->saveState('selection', [1, 2, 3]);

        // Configure posts table
        $this->stateManager->setTableId($postsTableId);
        $this->stateManager->saveState('filters', ['published' => true]);
        $this->stateManager->saveState('sorting', ['column' => 'created_at', 'direction' => 'desc']);
        $this->stateManager->saveState('pagination', ['page' => 2, 'pageSize' => 20]);
        $this->stateManager->saveState('selection', [10, 20]);

        // Configure comments table
        $this->stateManager->setTableId($commentsTableId);
        $this->stateManager->saveState('filters', ['approved' => false]);
        $this->stateManager->saveState('sorting', ['column' => 'created_at', 'direction' => 'asc']);
        $this->stateManager->saveState('pagination', ['page' => 1, 'pageSize' => 50]);
        $this->stateManager->saveState('selection', []);

        // Verify users table state
        $this->stateManager->setTableId($usersTableId);
        $this->assertEquals(['status' => 'active', 'role' => 'admin'], $this->stateManager->getState('filters'));
        $this->assertEquals(['column' => 'name', 'direction' => 'asc'], $this->stateManager->getState('sorting'));
        $this->assertEquals(['page' => 1, 'pageSize' => 10], $this->stateManager->getState('pagination'));
        $this->assertEquals([1, 2, 3], $this->stateManager->getState('selection'));

        // Verify posts table state
        $this->stateManager->setTableId($postsTableId);
        $this->assertEquals(['published' => true], $this->stateManager->getState('filters'));
        $this->assertEquals(['column' => 'created_at', 'direction' => 'desc'], $this->stateManager->getState('sorting'));
        $this->assertEquals(['page' => 2, 'pageSize' => 20], $this->stateManager->getState('pagination'));
        $this->assertEquals([10, 20], $this->stateManager->getState('selection'));

        // Verify comments table state
        $this->stateManager->setTableId($commentsTableId);
        $this->assertEquals(['approved' => false], $this->stateManager->getState('filters'));
        $this->assertEquals(['column' => 'created_at', 'direction' => 'asc'], $this->stateManager->getState('sorting'));
        $this->assertEquals(['page' => 1, 'pageSize' => 50], $this->stateManager->getState('pagination'));
        $this->assertEquals([], $this->stateManager->getState('selection'));

        // Verify complete isolation
        $allStates = $this->stateManager->getAllTableStates();
        $this->assertCount(3, $allStates);
        $this->assertArrayHasKey($usersTableId, $allStates);
        $this->assertArrayHasKey($postsTableId, $allStates);
        $this->assertArrayHasKey($commentsTableId, $allStates);
    }

    /**
     * Test setting and getting active tab.
     *
     * VALIDATES: Requirement 6.5 - Tab content caching
     * VALIDATES: Requirement 6.6 - Display cached content
     *
     * @return void
     */
    public function test_set_and_get_active_tab(): void
    {
        $tableId = 'canvastable_abc123';
        $this->stateManager->setTableId($tableId);

        // Default active tab should be 0
        $this->assertEquals(0, $this->stateManager->getActiveTab());

        // Set active tab to 1
        $this->stateManager->setActiveTab(1);
        $this->assertEquals(1, $this->stateManager->getActiveTab());

        // Set active tab to 2
        $this->stateManager->setActiveTab(2);
        $this->assertEquals(2, $this->stateManager->getActiveTab());
    }

    /**
     * Test adding and checking loaded tabs.
     *
     * VALIDATES: Requirement 6.5 - Tab content caching
     *
     * @return void
     */
    public function test_add_and_check_loaded_tabs(): void
    {
        $tableId = 'canvastable_abc123';
        $this->stateManager->setTableId($tableId);

        // Initially no tabs loaded
        $this->assertEquals([], $this->stateManager->getLoadedTabs());
        $this->assertFalse($this->stateManager->isTabLoaded(0));
        $this->assertFalse($this->stateManager->isTabLoaded(1));

        // Add tab 0 as loaded
        $this->stateManager->addLoadedTab(0);
        $this->assertTrue($this->stateManager->isTabLoaded(0));
        $this->assertFalse($this->stateManager->isTabLoaded(1));
        $this->assertEquals([0], $this->stateManager->getLoadedTabs());

        // Add tab 1 as loaded
        $this->stateManager->addLoadedTab(1);
        $this->assertTrue($this->stateManager->isTabLoaded(0));
        $this->assertTrue($this->stateManager->isTabLoaded(1));
        $this->assertEquals([0, 1], $this->stateManager->getLoadedTabs());

        // Add tab 2 as loaded
        $this->stateManager->addLoadedTab(2);
        $this->assertEquals([0, 1, 2], $this->stateManager->getLoadedTabs());
    }

    /**
     * Test that adding same tab multiple times doesn't duplicate.
     *
     * @return void
     */
    public function test_add_loaded_tab_no_duplicates(): void
    {
        $tableId = 'canvastable_abc123';
        $this->stateManager->setTableId($tableId);

        // Add tab 0 multiple times
        $this->stateManager->addLoadedTab(0);
        $this->stateManager->addLoadedTab(0);
        $this->stateManager->addLoadedTab(0);

        // Should only appear once
        $this->assertEquals([0], $this->stateManager->getLoadedTabs());
    }

    /**
     * Test setting and getting tab content.
     *
     * VALIDATES: Requirement 6.5 - Tab content caching
     * VALIDATES: Requirement 6.6 - Display cached content
     *
     * @return void
     */
    public function test_set_and_get_tab_content(): void
    {
        $tableId = 'canvastable_abc123';
        $this->stateManager->setTableId($tableId);

        // Initially no content cached
        $this->assertNull($this->stateManager->getTabContent(0));
        $this->assertFalse($this->stateManager->hasTabContent(0));

        // Cache content for tab 0
        $content0 = '<div>Tab 0 content</div>';
        $this->stateManager->setTabContent(0, $content0);
        $this->assertEquals($content0, $this->stateManager->getTabContent(0));
        $this->assertTrue($this->stateManager->hasTabContent(0));

        // Cache content for tab 1
        $content1 = '<div>Tab 1 content</div>';
        $this->stateManager->setTabContent(1, $content1);
        $this->assertEquals($content1, $this->stateManager->getTabContent(1));
        $this->assertTrue($this->stateManager->hasTabContent(1));

        // Verify tab 0 content is still there
        $this->assertEquals($content0, $this->stateManager->getTabContent(0));
    }

    /**
     * Test updating tab content.
     *
     * @return void
     */
    public function test_update_tab_content(): void
    {
        $tableId = 'canvastable_abc123';
        $this->stateManager->setTableId($tableId);

        // Set initial content
        $this->stateManager->setTabContent(0, '<div>Initial content</div>');
        $this->assertEquals('<div>Initial content</div>', $this->stateManager->getTabContent(0));

        // Update content
        $this->stateManager->setTabContent(0, '<div>Updated content</div>');
        $this->assertEquals('<div>Updated content</div>', $this->stateManager->getTabContent(0));
    }

    /**
     * Test clearing tab state.
     *
     * @return void
     */
    public function test_clear_tab_state(): void
    {
        $tableId = 'canvastable_abc123';
        $this->stateManager->setTableId($tableId);

        // Set up tab state
        $this->stateManager->setActiveTab(2);
        $this->stateManager->addLoadedTab(0);
        $this->stateManager->addLoadedTab(1);
        $this->stateManager->addLoadedTab(2);
        $this->stateManager->setTabContent(0, '<div>Tab 0</div>');
        $this->stateManager->setTabContent(1, '<div>Tab 1</div>');

        // Verify state is set
        $this->assertEquals(2, $this->stateManager->getActiveTab());
        $this->assertEquals([0, 1, 2], $this->stateManager->getLoadedTabs());
        $this->assertTrue($this->stateManager->hasTabContent(0));
        $this->assertTrue($this->stateManager->hasTabContent(1));

        // Clear tab state
        $this->stateManager->clearTabState();

        // Verify all tab state is cleared
        $this->assertEquals(0, $this->stateManager->getActiveTab()); // Default
        $this->assertEquals([], $this->stateManager->getLoadedTabs());
        $this->assertFalse($this->stateManager->hasTabContent(0));
        $this->assertFalse($this->stateManager->hasTabContent(1));
    }

    /**
     * Test tab state isolation between multiple tables.
     *
     * VALIDATES: Requirement 5.1 - Support multiple instances on same page
     * VALIDATES: Requirement 5.6 - Separate state for each table instance
     *
     * @return void
     */
    public function test_tab_state_isolation_between_tables(): void
    {
        $table1Id = 'canvastable_abc123';
        $table2Id = 'canvastable_def456';

        // Configure table 1 tabs
        $this->stateManager->setTableId($table1Id);
        $this->stateManager->setActiveTab(1);
        $this->stateManager->addLoadedTab(0);
        $this->stateManager->addLoadedTab(1);
        $this->stateManager->setTabContent(0, '<div>Table 1 Tab 0</div>');
        $this->stateManager->setTabContent(1, '<div>Table 1 Tab 1</div>');

        // Configure table 2 tabs
        $this->stateManager->setTableId($table2Id);
        $this->stateManager->setActiveTab(2);
        $this->stateManager->addLoadedTab(0);
        $this->stateManager->addLoadedTab(2);
        $this->stateManager->setTabContent(0, '<div>Table 2 Tab 0</div>');
        $this->stateManager->setTabContent(2, '<div>Table 2 Tab 2</div>');

        // Verify table 1 state
        $this->stateManager->setTableId($table1Id);
        $this->assertEquals(1, $this->stateManager->getActiveTab());
        $this->assertEquals([0, 1], $this->stateManager->getLoadedTabs());
        $this->assertEquals('<div>Table 1 Tab 0</div>', $this->stateManager->getTabContent(0));
        $this->assertEquals('<div>Table 1 Tab 1</div>', $this->stateManager->getTabContent(1));
        $this->assertNull($this->stateManager->getTabContent(2));

        // Verify table 2 state
        $this->stateManager->setTableId($table2Id);
        $this->assertEquals(2, $this->stateManager->getActiveTab());
        $this->assertEquals([0, 2], $this->stateManager->getLoadedTabs());
        $this->assertEquals('<div>Table 2 Tab 0</div>', $this->stateManager->getTabContent(0));
        $this->assertNull($this->stateManager->getTabContent(1));
        $this->assertEquals('<div>Table 2 Tab 2</div>', $this->stateManager->getTabContent(2));
    }

    /**
     * Test tab state with complex content.
     *
     * @return void
     */
    public function test_tab_state_with_complex_content(): void
    {
        $tableId = 'canvastable_abc123';
        $this->stateManager->setTableId($tableId);

        // Cache complex HTML content
        $complexContent = <<<HTML
<div class="tab-content">
    <div id="canvastable_xyz123">
        <table class="table">
            <thead>
                <tr><th>Name</th><th>Email</th></tr>
            </thead>
            <tbody>
                <tr><td>John</td><td>john@example.com</td></tr>
            </tbody>
        </table>
    </div>
    <script>
        initTanStack('canvastable_xyz123', {
            columns: ['name', 'email'],
            data: [{name: 'John', email: 'john@example.com'}]
        });
    </script>
</div>
HTML;

        $this->stateManager->setTabContent(0, $complexContent);
        $this->assertEquals($complexContent, $this->stateManager->getTabContent(0));
    }

    /**
     * Test tab state persistence across operations.
     *
     * @return void
     */
    public function test_tab_state_persistence(): void
    {
        $tableId = 'canvastable_abc123';
        $this->stateManager->setTableId($tableId);

        // Set up tab state
        $this->stateManager->setActiveTab(1);
        $this->stateManager->addLoadedTab(0);
        $this->stateManager->addLoadedTab(1);
        $this->stateManager->setTabContent(0, '<div>Tab 0</div>');

        // Perform other state operations
        $this->stateManager->saveState('filters', ['status' => 'active']);
        $this->stateManager->saveState('sorting', ['column' => 'name']);

        // Verify tab state is still intact
        $this->assertEquals(1, $this->stateManager->getActiveTab());
        $this->assertEquals([0, 1], $this->stateManager->getLoadedTabs());
        $this->assertEquals('<div>Tab 0</div>', $this->stateManager->getTabContent(0));

        // Verify other state is also intact
        $this->assertEquals(['status' => 'active'], $this->stateManager->getState('filters'));
        $this->assertEquals(['column' => 'name'], $this->stateManager->getState('sorting'));
    }

    /**
     * Test clearing specific tab state doesn't affect other state.
     *
     * @return void
     */
    public function test_clear_tab_state_preserves_other_state(): void
    {
        $tableId = 'canvastable_abc123';
        $this->stateManager->setTableId($tableId);

        // Set up mixed state
        $this->stateManager->setActiveTab(1);
        $this->stateManager->addLoadedTab(0);
        $this->stateManager->setTabContent(0, '<div>Tab 0</div>');
        $this->stateManager->saveState('filters', ['status' => 'active']);
        $this->stateManager->saveState('sorting', ['column' => 'name']);

        // Clear only tab state
        $this->stateManager->clearTabState();

        // Verify tab state is cleared
        $this->assertEquals(0, $this->stateManager->getActiveTab());
        $this->assertEquals([], $this->stateManager->getLoadedTabs());
        $this->assertNull($this->stateManager->getTabContent(0));

        // Verify other state is preserved
        $this->assertEquals(['status' => 'active'], $this->stateManager->getState('filters'));
        $this->assertEquals(['column' => 'name'], $this->stateManager->getState('sorting'));
    }

    /**
     * Test tab state with multiple tabs loaded in sequence.
     *
     * @return void
     */
    public function test_sequential_tab_loading(): void
    {
        $tableId = 'canvastable_abc123';
        $this->stateManager->setTableId($tableId);

        // Simulate sequential tab loading
        // User starts on tab 0
        $this->stateManager->setActiveTab(0);
        $this->stateManager->addLoadedTab(0);
        $this->stateManager->setTabContent(0, '<div>Tab 0 content</div>');

        // User switches to tab 1
        $this->stateManager->setActiveTab(1);
        $this->stateManager->addLoadedTab(1);
        $this->stateManager->setTabContent(1, '<div>Tab 1 content</div>');

        // User switches to tab 2
        $this->stateManager->setActiveTab(2);
        $this->stateManager->addLoadedTab(2);
        $this->stateManager->setTabContent(2, '<div>Tab 2 content</div>');

        // User switches back to tab 0 (should use cached content)
        $this->stateManager->setActiveTab(0);

        // Verify all tabs are loaded
        $this->assertEquals([0, 1, 2], $this->stateManager->getLoadedTabs());

        // Verify all content is cached
        $this->assertEquals('<div>Tab 0 content</div>', $this->stateManager->getTabContent(0));
        $this->assertEquals('<div>Tab 1 content</div>', $this->stateManager->getTabContent(1));
        $this->assertEquals('<div>Tab 2 content</div>', $this->stateManager->getTabContent(2));

        // Verify active tab is 0
        $this->assertEquals(0, $this->stateManager->getActiveTab());
    }
}
