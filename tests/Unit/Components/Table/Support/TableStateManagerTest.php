<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Table\Support;

use Canvastack\Canvastack\Components\Table\Support\TableStateManager;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Support\Facades\Session;

/**
 * Test for TableStateManager.
 *
 * Tests state management functionality including:
 * - save() method
 * - load() method
 * - clear() method
 * - merge() method
 * - Sort state management
 * - Filter state management
 * - Page size management
 * - Column visibility management
 * - Column widths management
 * - Search value management
 * - Custom state management
 *
 * @covers \Canvastack\Canvastack\Components\Table\Support\TableStateManager
 */
class TableStateManagerTest extends TestCase
{
    /**
     * Table state manager instance.
     *
     * @var TableStateManager
     */
    protected TableStateManager $stateManager;

    /**
     * Test table ID.
     *
     * @var string
     */
    protected string $tableId = 'test_table';

    /**
     * Setup test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Clear session before each test
        Session::flush();

        // Create state manager instance
        $this->stateManager = new TableStateManager($this->tableId);
    }

    /**
     * Teardown test environment.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        // Clear session after each test
        Session::flush();

        parent::tearDown();
    }

    /**
     * Test that state manager can be instantiated.
     *
     * @return void
     */
    public function test_state_manager_can_be_instantiated(): void
    {
        $this->assertInstanceOf(TableStateManager::class, $this->stateManager);
        $this->assertEquals($this->tableId, $this->stateManager->getTableId());
    }

    /**
     * Test that save() method persists state to session.
     *
     * @return void
     */
    public function test_save_method_persists_state_to_session(): void
    {
        // Arrange
        $this->stateManager->setSortState('name', 'asc');

        // Act
        $this->stateManager->save();

        // Assert
        $sessionKey = 'table_state_' . $this->tableId;
        $this->assertTrue(Session::has($sessionKey));

        $sessionData = Session::get($sessionKey);
        $this->assertIsArray($sessionData);
        $this->assertArrayHasKey('sort', $sessionData);
        $this->assertEquals('name', $sessionData['sort']['column']);
        $this->assertEquals('asc', $sessionData['sort']['direction']);
    }

    /**
     * Test that save() method updates existing session data.
     *
     * @return void
     */
    public function test_save_method_updates_existing_session_data(): void
    {
        // Arrange
        $this->stateManager->setSortState('name', 'asc');
        $this->stateManager->save();

        // Act - Update state
        $this->stateManager->setSortState('email', 'desc');
        $this->stateManager->save();

        // Assert
        $sessionKey = 'table_state_' . $this->tableId;
        $sessionData = Session::get($sessionKey);

        $this->assertEquals('email', $sessionData['sort']['column']);
        $this->assertEquals('desc', $sessionData['sort']['direction']);
    }

    /**
     * Test that load() method retrieves state from session.
     *
     * @return void
     */
    public function test_load_method_retrieves_state_from_session(): void
    {
        // Arrange
        $sessionKey = 'table_state_' . $this->tableId;
        $testState = [
            'sort' => ['column' => 'name', 'direction' => 'asc'],
            'page_size' => 25,
            'filters' => ['status' => 'active'],
        ];
        Session::put($sessionKey, $testState);

        // Act
        $newStateManager = new TableStateManager($this->tableId);

        // Assert
        $this->assertEquals('name', $newStateManager->getSortState()['column']);
        $this->assertEquals('asc', $newStateManager->getSortState()['direction']);
        $this->assertEquals(25, $newStateManager->getPageSize());
        $this->assertEquals(['status' => 'active'], $newStateManager->getFilterState());
    }

    /**
     * Test that load() method returns empty array when no session data exists.
     *
     * @return void
     */
    public function test_load_method_returns_empty_array_when_no_session_data(): void
    {
        // Arrange - No session data

        // Act
        $newStateManager = new TableStateManager('new_table');

        // Assert
        $this->assertTrue($newStateManager->isEmpty());
        $this->assertNull($newStateManager->getSortState());
        $this->assertEquals([], $newStateManager->getFilterState());
    }

