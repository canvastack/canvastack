<?php

namespace Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Query;

use Canvastack\Canvastack\Library\Components\Table\Craft\Datatables;
use Canvastack\Canvastack\Core\Craft\Includes\SafeLogger;

/**
 * DatatablesPostService — handles POST method datatables requests.
 *
 * This service processes datatables requests sent via POST method to overcome
 * URL length limitations and improve security by avoiding sensitive data in query strings.
 * It integrates seamlessly with the existing datatables infrastructure.
 */
final class DatatablesPostService
{
    /**
     * Handle POST datatables request.
     *
     * @param array $get GET parameters
     * @param array $post POST parameters containing datatables data
     * @param mixed $connection Database connection
     * @return mixed JSON response for datatables
     */
    public function handle(array $get = [], array $post = [], $connection = null)
    {
        if (app()->environment(['local', 'testing'])) {
            SafeLogger::debug('DatatablesPostService: Starting POST request handling', [
                'has_render_datatables' => !empty($get['renderDataTables']),
                'post_keys' => array_keys($post),
                'connection' => $connection ?? 'default'
            ]);
        }

        // Validate that this is a datatables POST request
        if (empty($get['renderDataTables'])) {
            if (app()->environment(['local', 'testing'])) {
                SafeLogger::warning('DatatablesPostService: Invalid request - missing renderDataTables parameter');
            }
            return null;
        }

        // Extract connection if provided in POST
        if (!empty($post['grabCoDIYC'])) {
            $connection = $post['grabCoDIYC'];
            unset($post['grabCoDIYC']);
        }

        // Extract difta parameters from POST
        $diftaData = $this->extractDiftaParameters($post);
        
        // Extract datatables parameters (draw, start, length, search, order, columns)
        $datatableParams = $this->extractDatatableParameters($post);
        
        // Extract filters if present
        $filters = $this->extractFilters($post);
        
        // Extract model filters if present
        $modelFilters = $this->extractModelFilters($post);
        
        // Build method array compatible with existing infrastructure
        $method = $this->buildMethodArray($get, $diftaData, $datatableParams);
        
        // Build data object from POST parameters
        $data = $this->buildDataObject($post, $connection, $diftaData);
        
        // Debug logging
        \Log::info('DatatablesPostService::handle', [
            'difta_name' => $diftaData['name'] ?? 'EMPTY',
            'difta_source' => $diftaData['source'] ?? 'EMPTY',
            'has_datatables_data' => !empty($post['datatables_data']),
            'datatables_model_keys' => !empty($data->datatables->model) ? array_keys($data->datatables->model) : [],
        ]);
        
        // Initialize datatables processor
        $datatables = new Datatables();
        
        // Set connection if provided
        if (!empty($connection)) {
            // Store connection for use in datatables processing
            $datatables->connection = $connection;
        }
        
        // Process the datatables request using existing infrastructure
        // This matches the signature used in View.php: process($method, $datatables, $filters, $model_filters)
        $response = $datatables->process($method, $data, $filters, $modelFilters);
        
        // Add filter configuration to response for frontend
        if ($response instanceof \Illuminate\Http\JsonResponse) {
            $responseData = json_decode($response->getContent(), true);
            
            // Add filter metadata if filterGroups are configured
            $tableName = $diftaData['name'] ?? '';
            $controllerConfig = $this->getControllerConfiguration($tableName);
            
            if (!empty($controllerConfig['filterGroups'])) {
                // Convert route path to URL path
                $routePath = $diftaData['route_path'] ?? '';
                $urlPath = str_replace('.', '/', $routePath);
                
                // Convert filterGroups to proper format for frontend
                $filterGroupsFormatted = [];
                foreach ($controllerConfig['filterGroups'] as $filterGroup) {
                    $column = $filterGroup['column'] ?? '';
                    $filterGroupsFormatted[] = [
                        'column' => $column,
                        'type' => $filterGroup['type'] ?? 'selectbox',
                        'relate' => $filterGroup['relate'] ?? true,
                        'label' => ucfirst(str_replace('_', ' ', $column))
                    ];
                }
                
                $responseData['filterConfig'] = [
                    'hasFilters' => true,
                    'filterGroups' => $filterGroupsFormatted,
                    'tableName' => $tableName,
                    'baseUrl' => url($urlPath),
                    'token' => session()->token() ?? 'no-token'
                ];
            }
            
            return response()->json($responseData);
        }
        
        return $response;
    }

