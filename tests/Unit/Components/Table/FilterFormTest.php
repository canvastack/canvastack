<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Table;

use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Support\Facades\View;

/**
 * Test for Filter Form Component.
 */
class FilterFormTest extends TestCase
{
    /**
     * Test that filter form renders with selectbox.
     *
     * @return void
     */
    public function test_filter_form_renders_with_selectbox(): void
    {
        // Arrange
        $filters = [
            [
                'column' => 'status',
                'type' => 'selectbox',
                'label' => 'Status',
                'options' => [
                    ['value' => 'active', 'label' => 'Active'],
                    ['value' => 'inactive', 'label' => 'Inactive'],
                ],
                'loading' => false,
                'relate' => false,
                'autoSubmit' => false,
            ],
        ];
        
        // Act
        $html = View::make('canvastack::components.table.filter-modal', [
            'filters' => $filters,
            'activeFilters' => [],
            'tableName' => 'users',
            'activeFilterCount' => 0,
        ])->render();
        
        // Assert
        $this->assertStringContainsString('filter.type === \'selectbox\'', $html);
        $this->assertStringContainsString('select select-bordered', $html);
        $this->assertStringContainsString('Status', $html);
    }

    /**
     * Test that filter form renders with inputbox.
     *
     * @return void
     */
    public function test_filter_form_renders_with_inputbox(): void
    {
        // Arrange
        $filters = [
            [
                'column' => 'name',
                'type' => 'inputbox',
                'label' => 'Name',
                'loading' => false,
                'relate' => false,
                'autoSubmit' => false,
            ],
        ];
        
        // Act
        $html = View::make('canvastack::components.table.filter-modal', [
            'filters' => $filters,
            'activeFilters' => [],
            'tableName' => 'users',
            'activeFilterCount' => 0,
        ])->render();
        
        // Assert
        $this->assertStringContainsString('filter.type === \'inputbox\'', $html);
        $this->assertStringContainsString('type="text"', $html);
        $this->assertStringContainsString('input input-bordered', $html);
    }

    /**
     * Test that filter form renders with datebox.
     *
     * @return void
     */
    public function test_filter_form_renders_with_datebox(): void
    {
        // Arrange
        $filters = [
            [
                'column' => 'created_at',
                'type' => 'datebox',
                'label' => 'Created Date',
                'loading' => false,
                'relate' => false,
                'autoSubmit' => false,
            ],
        ];
        
        // Act
        $html = View::make('canvastack::components.table.filter-modal', [
            'filters' => $filters,
            'activeFilters' => [],
            'tableName' => 'users',
            'activeFilterCount' => 0,
        ])->render();
        
        // Assert
        $this->assertStringContainsString('filter.type === \'datebox\'', $html);
        $this->assertStringContainsString('type="date"', $html);
        $this->assertStringContainsString('input input-bordered', $html);
    }

    /**
     * Test that filter form renders loading states.
     *
     * @return void
     */
    public function test_filter_form_renders_loading_states(): void
    {
        // Arrange
        $filters = [
            [
                'column' => 'status',
                'type' => 'selectbox',
                'label' => 'Status',
                'options' => [],
                'loading' => true,
                'relate' => false,
                'autoSubmit' => false,
            ],
        ];
        
        // Act
        $html = View::make('canvastack::components.table.filter-modal', [
            'filters' => $filters,
            'activeFilters' => [],
            'tableName' => 'users',
            'activeFilterCount' => 0,
        ])->render();
        
        // Assert
        $this->assertStringContainsString('loading loading-spinner', $html);
        $this->assertStringContainsString('filter.loading', $html);
    }

    /**
     * Test that filter form renders multiple filters.
     *
     * @return void
     */
    public function test_filter_form_renders_multiple_filters(): void
    {
        // Arrange
        $filters = [
            [
                'column' => 'status',
                'type' => 'selectbox',
                'label' => 'Status',
                'options' => [
                    ['value' => 'active', 'label' => 'Active'],
                ],
                'loading' => false,
                'relate' => false,
                'autoSubmit' => false,
            ],
            [
                'column' => 'name',
                'type' => 'inputbox',
                'label' => 'Name',
                'loading' => false,
                'relate' => false,
                'autoSubmit' => false,
            ],
            [
                'column' => 'created_at',
                'type' => 'datebox',
                'label' => 'Created Date',
                'loading' => false,
                'relate' => false,
                'autoSubmit' => false,
            ],
        ];
        
        // Act
        $html = View::make('canvastack::components.table.filter-modal', [
            'filters' => $filters,
            'activeFilters' => [],
            'tableName' => 'users',
            'activeFilterCount' => 0,
        ])->render();
        
        // Assert
        $this->assertStringContainsString('Status', $html);
        $this->assertStringContainsString('Name', $html);
        $this->assertStringContainsString('Created Date', $html);
    }

    /**
     * Test that filter form has apply button.
     *
     * @return void
     */
    public function test_filter_form_has_apply_button(): void
    {
        // Arrange
        $filters = [
            [
                'column' => 'status',
                'type' => 'selectbox',
                'label' => 'Status',
                'options' => [],
                'loading' => false,
                'relate' => false,
                'autoSubmit' => false,
            ],
        ];
        
        // Act
        $html = View::make('canvastack::components.table.filter-modal', [
            'filters' => $filters,
            'activeFilters' => [],
            'tableName' => 'users',
            'activeFilterCount' => 0,
        ])->render();
        
        // Assert
        $this->assertStringContainsString('type="submit"', $html);
        $this->assertStringContainsString('btn btn-primary', $html);
        $this->assertStringContainsString('dusk="apply-filter"', $html);
    }