    /**
     * Test that clear() method removes all state from session.
     *
     * @return void
     */
    public function test_clear_method_removes_all_state_from_session(): void
    {
        // Arrange
        $this->stateManager->setSortState('name', 'asc');
        $this->stateManager->setPageSize(50);
        $this->stateManager->setFilterState(['status' => 'active']);
        $this->stateManager->save();

        $sessionKey = 'table_state_' . $this->tableId;
        $this->assertTrue(Session::has($sessionKey));

        // Act
        $this->stateManager->clear();

        // Assert
        $this->assertFalse(Session::has($sessionKey));
        $this->assertTrue($this->stateManager->isEmpty());
        $this->assertNull($this->stateManager->getSortState());
        $this->assertEquals([], $this->stateManager->getFilterState());
    }

    /**
     * Test that clear() method resets internal state.
     *
     * @return void
     */
    public function test_clear_method_resets_internal_state(): void
    {
        // Arrange
        $this->stateManager->setSortState('name', 'asc');
        $this->stateManager->setPageSize(50);

        // Act
        $this->stateManager->clear();

        // Assert
        $this->assertEquals([], $this->stateManager->getAll());
        $this->assertTrue($this->stateManager->isEmpty());
    }

    /**
     * Test that merge() method combines new state with existing state.
     *
     * @return void
     */
    public function test_merge_method_combines_new_state_with_existing(): void
    {
        // Arrange
        $this->stateManager->setSortState('name', 'asc');
        $this->stateManager->setPageSize(25);

        // Act
        $this->stateManager->merge([
            'filters' => ['status' => 'active'],
            'search' => 'test',
        ]);

        // Assert
        $state = $this->stateManager->getAll();
        $this->assertArrayHasKey('sort', $state);
        $this->assertArrayHasKey('page_size', $state);
        $this->assertArrayHasKey('filters', $state);
        $this->assertArrayHasKey('search', $state);

        $this->assertEquals('name', $state['sort']['column']);
        $this->assertEquals(25, $state['page_size']);
        $this->assertEquals(['status' => 'active'], $state['filters']);
        $this->assertEquals('test', $state['search']);
    }

    /**
     * Test that merge() method overwrites existing keys.
     *
     * @return void
     */
    public function test_merge_method_overwrites_existing_keys(): void
    {
        // Arrange
        $this->stateManager->setPageSize(25);
        $this->stateManager->setFilterState(['status' => 'active']);

        // Act
        $this->stateManager->merge([
            'page_size' => 50,
            'filters' => ['status' => 'inactive', 'role' => 'admin'],
        ]);

        // Assert
        $this->assertEquals(50, $this->stateManager->getPageSize());
        $this->assertEquals(
            ['status' => 'inactive', 'role' => 'admin'],
            $this->stateManager->getFilterState()
        );
    }

    /**
     * Test that merge() method automatically saves to session.
     *
     * @return void
     */
    public function test_merge_method_automatically_saves_to_session(): void
    {
        // Arrange
        $this->stateManager->setSortState('name', 'asc');

        // Act
        $this->stateManager->merge(['page_size' => 50]);

        // Assert
        $sessionKey = 'table_state_' . $this->tableId;
        $sessionData = Session::get($sessionKey);

        $this->assertArrayHasKey('sort', $sessionData);
        $this->assertArrayHasKey('page_size', $sessionData);
        $this->assertEquals(50, $sessionData['page_size']);
    }

    /**
     * Test that setSortState() and getSortState() work correctly.
     *
     * @return void
     */
    public function test_sort_state_management(): void
    {
        // Arrange & Act
        $this->stateManager->setSortState('name', 'asc');

        // Assert
        $sortState = $this->stateManager->getSortState();
        $this->assertIsArray($sortState);
        $this->assertEquals('name', $sortState['column']);
        $this->assertEquals('asc', $sortState['direction']);
    }

    /**
     * Test that setSortState() defaults to 'asc' direction.
     *
     * @return void
     */
    public function test_sort_state_defaults_to_asc_direction(): void
    {
        // Arrange & Act
        $this->stateManager->setSortState('name');

        // Assert
        $sortState = $this->stateManager->getSortState();
        $this->assertEquals('asc', $sortState['direction']);
    }

    /**
     * Test that setSortState() with null clears sort state.
     *
     * @return void
     */
    public function test_sort_state_can_be_cleared(): void
    {
        // Arrange
        $this->stateManager->setSortState('name', 'asc');
        $this->assertNotNull($this->stateManager->getSortState());

        // Act
        $this->stateManager->setSortState(null);

        // Assert
        $this->assertNull($this->stateManager->getSortState());
    }