    /**
     * Extract difta parameters from POST data.
     *
     * @param array $post POST parameters
     * @return array Difta parameters
     */
    private function extractDiftaParameters(array &$post): array
    {
        $difta = [];
        
        // Extract difta data if present
        if (!empty($post['difta'])) {
            $difta = $post['difta'];
            unset($post['difta']);
        }
        
        // Ensure required difta parameters exist
        $difta['name'] = $difta['name'] ?? '';
        $difta['source'] = $difta['source'] ?? 'dynamics';
        
        return $difta;
    }

    /**
     * Extract standard datatables parameters from POST data.
     *
     * @param array $post POST parameters
     * @return array Datatables parameters
     */
    private function extractDatatableParameters(array &$post): array
    {
        $params = [];
        
        // Standard datatables parameters
        $standardParams = ['draw', 'start', 'length', 'search', 'order', 'columns'];
        
        foreach ($standardParams as $param) {
            if (isset($post[$param])) {
                $params[$param] = $post[$param];
                unset($post[$param]);
            }
        }
        
        return $params;
    }

    /**
     * Extract filter parameters from POST data and URL query parameters.
     *
     * @param array $post POST parameters
     * @return array Filter parameters
     */
    private function extractFilters(array &$post): array
    {
        $filters = [];
        
        // Extract custom filters if present
        if (!empty($post['_diyF'])) {
            $filters = $post['_diyF'];
            unset($post['_diyF']);
        }
        
        // Extract other filter-related parameters
        $filterParams = ['_fita', '_forKeys', '_n'];
        foreach ($filterParams as $param) {
            if (isset($post[$param])) {
                $filters[$param] = $post[$param];
                unset($post[$param]);
            }
        }
        
        // CRITICAL FIX: Extract filter parameters from URL query parameters
        // This handles filters sent via modal form submissions like: ?username=10001243&group_info=ASM&filters=true
        $queryParams = request()->query();
        if (!empty($queryParams)) {
            // List of parameters to exclude from filters (system parameters)
            $excludeParams = [
                'renderDataTables', 'draw', 'columns', 'order', 'start', 'length', 
                'search', 'difta', '_token', '_', 'filters'
            ];
            
            foreach ($queryParams as $name => $value) {
                // Only include non-system parameters with actual values
                if (!in_array($name, $excludeParams) && !empty($value) && $value !== '') {
                    $filters[$name] = urldecode((string) $value);
                }
            }
        }
        
        // Also check POST parameters for filter values (in case they're sent via POST body)
        foreach ($post as $name => $value) {
            // Skip system parameters and already processed parameters
            $excludeParams = [
                'renderDataTables', 'draw', 'columns', 'order', 'start', 'length', 
                'search', 'difta', '_token', '_', 'filters', 'datatables_data'
            ];
            
            if (!in_array($name, $excludeParams) && !empty($value) && $value !== '') {
                $filters[$name] = is_string($value) ? urldecode($value) : $value;
            }
        }
        
        return $filters;
    }

    /**
     * Extract model filters from POST data.
     *
     * @param array $post POST parameters
     * @return array Model filters
     */
    private function extractModelFilters(array &$post): array
    {
        $modelFilters = [];
        
        // Extract model filters if present
        if (!empty($post['model_filters'])) {
            $modelFilters = $post['model_filters'];
            unset($post['model_filters']);
        }
        
        return $modelFilters;
    }

