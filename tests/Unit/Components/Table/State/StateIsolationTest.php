<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Table\State;

use Canvastack\Canvastack\Components\Table\State\StateManager;
use Canvastack\Canvastack\Tests\TestCase;

/**
 * Test StateManager state isolation to prevent state leakage between instances.
 *
 * This test suite validates that each table instance maintains completely
 * isolated state without any cross-contamination or leakage.
 *
 * VALIDATES: Requirement 5.6 - Separate state for each table instance
 *
 * @version 1.0.0
 */
class StateIsolationTest extends TestCase
{
    protected StateManager $stateManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stateManager = new StateManager();
    }

    /**
     * Test that state changes in one instance don't affect other instances.
     *
     * VALIDATES: Requirement 5.6 - Separate state for each table instance
     *
     * @return void
     */
    public function test_state_changes_do_not_leak_between_instances(): void
    {
        $table1Id = 'canvastable_abc123';
        $table2Id = 'canvastable_def456';

        // Set initial state for table 1
        $this->stateManager->setTableId($table1Id);
        $this->stateManager->saveState('filters', ['status' => 'active']);
        $this->stateManager->saveState('page', 1);

        // Set initial state for table 2
        $this->stateManager->setTableId($table2Id);
        $this->stateManager->saveState('filters', ['role' => 'admin']);
        $this->stateManager->saveState('page', 2);

        // Modify table 1 state
        $this->stateManager->setTableId($table1Id);
        $this->stateManager->saveState('filters', ['status' => 'inactive']);
        $this->stateManager->saveState('page', 5);

        // Verify table 2 state is unchanged
        $this->stateManager->setTableId($table2Id);
        $this->assertEquals(['role' => 'admin'], $this->stateManager->getState('filters'));
        $this->assertEquals(2, $this->stateManager->getState('page'));

        // Verify table 1 has new state
        $this->stateManager->setTableId($table1Id);
        $this->assertEquals(['status' => 'inactive'], $this->stateManager->getState('filters'));
        $this->assertEquals(5, $this->stateManager->getState('page'));
    }

    /**
     * Test that clearing state in one instance doesn't affect others.
     *
     * @return void
     */
    public function test_clearing_state_does_not_affect_other_instances(): void
    {
        $table1Id = 'canvastable_abc123';
        $table2Id = 'canvastable_def456';
        $table3Id = 'canvastable_ghi789';

        // Setup all tables
        $this->stateManager->setTableId($table1Id);
        $this->stateManager->saveState('key1', 'value1');

        $this->stateManager->setTableId($table2Id);
        $this->stateManager->saveState('key2', 'value2');

        $this->stateManager->setTableId($table3Id);
        $this->stateManager->saveState('key3', 'value3');

        // Clear table 2
        $this->stateManager->setTableId($table2Id);
        $this->stateManager->clearAll();

        // Verify table 2 is cleared
        $this->assertNull($this->stateManager->getState('key2'));

        // Verify table 1 is not affected
        $this->stateManager->setTableId($table1Id);
        $this->assertEquals('value1', $this->stateManager->getState('key1'));

        // Verify table 3 is not affected
        $this->stateManager->setTableId($table3Id);
        $this->assertEquals('value3', $this->stateManager->getState('key3'));
    }

    /**
     * Test that clearing specific variable doesn't affect other instances.
     *
     * @return void
     */
    public function test_clearing_variable_does_not_affect_other_instances(): void
    {
        $table1Id = 'canvastable_abc123';
        $table2Id = 'canvastable_def456';

        // Setup both tables with same keys
        $this->stateManager->setTableId($table1Id);
        $this->stateManager->saveState('filters', ['status' => 'active']);
        $this->stateManager->saveState('sorting', ['column' => 'name']);

        $this->stateManager->setTableId($table2Id);
        $this->stateManager->saveState('filters', ['role' => 'admin']);
        $this->stateManager->saveState('sorting', ['column' => 'email']);

        // Clear filters in table 1
        $this->stateManager->setTableId($table1Id);
        $this->stateManager->clearVar('filters');

        // Verify table 1 filters cleared but sorting remains
        $this->assertNull($this->stateManager->getState('filters'));
        $this->assertEquals(['column' => 'name'], $this->stateManager->getState('sorting'));

        // Verify table 2 is completely unaffected
        $this->stateManager->setTableId($table2Id);
        $this->assertEquals(['role' => 'admin'], $this->stateManager->getState('filters'));
        $this->assertEquals(['column' => 'email'], $this->stateManager->getState('sorting'));
    }

    /**
     * Test that clearing clearable vars doesn't affect other instances.
     *
     * @return void
     */
    public function test_clearing_clearable_vars_does_not_affect_other_instances(): void
    {
        $table1Id = 'canvastable_abc123';
        $table2Id = 'canvastable_def456';

        // Setup both tables with clearable and non-clearable vars
        $this->stateManager->setTableId($table1Id);
        $this->stateManager->saveState('filters', ['status' => 'active']);
        $this->stateManager->saveState('hidden_columns', ['id']);
        $this->stateManager->saveState('custom_key', 'custom1');

        $this->stateManager->setTableId($table2Id);
        $this->stateManager->saveState('filters', ['role' => 'admin']);
        $this->stateManager->saveState('hidden_columns', ['password']);
        $this->stateManager->saveState('custom_key', 'custom2');

        // Clear clearable vars in table 1
        $this->stateManager->setTableId($table1Id);
        $this->stateManager->clearClearableVars();

        // Verify table 1 clearable vars cleared
        $this->assertNull($this->stateManager->getState('filters'));
        $this->assertNull($this->stateManager->getState('hidden_columns'));
        $this->assertEquals('custom1', $this->stateManager->getState('custom_key'));

        // Verify table 2 is completely unaffected
        $this->stateManager->setTableId($table2Id);
        $this->assertEquals(['role' => 'admin'], $this->stateManager->getState('filters'));
        $this->assertEquals(['password'], $this->stateManager->getState('hidden_columns'));
        $this->assertEquals('custom2', $this->stateManager->getState('custom_key'));
    }

    /**
     * Test state isolation with identical keys across instances.
     *
     * @return void
     */
    public function test_state_isolation_with_identical_keys(): void
    {
        $table1Id = 'canvastable_abc123';
        $table2Id = 'canvastable_def456';
        $table3Id = 'canvastable_ghi789';

        // Use same keys but different values
        $this->stateManager->setTableId($table1Id);
        $this->stateManager->saveState('key', 'value1');

        $this->stateManager->setTableId($table2Id);
        $this->stateManager->saveState('key', 'value2');

        $this->stateManager->setTableId($table3Id);
        $this->stateManager->saveState('key', 'value3');

        // Verify each table has its own value
        $this->stateManager->setTableId($table1Id);
        $this->assertEquals('value1', $this->stateManager->getState('key'));

        $this->stateManager->setTableId($table2Id);
        $this->assertEquals('value2', $this->stateManager->getState('key'));

        $this->stateManager->setTableId($table3Id);
        $this->assertEquals('value3', $this->stateManager->getState('key'));
    }

    /**
     * Test state isolation with complex nested data structures.
     *
     * @return void
     */
    public function test_state_isolation_with_complex_data(): void
    {
        $table1Id = 'canvastable_abc123';
        $table2Id = 'canvastable_def456';

        // Setup complex nested data for table 1
        $this->stateManager->setTableId($table1Id);
        $this->stateManager->saveState('filters', [
            'status' => 'active',
            'roles' => ['admin', 'editor'],
            'metadata' => [
                'created_after' => '2024-01-01',
                'tags' => ['important', 'urgent'],
            ],
        ]);

        // Setup different complex data for table 2
        $this->stateManager->setTableId($table2Id);
        $this->stateManager->saveState('filters', [
            'status' => 'inactive',
            'roles' => ['viewer'],
            'metadata' => [
                'created_before' => '2024-12-31',
                'tags' => ['archived'],
            ],
        ]);

        // Modify table 1 nested data
        $this->stateManager->setTableId($table1Id);
        $filters1 = $this->stateManager->getState('filters');
        $filters1['metadata']['tags'][] = 'new-tag';
        $this->stateManager->saveState('filters', $filters1);

        // Verify table 2 is not affected
        $this->stateManager->setTableId($table2Id);
        $filters2 = $this->stateManager->getState('filters');
        $this->assertEquals(['archived'], $filters2['metadata']['tags']);
        $this->assertNotContains('new-tag', $filters2['metadata']['tags']);
    }

    /**
     * Test that state operations on non-existent table don't affect existing tables.
     *
     * @return void
     */
    public function test_operations_on_nonexistent_table_do_not_affect_existing(): void
    {
        $existingTableId = 'canvastable_abc123';
        $nonExistentTableId = 'canvastable_xyz999';

        // Setup existing table
        $this->stateManager->setTableId($existingTableId);
        $this->stateManager->saveState('key1', 'value1');

        // Try to get state from non-existent table
        $this->stateManager->setTableId($nonExistentTableId);
        $value = $this->stateManager->getState('key1');
        $this->assertNull($value);

        // Save state to non-existent table (creates it)
        $this->stateManager->saveState('key2', 'value2');

        // Verify existing table is not affected
        $this->stateManager->setTableId($existingTableId);
        $this->assertEquals('value1', $this->stateManager->getState('key1'));
        $this->assertNull($this->stateManager->getState('key2'));
    }

    /**
     * Test state isolation under rapid switching between instances.
     *
     * @return void
     */
    public function test_state_isolation_under_rapid_switching(): void
    {
        $table1Id = 'canvastable_abc123';
        $table2Id = 'canvastable_def456';

        // Rapidly switch and modify state
        for ($i = 0; $i < 10; $i++) {
            $this->stateManager->setTableId($table1Id);
            $this->stateManager->saveState('counter', $i);

            $this->stateManager->setTableId($table2Id);
            $this->stateManager->saveState('counter', $i * 10);
        }

        // Verify final state is correct for each table
        $this->stateManager->setTableId($table1Id);
        $this->assertEquals(9, $this->stateManager->getState('counter'));

        $this->stateManager->setTableId($table2Id);
        $this->assertEquals(90, $this->stateManager->getState('counter'));
    }

    /**
     * Test that getAllTableStates returns isolated state for each table.
     *
     * @return void
     */
    public function test_get_all_table_states_shows_isolation(): void
    {
        $table1Id = 'canvastable_abc123';
        $table2Id = 'canvastable_def456';
        $table3Id = 'canvastable_ghi789';

        // Setup different state for each table
        $this->stateManager->setTableId($table1Id);
        $this->stateManager->saveState('key', 'value1');

        $this->stateManager->setTableId($table2Id);
        $this->stateManager->saveState('key', 'value2');

        $this->stateManager->setTableId($table3Id);
        $this->stateManager->saveState('key', 'value3');

        // Get all states
        $allStates = $this->stateManager->getAllTableStates();

        // Verify each table has its own isolated state
        $this->assertEquals('value1', $allStates[$table1Id]['key']);
        $this->assertEquals('value2', $allStates[$table2Id]['key']);
        $this->assertEquals('value3', $allStates[$table3Id]['key']);

        // Verify no cross-contamination
        $this->assertNotEquals($allStates[$table1Id]['key'], $allStates[$table2Id]['key']);
        $this->assertNotEquals($allStates[$table2Id]['key'], $allStates[$table3Id]['key']);
    }

    /**
     * Test state isolation when using getStateForTable method.
     *
     * @return void
     */
    public function test_get_state_for_table_maintains_isolation(): void
    {
        $table1Id = 'canvastable_abc123';
        $table2Id = 'canvastable_def456';

        // Setup both tables
        $this->stateManager->setTableId($table1Id);
        $this->stateManager->saveState('key', 'value1');

        $this->stateManager->setTableId($table2Id);
        $this->stateManager->saveState('key', 'value2');

        // Get state for table 1 while table 2 is active
        $table1State = $this->stateManager->getStateForTable($table1Id);
        $this->assertEquals('value1', $table1State['key']);

        // Verify current table ID hasn't changed
        $this->assertEquals($table2Id, $this->stateManager->getTableId());

        // Verify current table state is still table 2
        $this->assertEquals('value2', $this->stateManager->getState('key'));
    }

    /**
     * Test that clearing one table's state doesn't affect state history of others.
     *
     * @return void
     */
    public function test_clearing_state_preserves_other_table_history(): void
    {
        $table1Id = 'canvastable_abc123';
        $table2Id = 'canvastable_def456';

        // Make changes to both tables
        $this->stateManager->setTableId($table1Id);
        $this->stateManager->saveState('key', 'value1');
        $this->stateManager->saveState('key', 'value1_updated');

        $this->stateManager->setTableId($table2Id);
        $this->stateManager->saveState('key', 'value2');
        $this->stateManager->saveState('key', 'value2_updated');

        // Clear table 1 state
        $this->stateManager->setTableId($table1Id);
        $this->stateManager->clearAll();

        // Verify table 1 history still exists (history is separate from state)
        $table1History = $this->stateManager->getStateHistory($table1Id);
        $this->assertCount(2, $table1History);

        // Verify table 2 history is unaffected
        $table2History = $this->stateManager->getStateHistory($table2Id);
        $this->assertCount(2, $table2History);
    }

    /**
     * Test state isolation with concurrent modifications.
     *
     * Simulates scenario where multiple tables are being modified
     * in an interleaved fashion.
     *
     * @return void
     */
    public function test_state_isolation_with_concurrent_modifications(): void
    {
        $table1Id = 'canvastable_abc123';
        $table2Id = 'canvastable_def456';
        $table3Id = 'canvastable_ghi789';

        // Interleaved modifications
        $this->stateManager->setTableId($table1Id);
        $this->stateManager->saveState('step', 1);

        $this->stateManager->setTableId($table2Id);
        $this->stateManager->saveState('step', 1);

        $this->stateManager->setTableId($table1Id);
        $this->stateManager->saveState('step', 2);

        $this->stateManager->setTableId($table3Id);
        $this->stateManager->saveState('step', 1);

        $this->stateManager->setTableId($table2Id);
        $this->stateManager->saveState('step', 2);

        $this->stateManager->setTableId($table1Id);
        $this->stateManager->saveState('step', 3);

        // Verify final state for each table
        $this->stateManager->setTableId($table1Id);
        $this->assertEquals(3, $this->stateManager->getState('step'));

        $this->stateManager->setTableId($table2Id);
        $this->assertEquals(2, $this->stateManager->getState('step'));

        $this->stateManager->setTableId($table3Id);
        $this->assertEquals(1, $this->stateManager->getState('step'));
    }

    /**
     * Test that state isolation works with empty/null values.
     *
     * @return void
     */
    public function test_state_isolation_with_empty_values(): void
    {
        $table1Id = 'canvastable_abc123';
        $table2Id = 'canvastable_def456';

        // Setup table 1 with empty array
        $this->stateManager->setTableId($table1Id);
        $this->stateManager->saveState('filters', []);

        // Setup table 2 with null
        $this->stateManager->setTableId($table2Id);
        $this->stateManager->saveState('filters', null);

        // Verify isolation
        $this->stateManager->setTableId($table1Id);
        $this->assertEquals([], $this->stateManager->getState('filters'));

        $this->stateManager->setTableId($table2Id);
        $this->assertNull($this->stateManager->getState('filters'));
    }

    /**
     * Test state isolation when table IDs are similar.
     *
     * @return void
     */
    public function test_state_isolation_with_similar_table_ids(): void
    {
        $table1Id = 'canvastable_abc123';
        $table2Id = 'canvastable_abc124'; // Very similar ID
        $table3Id = 'canvastable_abc12';  // Substring of table1Id

        // Setup all tables
        $this->stateManager->setTableId($table1Id);
        $this->stateManager->saveState('key', 'value1');

        $this->stateManager->setTableId($table2Id);
        $this->stateManager->saveState('key', 'value2');

        $this->stateManager->setTableId($table3Id);
        $this->stateManager->saveState('key', 'value3');

        // Verify complete isolation despite similar IDs
        $this->stateManager->setTableId($table1Id);
        $this->assertEquals('value1', $this->stateManager->getState('key'));

        $this->stateManager->setTableId($table2Id);
        $this->assertEquals('value2', $this->stateManager->getState('key'));

        $this->stateManager->setTableId($table3Id);
        $this->assertEquals('value3', $this->stateManager->getState('key'));
    }

    /**
     * Test that hasTableState correctly identifies isolated state.
     *
     * @return void
     */
    public function test_has_table_state_respects_isolation(): void
    {
        $table1Id = 'canvastable_abc123';
        $table2Id = 'canvastable_def456';
        $table3Id = 'canvastable_ghi789';

        // Setup only table 1 and 2
        $this->stateManager->setTableId($table1Id);
        $this->stateManager->saveState('key', 'value');

        $this->stateManager->setTableId($table2Id);
        $this->stateManager->saveState('key', 'value');

        // Verify hasTableState
        $this->assertTrue($this->stateManager->hasTableState($table1Id));
        $this->assertTrue($this->stateManager->hasTableState($table2Id));
        $this->assertFalse($this->stateManager->hasTableState($table3Id));
    }

    /**
     * Test state isolation after clearing table ID.
     *
     * @return void
     */
    public function test_state_isolation_after_clearing_table_id(): void
    {
        $table1Id = 'canvastable_abc123';

        // Setup table with ID
        $this->stateManager->setTableId($table1Id);
        $this->stateManager->saveState('key', 'value1');

        // Clear table ID and use global state
        $this->stateManager->clearTableId();
        $this->stateManager->saveState('key', 'value2');

        // Verify table state is preserved
        $this->stateManager->setTableId($table1Id);
        $this->assertEquals('value1', $this->stateManager->getState('key'));

        // Verify global state is separate
        $this->stateManager->clearTableId();
        $this->assertEquals('value2', $this->stateManager->getState('key'));
    }
}
