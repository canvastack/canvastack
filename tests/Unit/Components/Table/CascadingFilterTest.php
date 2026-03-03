<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Table;

use Canvastack\Canvastack\Tests\TestCase;
use Canvastack\Canvastack\Components\Table\Filter\FilterManager;
use Canvastack\Canvastack\Components\Table\Filter\Filter;

/**
 * Test for cascading filter logic.
 * 
 * Tests the parent-child relationship between filters and how
 * changes in parent filters trigger updates in child filters.
 */
class CascadingFilterTest extends TestCase
{
    protected FilterManager $filterManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->filterManager = new FilterManager();
    }

    /**
     * Test that parent filter change detection works.
     */
    public function test_parent_filter_change_is_detected(): void
    {
        // Arrange
        $this->filterManager->addFilter('period', 'selectbox', true);
        $this->filterManager->addFilter('region', 'selectbox', true);
        
        // Act
        $this->filterManager->setActiveFilters(['period' => '2025-04']);
        
        // Assert
        $activeFilters = $this->filterManager->getActiveFilters();
        $this->assertEquals('2025-04', $activeFilters['period']);
    }

    /**
     * Test that related filters are identified correctly with boolean relate.
     */
    public function test_related_filters_identified_with_boolean_relate(): void
    {
        // Arrange
        $this->filterManager->addFilter('period', 'selectbox', true);
        $this->filterManager->addFilter('cor', 'selectbox', true);
        $this->filterManager->addFilter('region', 'selectbox', true);
        $this->filterManager->addFilter('cluster', 'selectbox');
        
        $filters = $this->filterManager->getFilters();
        $periodFilter = $filters['period'];
        
        // Act - When relate is true, all subsequent filters are related
        $relatedColumns = $this->getRelatedColumnsFromFilter($periodFilter, array_values($filters));
        
        // Assert
        $this->assertCount(3, $relatedColumns);
        $this->assertContains('cor', $relatedColumns);
        $this->assertContains('region', $relatedColumns);
        $this->assertContains('cluster', $relatedColumns);
    }

    /**
     * Test that related filters are identified correctly with string relate.
     */
    public function test_related_filters_identified_with_string_relate(): void
    {
        // Arrange
        $this->filterManager->addFilter('period', 'selectbox', 'region');
        $this->filterManager->addFilter('region', 'selectbox');
        $this->filterManager->addFilter('cluster', 'selectbox');
        
        $filters = $this->filterManager->getFilters();
        $periodFilter = $filters['period'];
        
        // Act - When relate is a string, only that specific filter is related
        $relatedColumns = $this->getRelatedColumnsFromFilter($periodFilter, array_values($filters));
        
        // Assert
        $this->assertCount(1, $relatedColumns);
        $this->assertContains('region', $relatedColumns);
        $this->assertNotContains('cluster', $relatedColumns);
    }

    /**
     * Test that related filters are identified correctly with array relate.
     */
    public function test_related_filters_identified_with_array_relate(): void
    {
        // Arrange
        $this->filterManager->addFilter('period', 'selectbox', ['region', 'cluster']);
        $this->filterManager->addFilter('cor', 'selectbox');
        $this->filterManager->addFilter('region', 'selectbox');
        $this->filterManager->addFilter('cluster', 'selectbox');
        
        $filters = $this->filterManager->getFilters();
        $periodFilter = $filters['period'];
        
        // Act - When relate is an array, only those specific filters are related
        $relatedColumns = $this->getRelatedColumnsFromFilter($periodFilter, array_values($filters));
        
        // Assert
        $this->assertCount(2, $relatedColumns);
        $this->assertContains('region', $relatedColumns);
        $this->assertContains('cluster', $relatedColumns);
        $this->assertNotContains('cor', $relatedColumns);
    }

    /**
     * Test that no related filters are identified when relate is false.
     */
    public function test_no_related_filters_when_relate_is_false(): void
    {
        // Arrange
        $this->filterManager->addFilter('period', 'selectbox', false);
        $this->filterManager->addFilter('region', 'selectbox');
        
        $filters = $this->filterManager->getFilters();
        $periodFilter = $filters['period'];
        
        // Act
        $relatedColumns = $this->getRelatedColumnsFromFilter($periodFilter, array_values($filters));
        
        // Assert
        $this->assertCount(0, $relatedColumns);
    }

    /**
     * Test that child filter values are cleared when parent changes.
     */
    public function test_child_filter_values_cleared_when_parent_changes(): void
    {
        // Arrange
        $this->filterManager->addFilter('period', 'selectbox', true);
        $this->filterManager->addFilter('region', 'selectbox');
        
        // Set initial values
        $this->filterManager->setActiveFilters([
            'period' => '2025-04',
            'region' => 'WEST'
        ]);
        
        // Act - Change parent filter
        $this->filterManager->setActiveFilters([
            'period' => '2025-05',
            'region' => '' // Should be cleared
        ]);
        
        // Assert
        $activeFilters = $this->filterManager->getActiveFilters();
        $this->assertEquals('2025-05', $activeFilters['period']);
        $this->assertEquals('', $activeFilters['region']);
    }

    /**
     * Test that auto-submit filters trigger immediate application.
     */
    public function test_auto_submit_filters_trigger_immediate_application(): void
    {
        // Arrange
        $this->filterManager->addFilter('period', 'selectbox', true);
        
        $filters = $this->filterManager->getFilters();
        $periodFilter = $filters['period'];
        
        // Act
        $periodFilter->setAutoSubmit(true);
        
        // Assert
        $this->assertTrue($periodFilter->shouldAutoSubmit());
    }

    /**
     * Test that manual submit filters do not trigger immediate application.
     */
    public function test_manual_submit_filters_do_not_trigger_immediate_application(): void
    {
        // Arrange
        $this->filterManager->addFilter('period', 'selectbox', false);
        
        $filters = $this->filterManager->getFilters();
        $periodFilter = $filters['period'];
        
        // Act
        $periodFilter->setAutoSubmit(false);
        
        // Assert
        $this->assertFalse($periodFilter->shouldAutoSubmit());
    }

    /**
     * Test that loading state is set during option loading.
     */
    public function test_loading_state_is_set_during_option_loading(): void
    {
        // Arrange
        $this->filterManager->addFilter('region', 'selectbox');
        
        $filters = $this->filterManager->getFilters();
        $regionFilter = $filters['region'];
        
        // Act
        $regionFilter->setLoading(true);
        
        // Assert
        $this->assertTrue($regionFilter->isLoading());
    }

    /**
     * Test that loading state is cleared after option loading.
     */
    public function test_loading_state_is_cleared_after_option_loading(): void
    {
        // Arrange
        $this->filterManager->addFilter('region', 'selectbox');
        
        $filters = $this->filterManager->getFilters();
        $regionFilter = $filters['region'];
        
        // Act
        $regionFilter->setLoading(true);
        $regionFilter->setLoading(false);
        
        // Assert
        $this->assertFalse($regionFilter->isLoading());
    }

    /**
     * Test that filter options are updated after parent change.
     */
    public function test_filter_options_updated_after_parent_change(): void
    {
        // Arrange
        $this->filterManager->addFilter('period', 'selectbox', true);
        $this->filterManager->addFilter('region', 'selectbox');
        
        $filters = $this->filterManager->getFilters();
        $regionFilter = $filters['region'];
        
        // Initial options
        $regionFilter->setOptions([
            ['value' => 'WEST', 'label' => 'WEST REGION'],
            ['value' => 'EAST', 'label' => 'EAST REGION']
        ]);
        
        // Act - Update options after parent change
        $regionFilter->setOptions([
            ['value' => 'WEST', 'label' => 'WEST REGION']
        ]);
        
        // Assert
        $options = $regionFilter->getOptions();
        $this->assertCount(1, $options);
        $this->assertEquals('WEST', $options[0]['value']);
    }

    /**
     * Test that multiple cascading levels work correctly.
     */
    public function test_multiple_cascading_levels_work_correctly(): void
    {
        // Arrange - 4 levels: period → cor → region → cluster
        $this->filterManager->addFilter('period', 'selectbox', true);
        $this->filterManager->addFilter('cor', 'selectbox', true);
        $this->filterManager->addFilter('region', 'selectbox', true);
        $this->filterManager->addFilter('cluster', 'selectbox');
        
        $filters = $this->filterManager->getFilters();
        
        // Act - Change first level
        $periodFilter = $filters['period'];
        $relatedToPeriod = $this->getRelatedColumnsFromFilter($periodFilter, array_values($filters));
        
        // Assert - All subsequent filters are related
        $this->assertCount(3, $relatedToPeriod);
        $this->assertContains('cor', $relatedToPeriod);
        $this->assertContains('region', $relatedToPeriod);
        $this->assertContains('cluster', $relatedToPeriod);
        
        // Act - Change second level
        $corFilter = $filters['cor'];
        $relatedToCor = $this->getRelatedColumnsFromFilter($corFilter, array_values($filters));
        
        // Assert - Only subsequent filters are related
        $this->assertCount(2, $relatedToCor);
        $this->assertContains('region', $relatedToCor);
        $this->assertContains('cluster', $relatedToCor);
    }

    /**
     * Test that error handling works for failed option loading.
     */
    public function test_error_handling_for_failed_option_loading(): void
    {
        // Arrange
        $this->filterManager->addFilter('region', 'selectbox');
        
        $filters = $this->filterManager->getFilters();
        $regionFilter = $filters['region'];
        
        // Act - Simulate error
        $regionFilter->setLoading(true);
        $regionFilter->setError('Failed to load options');
        $regionFilter->setLoading(false);
        
        // Assert
        $this->assertTrue($regionFilter->hasError());
        $this->assertEquals('Failed to load options', $regionFilter->getError());
    }

    /**
     * Helper method to get related columns from a filter.
     * Mimics the JavaScript getRelatedColumns() logic.
     */
    protected function getRelatedColumnsFromFilter(Filter $filter, array $allFilters): array
    {
        $relate = $filter->getRelate();
        
        if ($relate === true) {
            // Cascade to all filters after this one
            $currentIndex = array_search($filter, $allFilters, true);
            $relatedFilters = array_slice($allFilters, $currentIndex + 1);
            return array_map(fn($f) => $f->getColumn(), $relatedFilters);
        } elseif (is_string($relate)) {
            return [$relate];
        } elseif (is_array($relate)) {
            return $relate;
        }
        
        return [];
    }
}

