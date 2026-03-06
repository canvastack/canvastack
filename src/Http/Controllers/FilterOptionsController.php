<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Http\Controllers;

use Canvastack\Canvastack\Components\Table\Filter\FilterOptionsProvider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;

/**
 * FilterOptionsController - Handles AJAX requests for filter options.
 *
 * Provides endpoints for loading filter options dynamically based on
 * parent filter values (for cascading filters).
 *
 * @package Canvastack\Canvastack\Http\Controllers
 */
class FilterOptionsController extends Controller
{
    /**
     * Filter options provider instance.
     *
     * @var FilterOptionsProvider
     */
    protected FilterOptionsProvider $optionsProvider;

    /**
     * Constructor.
     *
     * @param FilterOptionsProvider $optionsProvider Options provider
     */
    public function __construct(FilterOptionsProvider $optionsProvider)
    {
        $this->optionsProvider = $optionsProvider;
    }

    /**
     * Get filter options for a specific column.
     *
     * @param Request $request HTTP request
     * @return JsonResponse
     */
    public function getOptions(Request $request): JsonResponse
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'table' => 'required|string',
            'column' => 'required|string',
            'filters' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $table = $request->input('table');
        $column = $request->input('column');
        $parentFilters = $request->input('filters', []);

        try {
            // Validate table name (prevent SQL injection)
            if (!$this->isValidTableName($table)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid table name',
                ], 400);
            }

            // Validate column name (prevent SQL injection)
            if (!$this->isValidColumnName($column)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid column name',
                ], 400);
            }

            // Get options
            $options = $this->optionsProvider->getOptions($table, $column, $parentFilters);

            return response()->json([
                'success' => true,
                'options' => $options,
                'count' => count($options),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load filter options',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get filter options with count for a specific column.
     *
     * @param Request $request HTTP request
     * @return JsonResponse
     */
    public function getOptionsWithCount(Request $request): JsonResponse
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'table' => 'required|string',
            'column' => 'required|string',
            'filters' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $table = $request->input('table');
        $column = $request->input('column');
        $parentFilters = $request->input('filters', []);

        try {
            // Validate table and column names
            if (!$this->isValidTableName($table) || !$this->isValidColumnName($column)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid table or column name',
                ], 400);
            }

            // Get options with count
            $options = $this->optionsProvider->getOptionsWithCount($table, $column, $parentFilters);

            return response()->json([
                'success' => true,
                'options' => $options,
                'count' => count($options),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load filter options',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Prefetch options for multiple columns.
     *
     * @param Request $request HTTP request
     * @return JsonResponse
     */
    public function prefetchOptions(Request $request): JsonResponse
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'table' => 'required|string',
            'columns' => 'required|array',
            'columns.*' => 'string',
            'filters' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $table = $request->input('table');
        $columns = $request->input('columns');
        $parentFilters = $request->input('filters', []);

        try {
            // Validate table name
            if (!$this->isValidTableName($table)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid table name',
                ], 400);
            }

            // Validate all column names
            foreach ($columns as $column) {
                if (!$this->isValidColumnName($column)) {
                    return response()->json([
                        'success' => false,
                        'message' => "Invalid column name: {$column}",
                    ], 400);
                }
            }

            // Prefetch options
            $options = $this->optionsProvider->prefetchOptions($table, $columns, $parentFilters);

            return response()->json([
                'success' => true,
                'options' => $options,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to prefetch filter options',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Clear filter options cache.
     *
     * @param Request $request HTTP request
     * @return JsonResponse
     */
    public function clearCache(Request $request): JsonResponse
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'table' => 'sometimes|string',
            'column' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $table = $request->input('table');
            $column = $request->input('column');

            if ($table && $column) {
                // Clear specific cache
                $this->optionsProvider->clearCache($table, $column);
                $message = "Cache cleared for {$table}.{$column}";
            } else {
                // Clear all cache
                $this->optionsProvider->clearAllCache();
                $message = 'All filter options cache cleared';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear cache',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Validate table name to prevent SQL injection.
     *
     * @param string $tableName Table name
     * @return bool
     */
    protected function isValidTableName(string $tableName): bool
    {
        // Allow alphanumeric, underscore, and dot (for schema.table)
        return preg_match('/^[a-zA-Z0-9_.]+$/', $tableName) === 1;
    }

    /**
     * Validate column name to prevent SQL injection.
     *
     * @param string $columnName Column name
     * @return bool
     */
    protected function isValidColumnName(string $columnName): bool
    {
        // Allow alphanumeric, underscore, and dot (for table.column)
        return preg_match('/^[a-zA-Z0-9_.]+$/', $columnName) === 1;
    }
}
