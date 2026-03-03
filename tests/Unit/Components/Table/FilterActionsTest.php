<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Table;

use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Support\Facades\View;

/**
 * Test for Filter Actions functionality.
 * 
 * Tests the apply filter, clear filter, auto-submit, and filter state display features.
 */
class FilterActionsTest extends TestCase
{
    /**
     * Test that apply filter button is rendered.
     */
    public function test_apply_filter_button_is_rendered(): void
    {
        $html = View::make('canvastack::components.table.filter-modal', [
            'filters' => [
                [
                    'column' => 'status',
                    'type' => 'selectbox',
                    'label' => 'Status',
                    'options' => [
                        ['value' => 'active', 'label' => 'Active'],
                        ['value' => 'inactive', 'label' => 'Inactive'],
                    ],
                    'relate' => false,
                    'autoSubmit' => false,
                    'loading' => false,
                ],
            ],
            'activeFilters' => [],
            'tableName' => 'users',
            'activeFilterCount' => 0,
        ])->render();
        
        $this->assertStringContainsString('type="submit"', $html);
        $this->assertStringContainsString('dusk="apply-filter"', $html);
        $this->assertStringContainsString('Apply Filter', $html);
    }
    
    /**
     * Test that apply filter button shows loading state.
     */
    public function test_apply_filter_button_shows_loading_state(): void
    {
        $html = View::make('canvastack::components.table.filter-modal', [
            'filters' => [],
            'activeFilters' => [],
            'tableName' => 'users',
            'activeFilterCount' => 0,
        ])->render();
        
        $this->assertStringContainsString(':disabled="isApplying"', $html);
        $this->assertStringContainsString('x-show="isApplying"', $html);
        $this->assertStringContainsString('loading loading-spinner', $html);
        $this->assertStringContainsString('Applying...', $html);
    }
    
    /**
     * Test that clear filter button is rendered.
     */
    public function test_clear_filter_button_is_rendered(): void
    {
        $html = View::make('canvastack::components.table.filter-modal', [
            'filters' => [],
            'activeFilters' => [],
            'tableName' => 'users',
            'activeFilterCount' => 0,
        ])->render();
        
        $this->assertStringContainsString('type="button"', $html);
        $this->assertStringContainsString('@click="clearFilters"', $html);
        $this->assertStringContainsString('Clear', $html);
    }
    
    /**
     * Test that clear filter button is disabled during apply.
     */
    public function test_clear_filter_button_is_disabled_during_apply(): void
    {
        $html = View::make('canvastack::components.table.filter-modal', [
            'filters' => [],
            'activeFilters' => [],
            'tableName' => 'users',
            'activeFilterCount' => 0,
        ])->render();
        
        $this->assertStringContainsString(':disabled="isApplying"', $html);
    }
    
    /**
     * Test that applyFilters function is defined.
     */
    public function test_apply_filters_function_is_defined(): void
    {
        $html = View::make('canvastack::components.table.filter-modal', [
            'filters' => [],
            'activeFilters' => [],
            'tableName' => 'users',
            'activeFilterCount' => 0,
        ])->render();
        
        $this->assertStringContainsString('async applyFilters()', $html);
        $this->assertStringContainsString('this.isApplying = true', $html);
        $this->assertStringContainsString('/datatable/save-filters', $html);
    }
    
    /**
     * Test that applyFilters saves to session.
     */
    public function test_apply_filters_saves_to_session(): void
    {
        $html = View::make('canvastack::components.table.filter-modal', [
            'filters' => [],
            'activeFilters' => [],
            'tableName' => 'users',
            'activeFilterCount' => 0,
        ])->render();
        
        $this->assertStringContainsString("table: 'users'", $html);
        $this->assertStringContainsString('filters: this.filterValues', $html);
    }
    
    /**
     * Test that applyFilters reloads DataTable.
     */
    public function test_apply_filters_reloads_datatable(): void
    {
        $html = View::make('canvastack::components.table.filter-modal', [
            'filters' => [],
            'activeFilters' => [],
            'tableName' => 'users',
            'activeFilterCount' => 0,
        ])->render();
        
        $this->assertStringContainsString('window.dataTable.ajax.reload()', $html);
    }
    
    /**
     * Test that applyFilters updates active count.
     */
    public function test_apply_filters_updates_active_count(): void
    {
        $html = View::make('canvastack::components.table.filter-modal', [
            'filters' => [],
            'activeFilters' => [],
            'tableName' => 'users',
            'activeFilterCount' => 0,
        ])->render();
        
        $this->assertStringContainsString('this.updateActiveCount()', $html);
    }
    
    /**
     * Test that applyFilters closes modal.
     */
    public function test_apply_filters_closes_modal(): void
    {
        $html = View::make('canvastack::components.table.filter-modal', [
            'filters' => [],
            'activeFilters' => [],
            'tableName' => 'users',
            'activeFilterCount' => 0,
        ])->render();
        
        $this->assertStringContainsString('this.open = false', $html);
    }
    
    /**
     * Test that applyFilters shows success notification.
     */
    public function test_apply_filters_shows_success_notification(): void
    {
        $html = View::make('canvastack::components.table.filter-modal', [
            'filters' => [],
            'activeFilters' => [],
            'tableName' => 'users',
            'activeFilterCount' => 0,
        ])->render();
        
        $this->assertStringContainsString("window.showNotification('success', 'Filters applied successfully')", $html);
    }
    
