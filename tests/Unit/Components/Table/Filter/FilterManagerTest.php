<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Table\Filter;

use Canvastack\Canvastack\Components\Table\Filter\FilterManager;
use Canvastack\Canvastack\Components\Table\Filter\Filter;
use Canvastack\Canvastack\Tests\TestCase;

/**
 * Test for FilterManager.
 */
class FilterManagerTest extends TestCase
{
    protected FilterManager $filterManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->filterManager = new FilterManager();
    }

    /**
     * Test that FilterManager can be instantiated.
     */
    public function test_filter_manager_can_be_instantiated(): void
    {
        $this->assertInstanceOf(FilterManager::class, $this->filterManager);
    }

    /**
     * Test that addFilter() adds a filter.
     */
    public function test_add_filter_adds_filter(): void
    {
        $this->filterManager->addFilter('status', 'selectbox');

        $filters = $this->filterManager->getFilters();

        $this->assertCount(1, $filters);
        $this->assertArrayHasKey('status', $filters);
        $this->assertInstanceOf(Filter::class, $filters['status']);
    }

    /**
     * Test that addFilter() with cascading relationship.
     */
    public function test_add_filter_with_cascading(): void
    {
        $this->filterManager->addFilter('region', 'selectbox', true);

        $filter = $this->filterManager->getFilter('region');

        $this->assertNotNull($filter);
        $this->assertTrue($filter->hasCascading());
    }

    /**
     * Test that getFilters() returns all filters.
     */
    public function test_get_filters_returns_all_filters(): void
    {
        $this->filterManager->addFilter('status', 'selectbox');
        $this->filterManager->addFilter('category', 'selectbox');
        $this->filterManager->addFilter('name', 'inputbox');

        $filters = $this->filterManager->getFilters();

        $this->assertCount(3, $filters);
        $this->assertArrayHasKey('status', $filters);
        $this->assertArrayHasKey('category', $filters);
        $this->assertArrayHasKey('name', $filters);
    }

    /**
     * Test that setActiveFilters() sets active filter values.
     */
    public function test_set_active_filters_sets_values(): void
    {
        $this->filterManager->addFilter('status', 'selectbox');
        $this->filterManager->addFilter('category', 'selectbox');

        $this->filterManager->setActiveFilters([
            'status' => 'active',
            'category' => 'electronics',
        ]);

        $activeFilters = $this->filterManager->getActiveFilters();

        $this->assertEquals('active', $activeFilters['status']);
        $this->assertEquals('electronics', $activeFilters['category']);
    }

    /**
     * Test that setActiveFilters() updates filter values.
     */
    public function test_set_active_filters_updates_filter_objects(): void
    {
        $this->filterManager->addFilter('status', 'selectbox');

        $this->filterManager->setActiveFilters(['status' => 'active']);

        $filter = $this->filterManager->getFilter('status');

        $this->assertEquals('active', $filter->getValue());
    }

    /**
     * Test that getActiveFilters() returns active filter values.
     */
    public function test_get_active_filters_returns_values(): void
    {
        $this->filterManager->addFilter('status', 'selectbox');
        $this->filterManager->setActiveFilters(['status' => 'active']);

        $activeFilters = $this->filterManager->getActiveFilters();

        $this->assertIsArray($activeFilters);
        $this->assertEquals('active', $activeFilters['status']);
    }

    /**
     * Test that clearFilters() clears all active filters.
     */
    public function test_clear_filters_clears_all_filters(): void
    {
        $this->filterManager->addFilter('status', 'selectbox');
        $this->filterManager->addFilter('category', 'selectbox');

        $this->filterManager->setActiveFilters([
            'status' => 'active',
            'category' => 'electronics',
        ]);

        $this->filterManager->clearFilters();

        $activeFilters = $this->filterManager->getActiveFilters();

        $this->assertEmpty($activeFilters);
    }

    /**
     * Test that clearFilters() clears filter object values.
     */
    public function test_clear_filters_clears_filter_object_values(): void
    {
        $this->filterManager->addFilter('status', 'selectbox');
        $this->filterManager->setActiveFilters(['status' => 'active']);

        $this->filterManager->clearFilters();

        $filter = $this->filterManager->getFilter('status');

        $this->assertNull($filter->getValue());
    }

    /**
     * Test that setSessionKey() sets session key.
     */
    public function test_set_session_key_sets_key(): void
    {
        $sessionKey = 'table_filters_test';

        $this->filterManager->setSessionKey($sessionKey);

        $this->assertEquals($sessionKey, $this->filterManager->getSessionKey());
    }

    /**
     * Test that saveToSession() saves filters to session.
     */
    public function test_save_to_session_saves_filters(): void
    {
        $this->filterManager->setSessionKey('table_filters_test');
        $this->filterManager->addFilter('status', 'selectbox');
        $this->filterManager->setActiveFilters(['status' => 'active']);

        $this->filterManager->saveToSession();

        $this->assertEquals(['status' => 'active'], session('table_filters_test'));
    }

    /**
     * Test that saveToSession() does nothing without session key.
     */
    public function test_save_to_session_does_nothing_without_key(): void
    {
        $this->filterManager->addFilter('status', 'selectbox');
        $this->filterManager->setActiveFilters(['status' => 'active']);

        $this->filterManager->saveToSession();

        $this->assertNull(session('table_filters_test'));
    }

    /**
     * Test that loadFromSession() loads filters from session.
     */
    public function test_load_from_session_loads_filters(): void
    {
        session(['table_filters_test' => ['status' => 'active']]);

        $this->filterManager->setSessionKey('table_filters_test');
        $this->filterManager->addFilter('status', 'selectbox');

        $this->filterManager->loadFromSession();

        $activeFilters = $this->filterManager->getActiveFilters();

        $this->assertEquals('active', $activeFilters['status']);
    }

    /**
     * Test that loadFromSession() does nothing without session key.
     */
    public function test_load_from_session_does_nothing_without_key(): void
    {
        session(['table_filters_test' => ['status' => 'active']]);

        $this->filterManager->addFilter('status', 'selectbox');

        $this->filterManager->loadFromSession();

        $activeFilters = $this->filterManager->getActiveFilters();

        $this->assertEmpty($activeFilters);
    }

    /**
     * Test that hasFilter() checks if filter exists.
     */
    public function test_has_filter_checks_existence(): void
    {
        $this->filterManager->addFilter('status', 'selectbox');

        $this->assertTrue($this->filterManager->hasFilter('status'));
        $this->assertFalse($this->filterManager->hasFilter('nonexistent'));
    }

    /**
     * Test that getFilter() returns specific filter.
     */
    public function test_get_filter_returns_filter(): void
    {
        $this->filterManager->addFilter('status', 'selectbox');

        $filter = $this->filterManager->getFilter('status');

        $this->assertInstanceOf(Filter::class, $filter);
        $this->assertEquals('status', $filter->getColumn());
    }

    /**
     * Test that getFilter() returns null for nonexistent filter.
     */
    public function test_get_filter_returns_null_for_nonexistent(): void
    {
        $filter = $this->filterManager->getFilter('nonexistent');

        $this->assertNull($filter);
    }

    /**
     * Test that getActiveFilterCount() returns correct count.
     */
    public function test_get_active_filter_count_returns_count(): void
    {
        $this->filterManager->addFilter('status', 'selectbox');
        $this->filterManager->addFilter('category', 'selectbox');
        $this->filterManager->addFilter('name', 'inputbox');

        $this->filterManager->setActiveFilters([
            'status' => 'active',
            'category' => 'electronics',
            'name' => '',
        ]);

        $count = $this->filterManager->getActiveFilterCount();

        $this->assertEquals(2, $count);
    }

    /**
     * Test that hasActiveFilters() checks if any filters are active.
     */
    public function test_has_active_filters_checks_active_state(): void
    {
        $this->filterManager->addFilter('status', 'selectbox');

        $this->assertFalse($this->filterManager->hasActiveFilters());

        $this->filterManager->setActiveFilters(['status' => 'active']);

        $this->assertTrue($this->filterManager->hasActiveFilters());
    }

    /**
     * Test that toArray() returns filters as array.
     */
    public function test_to_array_returns_filters_as_array(): void
    {
        $this->filterManager->addFilter('status', 'selectbox');
        $this->filterManager->addFilter('category', 'selectbox', true);

        $array = $this->filterManager->toArray();

        $this->assertIsArray($array);
        $this->assertCount(2, $array);
        $this->assertEquals('status', $array[0]['column']);
        $this->assertEquals('selectbox', $array[0]['type']);
        $this->assertEquals('category', $array[1]['column']);
        $this->assertTrue($array[1]['relate']);
    }

    /**
     * Test that multiple filters can be managed.
     */
    public function test_multiple_filters_can_be_managed(): void
    {
        $this->filterManager->addFilter('period', 'selectbox', true);
        $this->filterManager->addFilter('region', 'selectbox', true);
        $this->filterManager->addFilter('cluster', 'selectbox');
        $this->filterManager->addFilter('name', 'inputbox');

        $filters = $this->filterManager->getFilters();

        $this->assertCount(4, $filters);
    }

    /**
     * Test that session persistence works end-to-end.
     */
    public function test_session_persistence_works_end_to_end(): void
    {
        // Setup
        $this->filterManager->setSessionKey('table_filters_test');
        $this->filterManager->addFilter('status', 'selectbox');
        $this->filterManager->addFilter('category', 'selectbox');

        // Set and save
        $this->filterManager->setActiveFilters([
            'status' => 'active',
            'category' => 'electronics',
        ]);
        $this->filterManager->saveToSession();

        // Create new manager and load
        $newManager = new FilterManager();
        $newManager->setSessionKey('table_filters_test');
        $newManager->addFilter('status', 'selectbox');
        $newManager->addFilter('category', 'selectbox');
        $newManager->loadFromSession();

        $activeFilters = $newManager->getActiveFilters();

        $this->assertEquals('active', $activeFilters['status']);
        $this->assertEquals('electronics', $activeFilters['category']);
    }
}