    /**
     * Build method array compatible with existing datatables infrastructure.
     *
     * @param array $get GET parameters
     * @param array $diftaData Difta parameters
     * @param array $datatableParams Datatables parameters
     * @return array Method array
     */
    private function buildMethodArray(array $get, array $diftaData, array $datatableParams): array
    {
        // Build method array that matches the structure expected by existing infrastructure
        $method = array_merge($get, $datatableParams);
        $method['difta'] = $diftaData;
        
        // CRITICAL: Process DataTables order parameter to prevent SQL errors
        if (!empty($datatableParams['order']) && !empty($datatableParams['columns'])) {
            $processedOrder = $this->processDataTablesOrder($datatableParams['order'], $datatableParams['columns']);
            if ($processedOrder) {
                $method['processed_order'] = $processedOrder;
            }
        }
        
        // Apply default ordering from controller if no valid ordering from frontend
        if (empty($method['processed_order']) && !empty($diftaData['name'])) {
            $tableName = $diftaData['name'];
            $controllerConfig = $this->getControllerConfiguration($tableName);
            if (!empty($controllerConfig['orderby'])) {
                $method['processed_order'] = $controllerConfig['orderby'];
            }
        }
        
        return $method;
    }
    
    /**
     * Process DataTables order parameter to convert column index to column name.
     * This prevents SQL errors when trying to order by pseudo columns like 'number_lists'.
     *
     * @param array $order Order parameter from DataTables
     * @param array $columns Columns parameter from DataTables
     * @return array|null Processed order or null if invalid
     */
    private function processDataTablesOrder(array $order, array $columns): ?array
    {
        if (empty($order) || !is_array($order) || empty($columns) || !is_array($columns)) {
            return null;
        }
        
        // Get first order (DataTables usually sends array of orders)
        $firstOrder = $order[0] ?? null;
        if (!$firstOrder || !isset($firstOrder['column']) || !isset($firstOrder['dir'])) {
            return null;
        }
        
        $columnIndex = (int) $firstOrder['column'];
        $direction = strtolower($firstOrder['dir']) === 'desc' ? 'desc' : 'asc';
        
        // Get column data from columns array
        if (!isset($columns[$columnIndex]) || !isset($columns[$columnIndex]['data'])) {
            return null;
        }
        
        $columnName = $columns[$columnIndex]['data'];
        
        // CRITICAL: Validate column name is not empty or whitespace
        if (empty($columnName) || !is_string($columnName) || trim($columnName) === '') {
            \Log::warning('DatatablesPostService::processDataTablesOrder - Empty or invalid column name', [
                'column_name' => $columnName,
                'column_type' => gettype($columnName),
                'column_index' => $columnIndex,
                'direction' => $direction,
                'columns_data' => $columns[$columnIndex] ?? 'not found'
            ]);
            return null; // Skip ordering for empty columns
        }
        
        // CRITICAL: Skip pseudo columns that don't exist in database
        $pseudoColumns = ['number_lists', 'DT_RowIndex', 'action', 'no'];
        if (in_array($columnName, $pseudoColumns)) {
            \Log::info('DatatablesPostService::processDataTablesOrder - Skipping pseudo column', [
                'column_name' => $columnName,
                'column_index' => $columnIndex,
                'direction' => $direction
            ]);
            return null; // Skip ordering for pseudo columns
        }
        
        return [
            'column' => $columnName,
            'order' => $direction
        ];
    }