    /**
     * Test that setFilterState() and getFilterState() work correctly.
     *
     * @return void
     */
    public function test_filter_state_management(): void
    {
        // Arrange
        $filters = [
            'status' => 'active',
            'role' => 'admin',
            'created_after' => '2024-01-01',
        ];

        // Act
        $this->stateManager->setFilterState($filters);

        // Assert
        $this->assertEquals($filters, $this->stateManager->getFilterState());
    }

    /**
     * Test that clearFilterState() removes filter state.
     *
     * @return void
     */
    public function test_filter_state_can_be_cleared(): void
    {
        // Arrange
        $this->stateManager->setFilterState(['status' => 'active']);
        $this->assertNotEmpty($this->stateManager->getFilterState());

        // Act
        $this->stateManager->clearFilterState();

        // Assert
        $this->assertEquals([], $this->stateManager->getFilterState());
    }

    /**
     * Test that setPageSize() and getPageSize() work correctly.
     *
     * @return void
     */
    public function test_page_size_management(): void
    {
        // Arrange & Act
        $this->stateManager->setPageSize(50);

        // Assert
        $this->assertEquals(50, $this->stateManager->getPageSize());
    }

    /**
     * Test that getPageSize() returns default when not set.
     *
     * @return void
     */
    public function test_page_size_returns_default_when_not_set(): void
    {
        // Arrange - No page size set

        // Act & Assert
        $this->assertEquals(10, $this->stateManager->getPageSize());
        $this->assertEquals(25, $this->stateManager->getPageSize(25));
    }

    /**
     * Test that setColumnVisibility() and getColumnVisibility() work correctly.
     *
     * @return void
     */
    public function test_column_visibility_management(): void
    {
        // Arrange
        $hiddenColumns = ['id', 'password', 'created_at'];

        // Act
        $this->stateManager->setColumnVisibility($hiddenColumns);

        // Assert
        $this->assertEquals($hiddenColumns, $this->stateManager->getColumnVisibility());
    }

    /**
     * Test that getColumnVisibility() returns empty array when not set.
     *
     * @return void
     */
    public function test_column_visibility_returns_empty_array_when_not_set(): void
    {
        // Arrange - No column visibility set

        // Act & Assert
        $this->assertEquals([], $this->stateManager->getColumnVisibility());
    }

    /**
     * Test that setColumnWidths() and getColumnWidths() work correctly.
     *
     * @return void
     */
    public function test_column_widths_management(): void
    {
        // Arrange
        $widths = [
            'name' => 200,
            'email' => 250,
            'status' => 100,
        ];

        // Act
        $this->stateManager->setColumnWidths($widths);

        // Assert
        $this->assertEquals($widths, $this->stateManager->getColumnWidths());
    }

    /**
     * Test that getColumnWidth() returns specific column width.
     *
     * @return void
     */
    public function test_get_specific_column_width(): void
    {
        // Arrange
        $this->stateManager->setColumnWidths([
            'name' => 200,
            'email' => 250,
        ]);

        // Act & Assert
        $this->assertEquals(200, $this->stateManager->getColumnWidth('name'));
        $this->assertEquals(250, $this->stateManager->getColumnWidth('email'));
        $this->assertNull($this->stateManager->getColumnWidth('status'));
    }

    /**
     * Test that setCurrentPage() and getCurrentPage() work correctly.
     *
     * @return void
     */
    public function test_current_page_management(): void
    {
        // Arrange & Act
        $this->stateManager->setCurrentPage(5);

        // Assert
        $this->assertEquals(5, $this->stateManager->getCurrentPage());
    }

    /**
     * Test that getCurrentPage() returns default when not set.
     *
     * @return void
     */
    public function test_current_page_returns_default_when_not_set(): void
    {
        // Arrange - No current page set

        // Act & Assert
        $this->assertEquals(1, $this->stateManager->getCurrentPage());
        $this->assertEquals(3, $this->stateManager->getCurrentPage(3));
    }

    /**
     * Test that setSearchValue() and getSearchValue() work correctly.
     *
     * @return void
     */
    public function test_search_value_management(): void
    {
        // Arrange & Act
        $this->stateManager->setSearchValue('test search');

        // Assert
        $this->assertEquals('test search', $this->stateManager->getSearchValue());
    }

    /**
     * Test that setSearchValue() with null clears search value.
     *
     * @return void
     */
    public function test_search_value_can_be_cleared_with_null(): void
    {
        // Arrange
        $this->stateManager->setSearchValue('test');
        $this->assertNotNull($this->stateManager->getSearchValue());

        // Act
        $this->stateManager->setSearchValue(null);

        // Assert
        $this->assertNull($this->stateManager->getSearchValue());
    }