    /**
     * Test that filter form has clear button.
     *
     * @return void
     */
    public function test_filter_form_has_clear_button(): void
    {
        // Arrange
        $filters = [
            [
                'column' => 'status',
                'type' => 'selectbox',
                'label' => 'Status',
                'options' => [],
                'loading' => false,
                'relate' => false,
                'autoSubmit' => false,
            ],
        ];
        
        // Act
        $html = View::make('canvastack::components.table.filter-modal', [
            'filters' => $filters,
            'activeFilters' => [],
            'tableName' => 'users',
            'activeFilterCount' => 0,
        ])->render();
        
        // Assert
        $this->assertStringContainsString('@click="clearFilters"', $html);
        $this->assertStringContainsString('btn btn-ghost', $html);
    }

    /**
     * Test that filter form renders with active filters.
     *
     * @return void
     */
    public function test_filter_form_renders_with_active_filters(): void
    {
        // Arrange
        $filters = [
            [
                'column' => 'status',
                'type' => 'selectbox',
                'label' => 'Status',
                'options' => [
                    ['value' => 'active', 'label' => 'Active'],
                    ['value' => 'inactive', 'label' => 'Inactive'],
                ],
                'loading' => false,
                'relate' => false,
                'autoSubmit' => false,
            ],
        ];
        
        $activeFilters = [
            'status' => 'active',
        ];
        
        // Act
        $html = View::make('canvastack::components.table.filter-modal', [
            'filters' => $filters,
            'activeFilters' => $activeFilters,
            'tableName' => 'users',
            'activeFilterCount' => 1,
        ])->render();
        
        // Assert
        $this->assertStringContainsString('filterValues: ' . json_encode($activeFilters), $html);
        $this->assertStringContainsString('activeFilterCount: 1', $html);
    }

    /**
     * Test that filter form has proper accessibility attributes.
     *
     * @return void
     */
    public function test_filter_form_has_accessibility_attributes(): void
    {
        // Arrange
        $filters = [
            [
                'column' => 'status',
                'type' => 'selectbox',
                'label' => 'Status',
                'options' => [],
                'loading' => false,
                'relate' => false,
                'autoSubmit' => false,
            ],
        ];
        
        // Act
        $html = View::make('canvastack::components.table.filter-modal', [
            'filters' => $filters,
            'activeFilters' => [],
            'tableName' => 'users',
            'activeFilterCount' => 0,
        ])->render();
        
        // Assert
        $this->assertStringContainsString(':for="\'filter_\' + filter.column"', $html);
        $this->assertStringContainsString(':id="\'filter_\' + filter.column"', $html);
        $this->assertStringContainsString('aria-label', $html);
    }

    /**
     * Test that filter form has dusk attributes for testing.
     *
     * @return void
     */
    public function test_filter_form_has_dusk_attributes(): void
    {
        // Arrange
        $filters = [
            [
                'column' => 'status',
                'type' => 'selectbox',
                'label' => 'Status',
                'options' => [],
                'loading' => false,
                'relate' => false,
                'autoSubmit' => false,
            ],
        ];
        
        // Act
        $html = View::make('canvastack::components.table.filter-modal', [
            'filters' => $filters,
            'activeFilters' => [],
            'tableName' => 'users',
            'activeFilterCount' => 0,
        ])->render();
        
        // Assert
        $this->assertStringContainsString('dusk="filter-button"', $html);
        $this->assertStringContainsString('dusk="filter-modal"', $html);
        $this->assertStringContainsString('dusk="apply-filter"', $html);
        $this->assertStringContainsString(':dusk="\'filter-\' + filter.column"', $html);
    }

    /**
     * Test that filter form handles form submission.
     *
     * @return void
     */
    public function test_filter_form_handles_form_submission(): void
    {
        // Arrange
        $filters = [
            [
                'column' => 'status',
                'type' => 'selectbox',
                'label' => 'Status',
                'options' => [],
                'loading' => false,
                'relate' => false,
                'autoSubmit' => false,
            ],
        ];
        
        // Act
        $html = View::make('canvastack::components.table.filter-modal', [
            'filters' => $filters,
            'activeFilters' => [],
            'tableName' => 'users',
            'activeFilterCount' => 0,
        ])->render();
        
        // Assert
        $this->assertStringContainsString('@submit.prevent="applyFilters"', $html);
        $this->assertStringContainsString('async applyFilters()', $html);
    }

    /**
     * Test that filter form shows applying state.
     *
     * @return void
     */
    public function test_filter_form_shows_applying_state(): void
    {
        // Arrange
        $filters = [
            [
                'column' => 'status',
                'type' => 'selectbox',
                'label' => 'Status',
                'options' => [],
                'loading' => false,
                'relate' => false,
                'autoSubmit' => false,
            ],
        ];
        
        // Act
        $html = View::make('canvastack::components.table.filter-modal', [
            'filters' => $filters,
            'activeFilters' => [],
            'tableName' => 'users',
            'activeFilterCount' => 0,
        ])->render();
        
        // Assert
        $this->assertStringContainsString('isApplying: false', $html);
        $this->assertStringContainsString(':disabled="isApplying"', $html);
        $this->assertStringContainsString('x-show="isApplying"', $html);
    }
}
