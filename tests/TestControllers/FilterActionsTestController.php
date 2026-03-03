<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\TestControllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Test controller for filter actions functionality.
 */
class FilterActionsTestController
{
    /**
     * Show filter modal test page.
     */
    public function filterModal(): View
    {
        $filters = [
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
            [
                'column' => 'role',
                'type' => 'selectbox',
                'label' => 'Role',
                'options' => [
                    ['value' => 'admin', 'label' => 'Admin'],
                    ['value' => 'user', 'label' => 'User'],
                ],
                'relate' => false,
                'autoSubmit' => false,
                'loading' => false,
            ],
            [
                'column' => 'name',
                'type' => 'inputbox',
                'label' => 'Name',
                'options' => [],
                'relate' => false,
                'autoSubmit' => false,
                'loading' => false,
            ],
        ];

        return view('canvastack::test.filter-modal', [
            'filters' => $filters,
            'activeFilters' => [],
            'tableName' => 'users',
            'activeFilterCount' => 0,
        ]);
    }

    /**
     * Show filter modal with auto-submit test page.
     */
    public function filterModalAutoSubmit(): View
    {
        $filters = [
            [
                'column' => 'status',
                'type' => 'selectbox',
                'label' => 'Status',
                'options' => [
                    ['value' => 'active', 'label' => 'Active'],
                    ['value' => 'inactive', 'label' => 'Inactive'],
                ],
                'relate' => false,
                'autoSubmit' => true, // Auto-submit enabled
                'loading' => false,
            ],
        ];

        return view('canvastack::test.filter-modal', [
            'filters' => $filters,
            'activeFilters' => [],
            'tableName' => 'users',
            'activeFilterCount' => 0,
        ]);
    }

    /**
     * Get filter options (mock endpoint).
     */
    public function getFilterOptions(Request $request): JsonResponse
    {
        $table = $request->input('table');
        $column = $request->input('column');
        $parentFilters = $request->input('parentFilters', []);

        // Mock data based on column
        $options = match ($column) {
            'status' => [
                ['value' => 'active', 'label' => 'Active'],
                ['value' => 'inactive', 'label' => 'Inactive'],
            ],
            'role' => [
                ['value' => 'admin', 'label' => 'Admin'],
                ['value' => 'user', 'label' => 'User'],
            ],
            default => [],
        };

        return response()->json([
            'options' => $options,
        ]);
    }

    /**
     * Save filters (mock endpoint).
     */
    public function saveFilters(Request $request): JsonResponse
    {
        $table = $request->input('table');
        $filters = $request->input('filters', []);

        // Mock saving to session
        session(['test_filters' => $filters]);

        return response()->json([
            'success' => true,
            'message' => 'Filters saved successfully',
        ]);
    }
}