    /**
     * Test that setSearchValue() with empty string clears search value.
     *
     * @return void
     */
    public function test_search_value_can_be_cleared_with_empty_string(): void
    {
        // Arrange
        $this->stateManager->setSearchValue('test');
        $this->assertNotNull($this->stateManager->getSearchValue());

        // Act
        $this->stateManager->setSearchValue('');

        // Assert
        $this->assertNull($this->stateManager->getSearchValue());
    }

    /**
     * Test that set() and get() work for custom state values.
     *
     * @return void
     */
    public function test_custom_state_management(): void
    {
        // Arrange & Act
        $this->stateManager->set('custom_key', 'custom_value');

        // Assert
        $this->assertEquals('custom_value', $this->stateManager->get('custom_key'));
    }

    /**
     * Test that get() returns default for non-existent keys.
     *
     * @return void
     */
    public function test_get_returns_default_for_non_existent_keys(): void
    {
        // Arrange - No custom key set

        // Act & Assert
        $this->assertNull($this->stateManager->get('non_existent'));
        $this->assertEquals('default', $this->stateManager->get('non_existent', 'default'));
    }

    /**
     * Test that has() checks if state key exists.
     *
     * @return void
     */
    public function test_has_checks_if_state_key_exists(): void
    {
        // Arrange
        $this->stateManager->set('existing_key', 'value');

        // Act & Assert
        $this->assertTrue($this->stateManager->has('existing_key'));
        $this->assertFalse($this->stateManager->has('non_existent_key'));
    }

    /**
     * Test that remove() removes custom state value.
     *
     * @return void
     */
    public function test_remove_removes_custom_state_value(): void
    {
        // Arrange
        $this->stateManager->set('key_to_remove', 'value');
        $this->assertTrue($this->stateManager->has('key_to_remove'));

        // Act
        $this->stateManager->remove('key_to_remove');

        // Assert
        $this->assertFalse($this->stateManager->has('key_to_remove'));
    }

    /**
     * Test that getAll() returns all state data.
     *
     * @return void
     */
    public function test_get_all_returns_all_state_data(): void
    {
        // Arrange
        $this->stateManager->setSortState('name', 'asc');
        $this->stateManager->setPageSize(50);
        $this->stateManager->setFilterState(['status' => 'active']);
        $this->stateManager->set('custom', 'value');

        // Act
        $allState = $this->stateManager->getAll();

        // Assert
        $this->assertIsArray($allState);
        $this->assertArrayHasKey('sort', $allState);
        $this->assertArrayHasKey('page_size', $allState);
        $this->assertArrayHasKey('filters', $allState);
        $this->assertArrayHasKey('custom', $allState);
    }

    /**
     * Test that isEmpty() returns true when state is empty.
     *
     * @return void
     */
    public function test_is_empty_returns_true_when_state_is_empty(): void
    {
        // Arrange - New state manager with no data

        // Act & Assert
        $this->assertTrue($this->stateManager->isEmpty());
    }

    /**
     * Test that isEmpty() returns false when state has data.
     *
     * @return void
     */
    public function test_is_empty_returns_false_when_state_has_data(): void
    {
        // Arrange
        $this->stateManager->setPageSize(25);

        // Act & Assert
        $this->assertFalse($this->stateManager->isEmpty());
    }

    /**
     * Test that toJson() exports state as JSON string.
     *
     * @return void
     */
    public function test_to_json_exports_state_as_json_string(): void
    {
        // Arrange
        $this->stateManager->setSortState('name', 'asc');
        $this->stateManager->setPageSize(25);

        // Act
        $json = $this->stateManager->toJson();

        // Assert
        $this->assertIsString($json);
        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('sort', $decoded);
        $this->assertArrayHasKey('page_size', $decoded);
    }

    /**
     * Test that fromJson() imports state from JSON string.
     *
     * @return void
     */
    public function test_from_json_imports_state_from_json_string(): void
    {
        // Arrange
        $json = json_encode([
            'sort' => ['column' => 'email', 'direction' => 'desc'],
            'page_size' => 100,
            'filters' => ['role' => 'admin'],
        ]);

        // Act
        $this->stateManager->fromJson($json);

        // Assert
        $this->assertEquals('email', $this->stateManager->getSortState()['column']);
        $this->assertEquals('desc', $this->stateManager->getSortState()['direction']);
        $this->assertEquals(100, $this->stateManager->getPageSize());
        $this->assertEquals(['role' => 'admin'], $this->stateManager->getFilterState());
    }