    /**
     * Build data object compatible with existing datatables infrastructure.
     *
     * @param array $post POST parameters containing datatables data
     * @param mixed $connection Database connection
     * @return object Data object
     */
    private function buildDataObject(array $post, $connection = null, array $diftaData = []): object
    {
        // Extract datatables data from POST if present
        $datatablesData = [];
        if (!empty($post['datatables_data'])) {
            $datatablesData = $post['datatables_data'];
            unset($post['datatables_data']);
        }
        
        // Create data object structure that matches what the existing infrastructure expects
        $data = new \stdClass();
        
        // Initialize datatables property
        $data->datatables = new \stdClass();
        
        // Set properties from POST data or defaults
        $data->datatables->records = $datatablesData['records'] ?? [];
        $data->datatables->columns = $datatablesData['columns'] ?? [];
        $data->datatables->modelProcessing = $datatablesData['modelProcessing'] ?? [];
        $data->datatables->labels = $datatablesData['labels'] ?? [];
        $data->datatables->relations = $datatablesData['relations'] ?? [];
        $data->datatables->tableID = $datatablesData['tableID'] ?? [];
        $data->datatables->model = $datatablesData['model'] ?? [];
        
        // CRITICAL FIX: Auto-resolve missing model data for web requests
        $needsModelResolution = false;
        if (!empty($diftaData['name'])) {
            $tableName = $diftaData['name'];
            
            if (empty($data->datatables->model)) {
                $needsModelResolution = true;
                \Log::info('DatatablesPostService::buildDataObject - Model resolution needed: empty model');
            } else {
                // Check if existing model is invalid (not proper Eloquent model)
                $existingModel = $data->datatables->model[$tableName] ?? null;
                if (!$existingModel || !is_array($existingModel) || !isset($existingModel['type']) || !isset($existingModel['source'])) {
                    $needsModelResolution = true;
                    \Log::info('DatatablesPostService::buildDataObject - Model resolution needed: invalid model structure', [
                        'existing_model_type' => gettype($existingModel),
                        'existing_model_keys' => is_array($existingModel) ? array_keys($existingModel) : 'not_array'
                    ]);
                }
            }
        }
        
        if ($needsModelResolution) {
            $tableName = $diftaData['name'];
            $resolvedModel = $this->resolveModelForTable($tableName);
            if ($resolvedModel) {
                $data->datatables->model[$tableName] = $resolvedModel;
                \Log::info('DatatablesPostService::buildDataObject - Auto-resolved model', [
                    'table' => $tableName,
                    'model_type' => $resolvedModel['type'],
                    'model_class' => is_object($resolvedModel['source']) ? get_class($resolvedModel['source']) : 'not_object'
                ]);
            }
        }
        
        // CRITICAL FIX: Auto-resolve missing columns configuration for web requests
        $needsColumnsResolution = false;
        if (!empty($diftaData['name'])) {
            $tableName = $diftaData['name'];
            
            if (empty($data->datatables->columns)) {
                $needsColumnsResolution = true;
                \Log::info('DatatablesPostService::buildDataObject - Columns resolution needed: empty columns');
            } else {
                // Check if existing columns configuration is missing foreign keys
                $existingColumns = $data->datatables->columns[$tableName] ?? null;
                if (!$existingColumns || !isset($existingColumns['foreign_keys'])) {
                    $needsColumnsResolution = true;
                    \Log::info('DatatablesPostService::buildDataObject - Columns resolution needed: missing foreign keys', [
                        'existing_columns_keys' => $existingColumns ? array_keys($existingColumns) : 'null'
                    ]);
                }
            }
        }
        
        if ($needsColumnsResolution) {
            $tableName = $diftaData['name'];
            $resolvedColumns = $this->resolveColumnsForTable($tableName);
            if ($resolvedColumns) {
                $data->datatables->columns[$tableName] = $resolvedColumns;
                \Log::info('DatatablesPostService::buildDataObject - Auto-resolved columns', [
                    'table' => $tableName,
                    'columns_keys' => array_keys($resolvedColumns),
                    'has_foreign_keys' => isset($resolvedColumns['foreign_keys']),
                    'foreign_keys_count' => isset($resolvedColumns['foreign_keys']) ? count($resolvedColumns['foreign_keys']) : 0
                ]);
            }
        }
        
        // Add route_path from difta data if available
        if (!empty($diftaData['route_path'])) {
            $data->datatables->route_path = $diftaData['route_path'];
        }
        
        // Set connection if provided
        if (!empty($connection)) {
            $data->datatables->connection = $connection;
        }
        
        // Handle any remaining POST data as additional properties
        foreach ($post as $key => $value) {
            if (!isset($data->datatables->$key)) {
                $data->datatables->$key = $value;
            }
        }
        
        return $data;
    }
    
