<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Http\Controllers;

use Canvastack\Canvastack\Components\Table\FilterOptionsProvider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * DataTableController - Handles DataTable AJAX requests.
 *
 * Provides endpoints for:
 * - Filter options (cascading filters)
 * - Save filters to session
 * - Save display limit to session
 */
class DataTableController extends Controller
{
    protected FilterOptionsProvider $filterOptionsProvider;

    public function __construct(FilterOptionsProvider $filterOptionsProvider)
    {
        $this->filterOptionsProvider = $filterOptionsProvider;
    }

    /**
     * Get data for TanStack Table server-side processing.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getTanStackData(Request $request): JsonResponse
    {
        try {
            // Get TanStack Table parameters
            $page = (int) $request->input('page', 1);
            $pageSize = (int) $request->input('pageSize', 10);
            $sorting = $request->input('sorting', []);
            $globalFilter = $request->input('globalFilter', '');
            $columnFilters = $request->input('columnFilters', []);
            $filters = $request->input('filters', []); // Custom filters from filter modal
            
            // Log incoming request for debugging
            \Log::info('TanStack Table getData request', [
                'page' => $page,
                'pageSize' => $pageSize,
                'globalFilter' => $globalFilter,
                'columnFilters' => $columnFilters,
                'filters' => $filters,
            ]);
            
            // Get table/model information
            $tableName = $request->input('tableName', 'users');
            $modelClass = $request->input('modelClass');
            
            // Build query - prefer model over raw table
            if ($modelClass && class_exists($modelClass)) {
                $query = $modelClass::query();
            } else {
                $query = DB::table($tableName);
            }
            
            // Get total count before filtering
            if ($modelClass && class_exists($modelClass)) {
                $totalRecords = $modelClass::count();
            } else {
                $totalRecords = DB::table($tableName)->count();
            }
            
            // Apply global filter
            if ($globalFilter !== '') {
                $searchableColumns = $request->input('searchableColumns', []);
                if (!empty($searchableColumns)) {
                    $query->where(function ($q) use ($searchableColumns, $globalFilter) {
                        foreach ($searchableColumns as $column) {
                            $q->orWhere($column, 'like', "%{$globalFilter}%");
                        }
                    });
                }
            }
            
            // Apply column filters
            foreach ($columnFilters as $filter) {
                if (isset($filter['id']) && isset($filter['value'])) {
                    $column = $filter['id'];
                    $value = $filter['value'];
                    $query->where($column, $value);
                }
            }
            
            // Apply custom filters from filter modal
            if (!empty($filters) && is_array($filters)) {
                \Log::info('Applying custom filters', ['filters' => $filters]);
                
                foreach ($filters as $column => $value) {
                    if (empty($value)) {
                        continue;
                    }
                    
                    // Check if this is a date column
                    $isDateColumn = (substr($column, -3) === '_at' || $column === 'date');
                    
                    if ($isDateColumn && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                        // Date filter - use whereDate for exact date match
                        $query->whereDate($column, $value);
                        \Log::info('Applied whereDate filter', ['column' => $column, 'value' => $value]);
                    } else {
                        // Exact match filter
                        $query->where($column, $value);
                        \Log::info('Applied where filter', ['column' => $column, 'value' => $value]);
                    }
                }
            }
            
            // Get filtered count
            $filteredRecords = (clone $query)->count();
            
            // Apply sorting
            if (!empty($sorting) && isset($sorting[0])) {
                $sortColumn = $sorting[0]['id'] ?? 'id';
                $sortDirection = ($sorting[0]['desc'] ?? false) ? 'desc' : 'asc';
                $query->orderBy($sortColumn, $sortDirection);
            }
            
            // Apply pagination
            $offset = ($page - 1) * $pageSize;
            $query->skip($offset)->take($pageSize);
            
            // Get data
            $data = $query->get()->toArray();
            
            // Add actions to each row (simple implementation)
            // In production, actions should be configured via TableBuilder
            foreach ($data as &$row) {
                $row['_actions'] = $this->buildSimpleActions($row);
            }
            
            // Return TanStack Table response
            return response()->json([
                'data' => $data,
                'meta' => [
                    'page' => $page,
                    'pageSize' => $pageSize,
                    'totalRows' => $filteredRecords, // Use filtered count for pagination
                    'totalPages' => ceil($filteredRecords / $pageSize), // Calculate pages from filtered count
                    'filteredRows' => $filteredRecords,
                    'totalRecordsBeforeFilter' => $totalRecords, // Keep original total for reference
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('TanStack Table getData error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
            ]);
            
            return response()->json([
                'data' => [],
                'meta' => [
                    'page' => 1,
                    'pageSize' => 10,
                    'totalRows' => 0,
                    'totalPages' => 0,
                    'filteredRows' => 0,
                ],
                'error' => 'Error loading data: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Build simple actions for a row.
     * 
     * This is a simplified implementation. In production, actions should be
     * configured via TableBuilder and passed through the request.
     *
     * @param array $row
     * @return array
     */
    protected function buildSimpleActions(array $row): array
    {
        $id = $row['id'] ?? null;
        
        if (!$id) {
            return [];
        }
        
        // Build default actions (view, edit, delete)
        return [
            [
                'name' => 'view',
                'label' => 'View',
                'icon' => 'eye',
                'url' => '#',
                'method' => 'GET',
                'class' => 'btn-sm btn-info',
            ],
            [
                'name' => 'edit',
                'label' => 'Edit',
                'icon' => 'edit',
                'url' => route('test.form-edit', $id),
                'method' => 'GET',
                'class' => 'btn-sm btn-warning',
            ],
            [
                'name' => 'delete',
                'label' => 'Delete',
                'icon' => 'trash',
                'url' => '#',
                'method' => 'DELETE',
                'confirm' => 'Are you sure you want to delete this item?',
                'class' => 'btn-sm btn-error',
            ],
        ];
    }