    /**
     * Test that applyFilters handles errors.
     */
    public function test_apply_filters_handles_errors(): void
    {
        $html = View::make('canvastack::components.table.filter-modal', [
            'filters' => [],
            'activeFilters' => [],
            'tableName' => 'users',
            'activeFilterCount' => 0,
        ])->render();
        
        $this->assertStringContainsString('catch (error)', $html);
        $this->assertStringContainsString("window.showNotification('error', 'Error applying filters')", $html);
    }
    
    /**
     * Test that applyFilters resets loading state.
     */
    public function test_apply_filters_resets_loading_state(): void
    {
        $html = View::make('canvastack::components.table.filter-modal', [
            'filters' => [],
            'activeFilters' => [],
            'tableName' => 'users',
            'activeFilterCount' => 0,
        ])->render();
        
        $this->assertStringContainsString('finally {', $html);
        $this->assertStringContainsString('this.isApplying = false', $html);
    }
    
    /**
     * Test that clearFilters function is defined.
     */
    public function test_clear_filters_function_is_defined(): void
    {
        $html = View::make('canvastack::components.table.filter-modal', [
            'filters' => [],
            'activeFilters' => [],
            'tableName' => 'users',
            'activeFilterCount' => 0,
        ])->render();
        
        $this->assertStringContainsString('async clearFilters()', $html);
        $this->assertStringContainsString('this.filterValues = {}', $html);
    }
    
    /**
     * Test that clearFilters calls applyFilters.
     */
    public function test_clear_filters_calls_apply_filters(): void
    {
        $html = View::make('canvastack::components.table.filter-modal', [
            'filters' => [],
            'activeFilters' => [],
            'tableName' => 'users',
            'activeFilterCount' => 0,
        ])->render();
        
        $this->assertStringContainsString('await this.applyFilters()', $html);
    }
    
    /**
     * Test that auto-submit logic is implemented.
     */
    public function test_auto_submit_logic_is_implemented(): void
    {
        $html = View::make('canvastack::components.table.filter-modal', [
            'filters' => [
                [
                    'column' => 'status',
                    'type' => 'selectbox',
                    'label' => 'Status',
                    'options' => [],
                    'relate' => false,
                    'autoSubmit' => true,
                    'loading' => false,
                ],
            ],
            'activeFilters' => [],
            'tableName' => 'users',
            'activeFilterCount' => 0,
        ])->render();
        
        $this->assertStringContainsString('if (filter.autoSubmit)', $html);
        $this->assertStringContainsString('await this.applyFilters()', $html);
    }
    
    /**
     * Test that updateActiveCount function is defined.
     */
    public function test_update_active_count_function_is_defined(): void
    {
        $html = View::make('canvastack::components.table.filter-modal', [
            'filters' => [],
            'activeFilters' => [],
            'tableName' => 'users',
            'activeFilterCount' => 0,
        ])->render();
        
        $this->assertStringContainsString('updateActiveCount()', $html);
        $this->assertStringContainsString('this.activeFilterCount = Object.values(this.filterValues)', $html);
    }
    
    /**
     * Test that active filter count excludes empty values.
     */
    public function test_active_filter_count_excludes_empty_values(): void
    {
        $html = View::make('canvastack::components.table.filter-modal', [
            'filters' => [],
            'activeFilters' => [],
            'tableName' => 'users',
            'activeFilterCount' => 0,
        ])->render();
        
        $this->assertStringContainsString(".filter(v => v !== '' && v !== null && v !== undefined)", $html);
    }
    
    /**
     * Test that filter state is displayed with badge.
     */
    public function test_filter_state_is_displayed_with_badge(): void
    {
        $html = View::make('canvastack::components.table.filter-modal', [
            'filters' => [],
            'activeFilters' => ['status' => 'active'],
            'tableName' => 'users',
            'activeFilterCount' => 1,
        ])->render();
        
        $this->assertStringContainsString('x-show="activeFilterCount > 0"', $html);
        $this->assertStringContainsString('badge badge-sm badge-error', $html);
        $this->assertStringContainsString('x-text="activeFilterCount"', $html);
    }
    
    /**
     * Test that filter state badge has transitions.
     */
    public function test_filter_state_badge_has_transitions(): void
    {
        $html = View::make('canvastack::components.table.filter-modal', [
            'filters' => [],
            'activeFilters' => [],
            'tableName' => 'users',
            'activeFilterCount' => 0,
        ])->render();
        
        $this->assertStringContainsString('x-transition:enter="transition ease-out duration-200"', $html);
        $this->assertStringContainsString('x-transition:enter-start="opacity-0 scale-75"', $html);
        $this->assertStringContainsString('x-transition:enter-end="opacity-100 scale-100"', $html);
    }
    
    /**
     * Test that form prevents default submit.
     */
    public function test_form_prevents_default_submit(): void
    {
        $html = View::make('canvastack::components.table.filter-modal', [
            'filters' => [],
            'activeFilters' => [],
            'tableName' => 'users',
            'activeFilterCount' => 0,
        ])->render();
        
        $this->assertStringContainsString('@submit.prevent="applyFilters"', $html);
    }
}

