<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Table\Filter;

use Canvastack\Canvastack\Components\Table\Filter\FilterManager;
use Canvastack\Canvastack\Tests\TestCase;

/**
 * Test filter persistence functionality.
 * 
 * Validates Requirements 10.3, 10.5, 10.6, 33.3, 33.4
 */
class FilterPersistenceTest extends TestCase
{
    protected FilterManager $filterManager;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->filterManager = new FilterManager();
        $this->filterManager->setSessionKey('test_table_filters');
    }

    protected function tearDown(): void
    {
        // Clear session after each test
        session()->forget('test_table_filters');
        
        parent::tearDown();
    }

    /**
     * Test that active filters are saved to session.
     * 
     * @return void
     */
    public function test_active_filters_are_saved_to_session(): void
    {
        // Arrange
        $filters = [
            'status' => 'active',
            'category' => 'news',
        ];

        // Act
        $this->filterManager->setActiveFilters($filters);
        $this->filterManager->saveToSession();

        // Assert
        $this->assertEquals($filters, session('test_table_filters'));
    }

    /**
     * Test that active filters are loaded from session.
     * 
     * @return void
     */
    public function test_active_filters_are_loaded_from_session(): void
    {
        // Arrange
        $filters = [
            'status' => 'active',
            'category' => 'news',
        ];
        session(['test_table_filters' => $filters]);

        // Act
        $this->filterManager->loadFromSession();

        // Assert
        $this->assertEquals($filters, $this->filterManager->getActiveFilters());
    }

    /**
     * Test that session is cleared when clearing all filters.
     * 
     * @return void
     */
    public function test_session_is_cleared_when_clearing_all_filters(): void
    {
        // Arrange
        $filters = [
            'status' => 'active',
            'category' => 'news',
        ];
        session(['test_table_filters' => $filters]);

        // Act
        $this->filterManager->clearFilters();
        $this->filterManager->clearSession();

        // Assert
        $this->assertFalse(session()->has('test_table_filters'));
        $this->assertEmpty($this->filterManager->getActiveFilters());
    }

    /**
     * Test that individual filter can be cleared.
     * 
     * @return void
     */
    public function test_individual_filter_can_be_cleared(): void
    {
        // Arrange
        $filters = [
            'status' => 'active',
            'category' => 'news',
        ];
        $this->filterManager->setActiveFilters($filters);

        // Act
        $this->filterManager->clearFilter('status');

        // Assert
        $activeFilters = $this->filterManager->getActiveFilters();
        $this->assertArrayNotHasKey('status', $activeFilters);
        $this->assertArrayHasKey('category', $activeFilters);
        $this->assertEquals('news', $activeFilters['category']);
    }

    /**
     * Test that individual filter is cleared from session.
     * 
     * @return void
     */
    public function test_individual_filter_is_cleared_from_session(): void
    {
        // Arrange
        $filters = [
            'status' => 'active',
            'category' => 'news',
        ];
        session(['test_table_filters' => $filters]);

        // Act
        $this->filterManager->clearFilterFromSession('status');

        // Assert
        $sessionFilters = session('test_table_filters');
        $this->assertArrayNotHasKey('status', $sessionFilters);
        $this->assertArrayHasKey('category', $sessionFilters);
        $this->assertEquals('news', $sessionFilters['category']);
    }

    /**
     * Test that session is removed when last filter is cleared.
     * 
     * @return void
     */
    public function test_session_is_removed_when_last_filter_is_cleared(): void
    {
        // Arrange
        $filters = ['status' => 'active'];
        session(['test_table_filters' => $filters]);

        // Act
        $this->filterManager->clearFilterFromSession('status');

        // Assert
        $this->assertFalse(session()->has('test_table_filters'));
    }

    /**
     * Test that filters persist across page loads.
     * 
     * @return void
     */
    public function test_filters_persist_across_page_loads(): void
    {
        // Arrange - First page load
        $filters = [
            'status' => 'active',
            'category' => 'news',
        ];
        $this->filterManager->setActiveFilters($filters);
        $this->filterManager->saveToSession();

        // Act - Simulate second page load with new FilterManager instance
        $newFilterManager = new FilterManager();
        $newFilterManager->setSessionKey('test_table_filters');
        $newFilterManager->loadFromSession();

        // Assert
        $this->assertEquals($filters, $newFilterManager->getActiveFilters());
    }

    /**
     * Test that empty filters are not saved to session.
     * 
     * @return void
     */
    public function test_empty_filters_are_not_saved_to_session(): void
    {
        // Arrange
        $filters = [
            'status' => '',
            'category' => null,
        ];

        // Act
        $this->filterManager->setActiveFilters($filters);
        $this->filterManager->saveToSession();

        // Assert
        $sessionFilters = session('test_table_filters');
        $this->assertEquals($filters, $sessionFilters);
    }

    /**
     * Test that filter persistence works without session key.
     * 
     * @return void
     */
    public function test_filter_persistence_works_without_session_key(): void
    {
        // Arrange
        $filterManager = new FilterManager();
        // No session key set
        $filters = ['status' => 'active'];

        // Act
        $filterManager->setActiveFilters($filters);
        $filterManager->saveToSession();

        // Assert - Should not throw error, just do nothing
        $this->assertFalse(session()->has('test_table_filters'));
        $this->assertEquals($filters, $filterManager->getActiveFilters());
    }

    /**
     * Test that active filter count is correct.
     * 
     * @return void
     */
    public function test_active_filter_count_is_correct(): void
    {
        // Arrange
        $filters = [
            'status' => 'active',
            'category' => 'news',
            'empty' => '',
            'null' => null,
        ];

        // Act
        $this->filterManager->setActiveFilters($filters);

        // Assert
        $this->assertEquals(2, $this->filterManager->getActiveFilterCount());
    }

    /**
     * Test that hasActiveFilters returns correct boolean.
     * 
     * @return void
     */
    public function test_has_active_filters_returns_correct_boolean(): void
    {
        // Arrange & Act - No filters
        $this->assertFalse($this->filterManager->hasActiveFilters());

        // Act - Add filters
        $this->filterManager->setActiveFilters(['status' => 'active']);

        // Assert
        $this->assertTrue($this->filterManager->hasActiveFilters());

        // Act - Clear filters
        $this->filterManager->clearFilters();

        // Assert
        $this->assertFalse($this->filterManager->hasActiveFilters());
    }

    /**
     * Test that filter values are updated when setting active filters.
     * 
     * @return void
     */
    public function test_filter_values_are_updated_when_setting_active_filters(): void
    {
        // Arrange
        $this->filterManager->addFilter('status', 'selectbox');
        $this->filterManager->addFilter('category', 'selectbox');

        // Act
        $this->filterManager->setActiveFilters([
            'status' => 'active',
            'category' => 'news',
        ]);

        // Assert
        $statusFilter = $this->filterManager->getFilter('status');
        $categoryFilter = $this->filterManager->getFilter('category');
        
        $this->assertEquals('active', $statusFilter->getValue());
        $this->assertEquals('news', $categoryFilter->getValue());
    }

    /**
     * Test that filter values are cleared when clearing filters.
     * 
     * @return void
     */
    public function test_filter_values_are_cleared_when_clearing_filters(): void
    {
        // Arrange
        $this->filterManager->addFilter('status', 'selectbox');
        $this->filterManager->setActiveFilters(['status' => 'active']);

        // Act
        $this->filterManager->clearFilters();

        // Assert
        $statusFilter = $this->filterManager->getFilter('status');
        $this->assertNull($statusFilter->getValue());
    }

    /**
     * Test that individual filter value is cleared when clearing single filter.
     * 
     * @return void
     */
    public function test_individual_filter_value_is_cleared_when_clearing_single_filter(): void
    {
        // Arrange
        $this->filterManager->addFilter('status', 'selectbox');
        $this->filterManager->addFilter('category', 'selectbox');
        $this->filterManager->setActiveFilters([
            'status' => 'active',
            'category' => 'news',
        ]);

        // Act
        $this->filterManager->clearFilter('status');

        // Assert
        $statusFilter = $this->filterManager->getFilter('status');
        $categoryFilter = $this->filterManager->getFilter('category');
        
        $this->assertNull($statusFilter->getValue());
        $this->assertEquals('news', $categoryFilter->getValue());
    }
}