    /**
     * Get data for DataTables server-side processing.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getData(Request $request): JsonResponse
    {
        try {
            // Get DataTables parameters
            $draw = (int) $request->input('draw', 1);
            $start = (int) $request->input('start', 0);
            $length = (int) $request->input('length', 10);
            $searchValue = $request->input('search.value', '');
            $orderColumn = (int) $request->input('order.0.column', 0);
            $orderDir = $request->input('order.0.dir', 'asc');
            
            // Get filter values
            $filters = $request->input('filters', []);
            
            // Get table/model information
            $tableName = $request->input('tableName', 'users');
            $modelClass = $request->input('modelClass');
            
            // Build query - prefer model over raw table
            if ($modelClass && class_exists($modelClass)) {
                $query = $modelClass::query();
            } else {
                $query = DB::table($tableName);
            }
            
            // Apply filters
            foreach ($filters as $column => $value) {
                if ($value !== '' && $value !== null) {
                    // Check if this is a date/datetime column by checking if value is a valid date
                    if ($this->isDateValue($value)) {
                        // For date columns, use date range (start of day to end of day)
                        // This works across MySQL, PostgreSQL, SQLite, SQL Server
                        $query->whereDate($column, '=', $value);
                    } else {
                        // For non-date columns, use exact match
                        $query->where($column, $value);
                    }
                }
            }
            
            // Apply search
            if ($searchValue !== '') {
                // Get columns from request
                $columns = $request->input('columns', []);
                $searchableColumns = array_filter($columns, function ($col) {
                    return isset($col['searchable']) && $col['searchable'] === 'true';
                });
                
                if (!empty($searchableColumns)) {
                    $query->where(function ($q) use ($searchableColumns, $searchValue) {
                        foreach ($searchableColumns as $col) {
                            if (isset($col['data']) && $col['data'] !== null) {
                                $q->orWhere($col['data'], 'like', "%{$searchValue}%");
                            }
                        }
                    });
                }
            }
            
            // Get total count before filtering
            if ($modelClass && class_exists($modelClass)) {
                $totalRecords = $modelClass::count();
            } else {
                $totalRecords = DB::table($tableName)->count();
            }
            
            // Get filtered count (clone query to avoid affecting main query)
            $filteredRecords = (clone $query)->count();
            
            // Apply ordering
            $columns = $request->input('columns', []);
            if (isset($columns[$orderColumn]['data']) && $columns[$orderColumn]['data'] !== null) {
                $orderColumnName = $columns[$orderColumn]['data'];
                $query->orderBy($orderColumnName, $orderDir);
            }
            
            // Apply pagination
            if ($length !== -1) {
                $query->skip($start)->take($length);
            }
            
            // Get data
            $data = $query->get()->toArray();
            
            // Return DataTables response
            return response()->json([
                'draw' => $draw,
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            \Log::error('DataTables getData error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
            ]);
            
            return response()->json([
                'draw' => (int) $request->input('draw', 1),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => 'Error loading data: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get filter options for a specific column.
     *
     * Supports cascading filters by accepting parent filter values.
     * For date columns, returns min/max range instead of options list.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getFilterOptions(Request $request): JsonResponse
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'table' => 'required|string|max:255',
            'column' => 'required|string|max:255',
            'parentFilters' => 'sometimes|array',
            'type' => 'sometimes|string|in:selectbox,inputbox,datebox,daterangebox',
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
        $parentFilters = $request->input('parentFilters', []);
        $type = $request->input('type', 'selectbox');

        try {
            // For date columns, return min/max range
            if ($type === 'datebox' || $type === 'daterangebox') {
                $range = $this->getDateRange($table, $column, $parentFilters);
                
                return response()->json([
                    'success' => true,
                    'type' => 'date_range',
                    'min' => $range['min'],
                    'max' => $range['max'],
                    'count' => $range['count'],
                    'availableDates' => $range['availableDates'], // ✅ ADD THIS LINE
                ]);
            }
            
            // For other types, return options list
            $options = $this->filterOptionsProvider->getOptions($table, $column, $parentFilters);

            return response()->json([
                'success' => true,
                'type' => 'options',
                'options' => $options,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading filter options: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get date range (min/max) for a date column with parent filters.
     * Also returns list of available dates for custom date picker.
     *
     * @param string $table
     * @param string $column
     * @param array $parentFilters
     * @return array
     */
    protected function getDateRange(string $table, string $column, array $parentFilters = []): array
    {
        $query = DB::table($table)
            ->whereNotNull($column);

        // Apply parent filters
        foreach ($parentFilters as $col => $value) {
            if ($value !== '' && $value !== null) {
                $query->where($col, $value);
            }
        }

        // Get min and max dates
        $result = $query->selectRaw("MIN({$column}) as min_date, MAX({$column}) as max_date, COUNT(*) as count")
            ->first();

        // Get list of distinct dates (for custom date picker)
        $distinctDates = DB::table($table)
            ->whereNotNull($column);
        
        // Apply same parent filters
        foreach ($parentFilters as $col => $value) {
            if ($value !== '' && $value !== null) {
                $distinctDates->where($col, $value);
            }
        }
        
        // Get distinct dates, limit to 1000 to prevent memory issues
        $availableDates = $distinctDates
            ->select(DB::raw("DATE({$column}) as date"))
            ->distinct()
            ->orderBy('date')
            ->limit(1000)
            ->pluck('date')
            ->map(function($date) {
                return date('Y-m-d', strtotime($date));
            })
            ->toArray();

        return [
            'min' => $result->min_date ? date('Y-m-d', strtotime($result->min_date)) : null,
            'max' => $result->max_date ? date('Y-m-d', strtotime($result->max_date)) : null,
            'count' => $result->count ?? 0,
            'availableDates' => $availableDates, // List of valid dates
        ];
    }