    /**
     * Resolve model for a given table name by checking common model locations
     *
     * @param string $tableName
     * @return array|null Model structure with type and source
     */
    private function resolveModelForTable(string $tableName): ?array
    {
        // Common model mappings for system tables
        $modelMappings = [
            'users' => \Canvastack\Canvastack\Models\Admin\System\User::class,
            'base_group' => \Canvastack\Canvastack\Models\Admin\System\Group::class,
            'base_module' => \Canvastack\Canvastack\Models\Admin\System\Module::class,
            // Add more mappings as needed
        ];
        
        // Try to resolve from mapping first
        if (isset($modelMappings[$tableName])) {
            $modelClass = $modelMappings[$tableName];
            if (class_exists($modelClass)) {
                try {
                    $modelInstance = new $modelClass();
                    return [
                        'type' => 'model',
                        'source' => $modelInstance
                    ];
                } catch (\Exception $e) {
                    \Log::warning('DatatablesPostService::resolveModelForTable - Failed to instantiate model', [
                        'table' => $tableName,
                        'model_class' => $modelClass,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }
        
        // Try to auto-discover model by convention (TableName -> Model\TableName)
        $camelCaseName = str_replace(' ', '', ucwords(str_replace('_', ' ', $tableName)));
        $conventionMappings = [
            'App\\Models\\' . ucfirst(str_replace('_', '', $tableName)),
            'App\\Models\\' . $camelCaseName,
            'Canvastack\\Canvastack\\Models\\Admin\\System\\' . ucfirst(str_replace('_', '', $tableName)),
            'Canvastack\\Canvastack\\Models\\Admin\\System\\' . $camelCaseName,
        ];
        
        foreach ($conventionMappings as $modelClass) {
            if (class_exists($modelClass)) {
                try {
                    $modelInstance = new $modelClass();
                    // Verify this model actually uses the expected table
                    if (method_exists($modelInstance, 'getTable') && $modelInstance->getTable() === $tableName) {
                        return [
                            'type' => 'model',
                            'source' => $modelInstance
                        ];
                    }
                } catch (\Exception $e) {
                    // Continue to next mapping
                    continue;
                }
            }
        }
        
        \Log::warning('DatatablesPostService::resolveModelForTable - Could not resolve model', [
            'table' => $tableName,
            'tried_mappings' => array_merge([$modelMappings[$tableName] ?? 'none'], $conventionMappings)
        ]);
        
        return null;
    }
    
    /**
     * Resolve columns configuration for a given table name
     * UPDATED: Now tries to get configuration from actual controller first
     *
     * @param string $tableName
     * @return array|null Columns configuration
     */
    private function resolveColumnsForTable(string $tableName): ?array
    {
        // STEP 1: Try to get configuration from actual controller
        $controllerConfig = $this->getControllerConfiguration($tableName);
        if ($controllerConfig) {
            \Log::info('DatatablesPostService::resolveColumnsForTable - Using controller config', [
                'table' => $tableName,
                'config_keys' => array_keys($controllerConfig),
                'lists_count' => isset($controllerConfig['lists']) ? count($controllerConfig['lists']) : 0
            ]);
            return $controllerConfig;
        }
        
        // STEP 2: Fallback to predefined configuration (minimal, only foreign keys)
        $columnsConfig = [
            'users' => [
                'foreign_keys' => [
                    'base_user_group.user_id' => 'users.id',
                    'base_group.id' => 'base_user_group.group_id'
                ],
                'clickable' => true,
                'orderby' => ['column' => 'id', 'order' => 'desc'] // Match UserController ordering
                // REMOVED hardcoded lists - let controller take precedence
            ],
            'base_group' => [
                'lists' => [
                    'group_name', 'group_alias', 'group_info', 'active'
                ],
                'clickable' => true,
                'orderby' => ['column' => 'group_name', 'order' => 'asc']
            ],
            'base_module' => [
                'lists' => [
                    'module_name', 'module_alias', 'module_info', 'active'
                ],
                'clickable' => true,
                'orderby' => ['column' => 'module_name', 'order' => 'asc']
            ]
        ];
        
        if (isset($columnsConfig[$tableName])) {
            \Log::info('DatatablesPostService::resolveColumnsForTable - Using predefined config', [
                'table' => $tableName,
                'config_keys' => array_keys($columnsConfig[$tableName])
            ]);
            return $columnsConfig[$tableName];
        }
        
        // Try to auto-generate basic columns configuration
        try {
            $columns = \Schema::getColumnListing($tableName);
            if (!empty($columns)) {
                $basicConfig = [
                    'lists' => array_slice($columns, 0, 6), // First 6 columns
                    'clickable' => true,
                    'orderby' => ['column' => $columns[0], 'order' => 'asc'] // Order by first column
                ];
                
                \Log::info('DatatablesPostService::resolveColumnsForTable - Auto-generated config', [
                    'table' => $tableName,
                    'columns_count' => count($columns),
                    'lists_count' => count($basicConfig['lists'])
                ]);
                
                return $basicConfig;
            }
        } catch (\Exception $e) {
            \Log::warning('DatatablesPostService::resolveColumnsForTable - Failed to auto-generate', [
                'table' => $tableName,
                'error' => $e->getMessage()
            ]);
        }
        
        return null;
    }
    
    /**
     * Get configuration from actual controller
     * This method attempts to instantiate the controller and read its table configuration
     *
     * @param string $tableName
     * @return array|null Controller configuration
     */
    private function getControllerConfiguration(string $tableName): ?array
    {
        try {
            // Map table names to controller classes
            $controllerMap = [
                'users' => \Canvastack\Canvastack\Controllers\Admin\System\UserController::class,
                'base_group' => \Canvastack\Canvastack\Controllers\Admin\System\GroupController::class,
                'base_module' => \Canvastack\Canvastack\Controllers\Admin\System\ModuleController::class,
            ];
            
            if (!isset($controllerMap[$tableName])) {
                \Log::info('DatatablesPostService::getControllerConfiguration - No controller mapping', [
                    'table' => $tableName
                ]);
                return null;
            }
            
            $controllerClass = $controllerMap[$tableName];
            
            // Check if controller class exists
            if (!class_exists($controllerClass)) {
                \Log::warning('DatatablesPostService::getControllerConfiguration - Controller class not found', [
                    'table' => $tableName,
                    'controller_class' => $controllerClass
                ]);
                return null;
            }
            
            // Create controller instance
            $controller = new $controllerClass();
            
            // Use reflection to access the table property
            $reflection = new \ReflectionClass($controller);
            $tableProperty = $reflection->getProperty('table');
            $tableProperty->setAccessible(true);
            $table = $tableProperty->getValue($controller);
            
            if (!$table) {
                \Log::warning('DatatablesPostService::getControllerConfiguration - No table property', [
                    'table' => $tableName,
                    'controller_class' => $controllerClass
                ]);
                return null;
            }
            
            // Call individual configuration methods instead of full index()
            // This avoids session errors and encryption issues
            try {
                // Set up basic configuration
                $table->method('POST');
                $table->searchable();
                $table->clickable();
                $table->sortable();
                
                // Set up ordering (from UserController line 82)
                $table->orderby('id', 'DESC');
                
                // Set up filter groups (from UserController lines 80-81)
                $table->filterGroups('username', 'selectbox', true);
                $table->filterGroups('group_info', 'selectbox', true);
                
                // Set field as image (from UserController line 83)
                $table->setFieldAsImage(['photo']);
                
                // For lists, we'll use the hardcoded configuration from UserController line 84
                // ['username:User', 'email', 'photo', 'group_info', 'address', 'phone', 'expire_date', 'active']
                
                \Log::info('DatatablesPostService::getControllerConfiguration - Applied controller methods', [
                    'table' => $tableName
                ]);
                
            } catch (\Exception $e) {
                \Log::info('DatatablesPostService::getControllerConfiguration - Method setup exception (ignored)', [
                    'table' => $tableName,
                    'exception' => $e->getMessage()
                ]);
            }
            
            // Now extract the configuration from the table object
            $config = [];
            
            // Extract from variables (this is where orderby, clickable, etc. are stored)
            // Use reflection to access private variables property
            $tableReflection = new \ReflectionClass($table);
            if ($tableReflection->hasProperty('variables')) {
                $variablesProperty = $tableReflection->getProperty('variables');
                $variablesProperty->setAccessible(true);
                $variables = $variablesProperty->getValue($table);
                
                if (!empty($variables) && is_array($variables)) {
                
                // Extract orderby configuration
                if (isset($variables['orderby_column'])) {
                    $config['orderby'] = $variables['orderby_column'];
                }
                
                // Extract clickable configuration
                if (isset($variables['clickable_columns'])) {
                    $clickableColumns = $variables['clickable_columns'];
                    // Check if clickable is enabled (usually {"all::columns":true})
                    $config['clickable'] = !empty($clickableColumns) && (
                        (is_array($clickableColumns) && isset($clickableColumns['all::columns']) && $clickableColumns['all::columns']) ||
                        (is_bool($clickableColumns) && $clickableColumns)
                    );
                } else {
                    // Default clickable to true for users table
                    $config['clickable'] = true;
                }
                
                // Extract searchable configuration
                if (isset($variables['searchable_columns'])) {
                    $config['searchable'] = $variables['searchable_columns'];
                }
                
                // Extract filter groups
                if (isset($variables['filter_groups'])) {
                    $config['filterGroups'] = $variables['filter_groups'];
                }
                
                // Extract orderby configuration
                if (isset($variables['orderby_column'])) {
                    $config['orderby'] = $variables['orderby_column'];
                }
                
                \Log::info('DatatablesPostService::getControllerConfiguration - Extracted from variables', [
                    'table' => $tableName,
                    'config_keys' => array_keys($config),
                    'variables_keys' => array_keys($variables),
                    'clickable_config' => $config['clickable'] ?? 'not set',
                    'clickable_columns_raw' => $variables['clickable_columns'] ?? 'not found'
                ]);
                } else {
                    \Log::info('DatatablesPostService::getControllerConfiguration - Variables empty or not array', [
                        'table' => $tableName,
                        'variables_type' => gettype($variables),
                        'variables_empty' => empty($variables)
                    ]);
                }
            } else {
                \Log::info('DatatablesPostService::getControllerConfiguration - No variables property', [
                    'table' => $tableName
                ]);
            }
            
            // Add hardcoded lists configuration from UserController
            if ($tableName === 'users') {
                // Parse the lists configuration to extract column names (remove labels)
                $rawLists = ['username:User', 'email', 'photo', 'group_info', 'address', 'phone', 'expire_date', 'active'];
                $parsedLists = [];
                
                foreach ($rawLists as $item) {
                    // Extract column name (before colon) or use full item if no colon
                    if (strpos($item, ':') !== false) {
                        $parsedLists[] = explode(':', $item)[0];
                    } else {
                        $parsedLists[] = $item;
                    }
                }
                
                $config['lists'] = $parsedLists;
                
                \Log::info('DatatablesPostService::getControllerConfiguration - Parsed lists config', [
                    'table' => $tableName,
                    'raw_lists' => $rawLists,
                    'parsed_lists' => $parsedLists
                ]);
            }
            
            // Try to get columns configuration (if any)
            if (property_exists($table, 'columns') && !empty($table->columns)) {
                $tableColumns = $table->columns;
                if (isset($tableColumns[$tableName])) {
                    $config = array_merge($config, $tableColumns[$tableName]);
                    \Log::info('DatatablesPostService::getControllerConfiguration - Merged columns config', [
                        'table' => $tableName,
                        'final_config_keys' => array_keys($config)
                    ]);
                }
            }
            
            // Add foreign keys for users table (hardcoded for now)
            if ($tableName === 'users' && !isset($config['foreign_keys'])) {
                $config['foreign_keys'] = [
                    'base_user_group.user_id' => 'users.id',
                    'base_group.id' => 'base_user_group.group_id'
                ];
            }
            
            return !empty($config) ? $config : null;
            
        } catch (\Exception $e) {
            \Log::error('DatatablesPostService::getControllerConfiguration - Exception', [
                'table' => $tableName,
                'exception' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return null;
        }
    }
}