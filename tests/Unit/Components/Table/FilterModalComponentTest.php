<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Table;

use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Support\Facades\View;

/**
 * Test for Filter Modal Component.
 *
 * Tests the Blade component rendering and functionality.
 */
class FilterModalComponentTest extends TestCase
{
    /**
     * Test that filter modal component renders correctly.
     */
    public function test_filter_modal_renders_correctly(): void
    {
        $filters = [
            [
                'column' => 'period',
                'label' => 'Period',
                'type' => 'selectbox',
                'options' => [
                    ['value' => '2025-01', 'label' => 'January 2025'],
                    ['value' => '2025-02', 'label' => 'February 2025'],
                ],
                'loading' => false,
                'relate' => false,
                'autoSubmit' => false,
            ],
        ];

        $html = View::make('canvastack::components.table.filter-modal', [
            'filters' => $filters,
            'activeFilters' => [],
            'tableName' => 'test_table',
            'activeFilterCount' => 0,
        ])->render();

        $this->assertStringContainsString('filter-button', $html);
        $this->assertStringContainsString('filter-modal', $html);
        $this->assertStringContainsString('Period', $html);
    }

    /**
     * Test that filter modal shows active filter count badge.
     */
    public function test_filter_modal_shows_active_filter_count(): void
    {
        $filters = [
            [
                'column' => 'period',
                'label' => 'Period',
                'type' => 'selectbox',
                'options' => [],
                'loading' => false,
                'relate' => false,
                'autoSubmit' => false,
            ],
        ];

        $html = View::make('canvastack::components.table.filter-modal', [
            'filters' => $filters,
            'activeFilters' => ['period' => '2025-01'],
            'tableName' => 'test_table',
            'activeFilterCount' => 1,
        ])->render();

        $this->assertStringContainsString('badge', $html);
        $this->assertStringContainsString('activeFilterCount', $html);
    }

    /**
     * Test that filter modal renders selectbox filter type.
     */
    public function test_filter_modal_renders_selectbox_type(): void
    {
        $filters = [
            [
                'column' => 'status',
                'label' => 'Status',
                'type' => 'selectbox',
                'options' => [
                    ['value' => 'active', 'label' => 'Active'],
                    ['value' => 'inactive', 'label' => 'Inactive'],
                ],
                'loading' => false,
                'relate' => false,
                'autoSubmit' => false,
            ],
        ];

        $html = View::make('canvastack::components.table.filter-modal', [
            'filters' => $filters,
            'activeFilters' => [],
            'tableName' => 'test_table',
            'activeFilterCount' => 0,
        ])->render();

        $this->assertStringContainsString('selectbox', $html);
        $this->assertStringContainsString('Status', $html);
    }

    /**
     * Test that filter modal renders inputbox filter type.
     */
    public function test_filter_modal_renders_inputbox_type(): void
    {
        $filters = [
            [
                'column' => 'name',
                'label' => 'Name',
                'type' => 'inputbox',
                'options' => [],
                'loading' => false,
                'relate' => false,
                'autoSubmit' => false,
            ],
        ];

        $html = View::make('canvastack::components.table.filter-modal', [
            'filters' => $filters,
            'activeFilters' => [],
            'tableName' => 'test_table',
            'activeFilterCount' => 0,
        ])->render();

        $this->assertStringContainsString('inputbox', $html);
        $this->assertStringContainsString('Name', $html);
    }

    /**
     * Test that filter modal renders datebox filter type.
     */
    public function test_filter_modal_renders_datebox_type(): void
    {
        $filters = [
            [
                'column' => 'created_at',
                'label' => 'Created Date',
                'type' => 'datebox',
                'options' => [],
                'loading' => false,
                'relate' => false,
                'autoSubmit' => false,
            ],
        ];

        $html = View::make('canvastack::components.table.filter-modal', [
            'filters' => $filters,
            'activeFilters' => [],
            'tableName' => 'test_table',
            'activeFilterCount' => 0,
        ])->render();

        $this->assertStringContainsString('datebox', $html);
        $this->assertStringContainsString('Created Date', $html);
    }

    /**
     * Test that filter modal includes Alpine.js data.
     */
    public function test_filter_modal_includes_alpine_data(): void
    {
        $filters = [
            [
                'column' => 'period',
                'label' => 'Period',
                'type' => 'selectbox',
                'options' => [],
                'loading' => false,
                'relate' => false,
                'autoSubmit' => false,
            ],
        ];

        $html = View::make('canvastack::components.table.filter-modal', [
            'filters' => $filters,
            'activeFilters' => [],
            'tableName' => 'test_table',
            'activeFilterCount' => 0,
        ])->render();

        $this->assertStringContainsString('x-data="filterModal()"', $html);
        $this->assertStringContainsString('function filterModal()', $html);
    }

    /**
     * Test that filter modal includes action buttons.
     */
    public function test_filter_modal_includes_action_buttons(): void
    {
        $filters = [];

        $html = View::make('canvastack::components.table.filter-modal', [
            'filters' => $filters,
            'activeFilters' => [],
            'tableName' => 'test_table',
            'activeFilterCount' => 0,
        ])->render();

        $this->assertStringContainsString('apply-filter', $html);
        $this->assertStringContainsString('applyFilters', $html);
        $this->assertStringContainsString('clearFilters', $html);
    }