    /**
     * Save filter values to session.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function saveFilters(Request $request): JsonResponse
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'table' => 'required|string|max:255',
            'filters' => 'nullable|array', // Changed from 'required' to 'nullable' to allow empty filters
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $table = $request->input('table');
        $filters = $request->input('filters', []); // Default to empty array if not provided

        try {
            // Save to session
            $sessionKey = "datatable_filters_{$table}";
            session([$sessionKey => $filters]);

            return response()->json([
                'success' => true,
                'message' => 'Filters saved successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error saving filters: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get filter values from session.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getFilters(Request $request): JsonResponse
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'table_id' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $tableId = $request->input('table_id');

        try {
            // Try both session keys for backward compatibility
            // First try with table_id (new format)
            $sessionKey = "datatable_filters_{$tableId}";
            $filters = session($sessionKey, null);
            
            // If not found, try with just the table ID without prefix
            if (empty($filters)) {
                $filters = session($tableId, []);
            }

            \Log::info('Get filters from session', [
                'table_id' => $tableId,
                'session_key' => $sessionKey,
                'filters' => $filters,
            ]);

            return response()->json([
                'success' => true,
                'filters' => $filters ?? [],
            ]);
        } catch (\Exception $e) {
            \Log::error('Error getting filters', [
                'error' => $e->getMessage(),
                'table_id' => $tableId,
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error getting filters: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Save display limit to session.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function saveDisplayLimit(Request $request): JsonResponse
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'table' => 'required|string|max:255',
            'limit' => 'required|integer|min:-1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $table = $request->input('table');
        $limit = $request->input('limit');

        try {
            // Save to session
            $sessionKey = "datatable_limit_{$table}";
            session([$sessionKey => $limit]);

            return response()->json([
                'success' => true,
                'message' => 'Display limit saved successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error saving display limit: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check if a value is a valid date string.
     *
     * @param mixed $value
     * @return bool
     */
    protected function isDateValue($value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        // Check if value matches common date formats
        // Y-m-d, Y/m/d, d-m-Y, d/m/Y, etc.
        $datePatterns = [
            '/^\d{4}-\d{2}-\d{2}$/',           // 2026-03-01
            '/^\d{4}\/\d{2}\/\d{2}$/',         // 2026/03/01
            '/^\d{2}-\d{2}-\d{4}$/',           // 01-03-2026
            '/^\d{2}\/\d{2}\/\d{4}$/',         // 01/03/2026
            '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', // 2026-03-01 10:30:00
        ];

        foreach ($datePatterns as $pattern) {
            if (preg_match($pattern, $value)) {
                // Verify it's actually a valid date
                $timestamp = strtotime($value);
                return $timestamp !== false;
            }
        }

        return false;
    }
}