    /**
     * Test that fromJson() saves state to session.
     *
     * @return void
     */
    public function test_from_json_saves_state_to_session(): void
    {
        // Arrange
        $json = json_encode(['page_size' => 50]);

        // Act
        $this->stateManager->fromJson($json);

        // Assert
        $sessionKey = 'table_state_' . $this->tableId;
        $this->assertTrue(Session::has($sessionKey));
        $sessionData = Session::get($sessionKey);
        $this->assertEquals(50, $sessionData['page_size']);
    }

    /**
     * Test that state persists across multiple instances with same table ID.
     *
     * @return void
     */
    public function test_state_persists_across_multiple_instances(): void
    {
        // Arrange
        $manager1 = new TableStateManager('shared_table');
        $manager1->setSortState('name', 'asc');
        $manager1->setPageSize(50);
        $manager1->save();

        // Act
        $manager2 = new TableStateManager('shared_table');

        // Assert
        $this->assertEquals('name', $manager2->getSortState()['column']);
        $this->assertEquals(50, $manager2->getPageSize());
    }

    /**
     * Test that different table IDs have separate state.
     *
     * @return void
     */
    public function test_different_table_ids_have_separate_state(): void
    {
        // Arrange
        $manager1 = new TableStateManager('table1');
        $manager1->setPageSize(25);
        $manager1->save();

        $manager2 = new TableStateManager('table2');
        $manager2->setPageSize(50);
        $manager2->save();

        // Act & Assert
        $this->assertEquals(25, $manager1->getPageSize());
        $this->assertEquals(50, $manager2->getPageSize());
    }

    /**
     * Test that complex state with multiple properties is saved and loaded correctly.
     *
     * @return void
     */
    public function test_complex_state_is_saved_and_loaded_correctly(): void
    {
        // Arrange
        $this->stateManager->setSortState('name', 'desc');
        $this->stateManager->setPageSize(100);
        $this->stateManager->setFilterState([
            'status' => 'active',
            'role' => 'admin',
            'created_after' => '2024-01-01',
        ]);
        $this->stateManager->setColumnVisibility(['id', 'password']);
        $this->stateManager->setColumnWidths(['name' => 200, 'email' => 250]);
        $this->stateManager->setCurrentPage(3);
        $this->stateManager->setSearchValue('test search');
        $this->stateManager->set('custom_field', 'custom_value');
        $this->stateManager->save();

        // Act
        $newManager = new TableStateManager($this->tableId);

        // Assert
        $this->assertEquals('name', $newManager->getSortState()['column']);
        $this->assertEquals('desc', $newManager->getSortState()['direction']);
        $this->assertEquals(100, $newManager->getPageSize());
        $this->assertEquals([
            'status' => 'active',
            'role' => 'admin',
            'created_after' => '2024-01-01',
        ], $newManager->getFilterState());
        $this->assertEquals(['id', 'password'], $newManager->getColumnVisibility());
        $this->assertEquals(['name' => 200, 'email' => 250], $newManager->getColumnWidths());
        $this->assertEquals(3, $newManager->getCurrentPage());
        $this->assertEquals('test search', $newManager->getSearchValue());
        $this->assertEquals('custom_value', $newManager->get('custom_field'));
    }

    /**
     * Test that state manager handles empty filter state correctly.
     *
     * @return void
     */
    public function test_handles_empty_filter_state_correctly(): void
    {
        // Arrange & Act
        $this->stateManager->setFilterState([]);

        // Assert
        $this->assertEquals([], $this->stateManager->getFilterState());
    }

    /**
     * Test that state manager handles zero page size correctly.
     *
     * @return void
     */
    public function test_handles_zero_page_size_correctly(): void
    {
        // Arrange & Act
        $this->stateManager->setPageSize(0);

        // Assert
        $this->assertEquals(0, $this->stateManager->getPageSize());
    }

    /**
     * Test that state manager handles page 1 correctly.
     *
     * @return void
     */
    public function test_handles_page_one_correctly(): void
    {
        // Arrange & Act
        $this->stateManager->setCurrentPage(1);

        // Assert
        $this->assertEquals(1, $this->stateManager->getCurrentPage());
    }
}