    /**
     * Test that filter modal includes loading states.
     */
    public function test_filter_modal_includes_loading_states(): void
    {
        $filters = [
            [
                'column' => 'region',
                'label' => 'Region',
                'type' => 'selectbox',
                'options' => [],
                'loading' => true,
                'relate' => false,
                'autoSubmit' => false,
            ],
        ];

        $html = View::make('canvastack::components.table.filter-modal', [
            'filters' => $filters,
            'activeFilters' => [],
            'tableName' => 'test_table',
            'activeFilterCount' => 0,
        ])->render();

        $this->assertStringContainsString('loading', $html);
        $this->assertStringContainsString('filter.loading', $html);
    }

    /**
     * Test that filter modal includes CSRF token handling.
     */
    public function test_filter_modal_includes_csrf_token(): void
    {
        $filters = [];

        $html = View::make('canvastack::components.table.filter-modal', [
            'filters' => $filters,
            'activeFilters' => [],
            'tableName' => 'test_table',
            'activeFilterCount' => 0,
        ])->render();

        $this->assertStringContainsString('X-CSRF-TOKEN', $html);
        $this->assertStringContainsString('csrf-token', $html);
    }

    /**
     * Test that filter modal includes API endpoints.
     */
    public function test_filter_modal_includes_api_endpoints(): void
    {
        $filters = [];

        $html = View::make('canvastack::components.table.filter-modal', [
            'filters' => $filters,
            'activeFilters' => [],
            'tableName' => 'test_table',
            'activeFilterCount' => 0,
        ])->render();

        $this->assertStringContainsString('/datatable/filter-options', $html);
        $this->assertStringContainsString('/datatable/save-filters', $html);
    }

    /**
     * Test that filter modal includes animations.
     */
    public function test_filter_modal_includes_animations(): void
    {
        $filters = [];

        $html = View::make('canvastack::components.table.filter-modal', [
            'filters' => $filters,
            'activeFilters' => [],
            'tableName' => 'test_table',
            'activeFilterCount' => 0,
        ])->render();

        $this->assertStringContainsString('x-transition', $html);
        $this->assertStringContainsString('transition ease-out', $html);
    }

    /**
     * Test that filter modal includes dark mode support.
     */
    public function test_filter_modal_includes_dark_mode_support(): void
    {
        $filters = [];

        $html = View::make('canvastack::components.table.filter-modal', [
            'filters' => $filters,
            'activeFilters' => [],
            'tableName' => 'test_table',
            'activeFilterCount' => 0,
        ])->render();

        $this->assertStringContainsString('dark:bg-gray-900', $html);
        $this->assertStringContainsString('dark:text-gray-', $html);
    }

    /**
     * Test that filter modal includes accessibility attributes.
     */
    public function test_filter_modal_includes_accessibility_attributes(): void
    {
        $filters = [];

        $html = View::make('canvastack::components.table.filter-modal', [
            'filters' => $filters,
            'activeFilters' => [],
            'tableName' => 'test_table',
            'activeFilterCount' => 0,
        ])->render();

        $this->assertStringContainsString('aria-label', $html);
        $this->assertStringContainsString('dusk=', $html);
    }

    /**
     * Test that filter modal handles multiple filters.
     */
    public function test_filter_modal_handles_multiple_filters(): void
    {
        $filters = [
            [
                'column' => 'period',
                'label' => 'Period',
                'type' => 'selectbox',
                'options' => [],
                'loading' => false,
                'relate' => false,
                'autoSubmit' => false,
            ],
            [
                'column' => 'region',
                'label' => 'Region',
                'type' => 'selectbox',
                'options' => [],
                'loading' => false,
                'relate' => false,
                'autoSubmit' => false,
            ],
            [
                'column' => 'name',
                'label' => 'Name',
                'type' => 'inputbox',
                'options' => [],
                'loading' => false,
                'relate' => false,
                'autoSubmit' => false,
            ],
        ];

        $html = View::make('canvastack::components.table.filter-modal', [
            'filters' => $filters,
            'activeFilters' => [],
            'tableName' => 'test_table',
            'activeFilterCount' => 0,
        ])->render();

        $this->assertStringContainsString('Period', $html);
        $this->assertStringContainsString('Region', $html);
        $this->assertStringContainsString('Name', $html);
    }

    /**
     * Test that filter modal includes cascading filter logic.
     */
    public function test_filter_modal_includes_cascading_filter_logic(): void
    {
        $filters = [
            [
                'column' => 'period',
                'label' => 'Period',
                'type' => 'selectbox',
                'options' => [],
                'loading' => false,
                'relate' => true,
                'autoSubmit' => false,
            ],
        ];

        $html = View::make('canvastack::components.table.filter-modal', [
            'filters' => $filters,
            'activeFilters' => [],
            'tableName' => 'test_table',
            'activeFilterCount' => 0,
        ])->render();

        $this->assertStringContainsString('handleFilterChange', $html);
        $this->assertStringContainsString('updateRelatedFilters', $html);
        $this->assertStringContainsString('getRelatedColumns', $html);
    }
}
