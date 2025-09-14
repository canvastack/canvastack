<?php

namespace Canvastack\Canvastack\Library\Components\Table\Craft;

use Canvastack\Canvastack\Controllers\Core\Craft\Includes\Privileges;
use Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Columns\ColumnFactory;
use Yajra\DataTables\DataTables as DataTable;

/**
 * Created on 21 Apr 2021
 * Time Created : 12:45:06
 *
 * @filesource Datatables.php
 *
 * @author     wisnuwidi@canvastack.com - 2021
 * @copyright  wisnuwidi
 *
 * @email      wisnuwidi@canvastack.com
 */
class Datatables
{
    use Privileges;

    public $filter_model = [];

    private $image_checker = ['jpg', 'jpeg', 'png', 'gif'];

    public function __construct()
    {
    }

    private function setAssetPath($file_path, $http = false, $public_path = 'public')
    {
        if (true === $http) {
            $assetsURL = explode('/', url()->asset('assets'));
            $stringURL = explode('/', $file_path);

            return implode('/', array_unique(array_merge_recursive($assetsURL, $stringURL)));
        }

        $file_path = str_replace($public_path.'/', public_path('\\'), $file_path);

        return $file_path;
    }

    private function checkValidImage($string, $local_path = true)
    {
        $filePath = $this->setAssetPath($string);

        if (true === file_exists($filePath)) {
            foreach ($this->image_checker as $check) {
                if (false !== strpos($string, $check)) {
                    return true;
                } else {
                    return false;
                }
            }

        } else {
            $filePath = explode('/', $string);
            $lastSrc = array_key_last($filePath);
            $lastFile = $filePath[$lastSrc];
            $info = "This File [ {$lastFile} ] Do Not or Never Exist!";

            return "<div class=\"show-hidden-on-hover missing-file\" title=\"{$info}\"><i class=\"fa fa-warning\"></i>&nbsp;{$lastFile}</div><!--div class=\"hide\">{$info}</div-->";
        }
    }

    public function process($method, $data, $filters = [], $filter_page = [])
    {

        $__resolved = \Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Query\ModelQueryBridge::resolve($data, $method['difta']['name'], $method);
        $model_data = $__resolved['model_data'] ?? $model_data ?? null;
        $table_name = $__resolved['table_name'] ?? $table_name ?? '';
        $order_by = $__resolved['order_by'] ?? $order_by ?? [];

        // Check if any $this->table->runModel() called
        if (! empty($data->datatables->modelProcessing[$table_name])) {
            canvastack_model_processing_table($data->datatables->modelProcessing, $table_name);
        }

        // Bridge resolution (non-destructive): re-resolve model/table/order_by to centralize logic
        $__resolved = \Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Query\ModelQueryBridge::resolve($data, $method['difta']['name'], $method);
        $model_data = $__resolved['model_data'] ?? $model_data ?? null;
        $table_name = $__resolved['table_name'] ?? $table_name ?? '';
        $order_by = $__resolved['order_by'] ?? $order_by ?? [];

        $privileges = $this->set_module_privileges();
        $index_lists = $data->datatables->records['index_lists'] ?? true; // Default to true if not set
        $column_data = $data->datatables->columns;
        $action_list = [];
        $_action_lists = [];
        $removed_privileges = [];

        // DEBUG: Log the structure of datatables columns for troubleshooting
        \Log::debug('Datatables::process - Columns structure', [
            'table_name' => $table_name,
            'has_table_key' => isset($column_data[$table_name]),
            'column_data_type' => gettype($column_data),
        ]);

        $firstField = 'id';
        $blacklists = ['password', 'action', 'no'];

        // CRITICAL FIX: If columns configuration is missing for this table, create a basic fallback
        if (!empty($table_name) && (empty($column_data) || !isset($column_data[$table_name]))) {
            \Log::warning("Datatables::process - Missing columns configuration for table: {$table_name}. Creating fallback configuration.");
            
            // Initialize column_data if it's empty
            if (empty($column_data)) {
                $column_data = [];
            }
            
            // Create comprehensive fallback configuration based on common table structures
            $fallbackLists = [$firstField]; // Start with primary key
            
            // For users table, add common user fields (use actual column names, not display labels)
            if ($table_name === 'users') {
                $fallbackLists = ['username', 'email', 'photo', 'group_info', 'address', 'phone', 'expire_date', 'active'];
            }
            
            // Create basic configuration for the table
            $column_data[$table_name] = [
                'lists' => $fallbackLists,
                'actions' => true, // Enable default actions
                'clickable' => true, // Enable row clickable functionality
            ];
            
            // Update the data object to include our fallback
            $data->datatables->columns = $column_data;
        }
        
        // CRITICAL: Update blacklist logic AFTER configuration is set (whether fallback or controller)
        // This ensures ID is blacklisted when not in lists, regardless of configuration source
        if (!empty($table_name) && isset($data->datatables->columns[$table_name]['lists']) && !empty($data->datatables->columns[$table_name]['lists'])) {
            if (!in_array('id', $data->datatables->columns[$table_name]['lists'])) {
                $firstField = $data->datatables->columns[$table_name]['lists'][0];
                $blacklists = ['password', 'action', 'no', 'id'];
                
                \Log::info('Datatables::process - ID blacklisted (not in lists)', [
                    'table_name' => $table_name,
                    'lists' => $data->datatables->columns[$table_name]['lists'],
                    'blacklists' => $blacklists,
                    'first_field' => $firstField
                ]);
            } else {
                \Log::info('Datatables::process - ID not blacklisted (in lists)', [
                    'table_name' => $table_name,
                    'lists' => $data->datatables->columns[$table_name]['lists']
                ]);
            }
        }

        $buttonsRemoval = [];
        if (!empty($table_name) && isset($data->datatables->columns[$table_name]['button_removed']) && ! empty($data->datatables->columns[$table_name]['button_removed'])) {
            $buttonsRemoval = $data->datatables->columns[$table_name]['button_removed'];
        }

        if (!empty($table_name) && isset($column_data[$table_name]['actions']) && ($column_data[$table_name]['actions'] === true || is_array($column_data[$table_name]['actions']))) {

            $action_default = ['view', 'insert', 'edit', 'delete'];
            if (true === $column_data[$table_name]['actions']) {
                $action_list = $action_default;
            } else {
                $action_list = array_merge_recursive_distinct($action_default, $column_data[$table_name]['actions']);
            }

            $actions = null;
            if ($privileges['role_group'] > 1) {
                if (! empty($privileges['role'])) {

                    if (function_exists('routelists_info') && ! empty(strpos(json_encode($privileges['role']), routelists_info()['base_info']))) {
                        foreach ($privileges['role'] as $roles) {

                            if (canvastack_string_contained($roles, routelists_info()['base_info'])) {

                                $routename = routelists_info($roles)['last_info'];
                                if (in_array($routename, ['index', 'show', 'view'])) {
                                    $actions[routelists_info()['base_info']]['view'] = 'view';

                                } elseif (in_array($routename, ['create', 'insert'])) {
                                    $actions[routelists_info()['base_info']]['insert'] = 'insert';

                                } elseif (in_array($routename, ['edit', 'modify', 'update'])) {
                                    $actions[routelists_info()['base_info']]['edit'] = 'edit';

                                } elseif (in_array($routename, ['destroy', 'delete'])) {
                                    $actions[routelists_info()['base_info']]['delete'] = 'delete';
                                }
                            }
                        }

                        if (! empty($actions)) {
                            foreach ($action_list as $_list) {
                                if (isset($actions[routelists_info()['base_info']][$_list])) {
                                    $_action_lists[] = $actions[routelists_info()['base_info']][$_list];
                                } else {
                                    if (! in_array($_list, ['view', 'insert', 'edit', 'delete'])) {
                                        $_action_lists[] = $_list;
                                    }
                                }
                            }
                        }
                    }
                }
            }

            if (! empty(array_diff($action_list, $_action_lists))) {
                $removed_privileges = array_diff($action_list, $_action_lists);
            }
        }

        // Query Factory - Extract query building logic
        $queryFactory = new \Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Query\QueryFactory();
        $queryResult = $queryFactory->buildQuery($model_data, $data, $table_name, $filters, $order_by);

        $model = $queryResult['model'];
        $limit = $queryResult['limit'];
        $joinFields = $queryResult['joinFields'] ?? null;
        $order_by = $queryResult['order_by'];

        $datatables = DataTable::of($model)
            ->setTotalRecords($limit['total'])
            ->setFilteredRecords($limit['total'])
            ->smart(true);
            
        // CRITICAL: Use only() instead of blacklist() for better column control
        // This ensures only columns in lists configuration are displayed
        if (!empty($table_name) && isset($data->datatables->columns[$table_name]['lists']) && !empty($data->datatables->columns[$table_name]['lists'])) {
            $allowedColumns = $data->datatables->columns[$table_name]['lists'];
            
            // Always add ID column for ordering and clickable functionality (will be hidden in frontend)
            if (!in_array('id', $allowedColumns)) {
                array_unshift($allowedColumns, 'id'); // Add ID as first column
            }
            
            // Always add action column if it exists
            if (!in_array('action', $allowedColumns)) {
                $allowedColumns[] = 'action';
            }
            
            $datatables->only($allowedColumns);
            
            \Log::info('Datatables::process - Using only() for column control', [
                'table_name' => $table_name,
                'allowed_columns' => $allowedColumns
            ]);
        } else {
            // Fallback to blacklist if no lists configuration
            $datatables->blacklist($blacklists);
            
            \Log::info('Datatables::process - Using blacklist() fallback', [
                'table_name' => $table_name,
                'blacklists' => $blacklists
            ]);
        }
            
        // CRITICAL: Disable Yajra's automatic ordering since we handle it in QueryFactory
        // This prevents empty column names from being processed by Yajra
        if (method_exists($datatables, 'skipOrdering')) {
            $datatables->skipOrdering();
        }

        // Always enforce raw HTML columns consistently across all pages
        $defaultRaw = ['action', 'flag_status'];
        $forcedRaw = [];
        $explicitImage = [];
        $legacyImage = [];

        if (!empty($table_name) && ! empty($column_data[$table_name]['raw_columns_forced'])) {
            $forcedRaw = (array) $column_data[$table_name]['raw_columns_forced'];
        }
        if (!empty($table_name) && ! empty($column_data[$table_name]['image_fields'])) {
            $explicitImage = (array) $column_data[$table_name]['image_fields'];
        }
        if (! empty($this->form->imageTagFieldsDatatable)) {
            $legacyImage = array_keys($this->form->imageTagFieldsDatatable);
        }

        $allRaw = array_values(array_unique(array_merge($defaultRaw, $forcedRaw, $explicitImage, $legacyImage)));
        if (! empty($allRaw)) {
            $datatables->rawColumns($allRaw);
        }
        
        // CRITICAL: Mark ID column as hidden but keep the value for ordering
        // Frontend should handle hiding this column in the display
        // Only mark as hidden if ID is not explicitly included in lists configuration
        if (!empty($table_name) && isset($data->datatables->columns[$table_name]['lists'])) {
            $allowedColumns = $data->datatables->columns[$table_name]['lists'];
            if (!in_array('id', $allowedColumns)) {
                // Add metadata to indicate this column should be hidden in frontend
                $datatables->addColumn('DT_ColumnHidden', function ($row) {
                    return 'id'; // Mark ID column as hidden
                });
            }
        }

        // Force image rendering for any explicitly-configured image fields
        $forceImageColumns = array_values(array_unique(array_merge($explicitImage, $legacyImage)));
        foreach ($forceImageColumns as $__imgField) {
            $datatables->editColumn($__imgField, function ($row) use ($__imgField) {
                $label = ucwords(str_replace('-', ' ', canvastack_clean_strings($__imgField)));
                $imgSrc = 'imgsrc::'.$label;

                // Eloquent model or array row
                $dataValue = method_exists($row, 'getAttributes') ? $row->getAttributes() : (array) $row;
                $value = (string) ($dataValue[$__imgField] ?? '');
                if ($value === '') {
                    return '';
                }

                // Prefer provided {field}_thumb if present and exists
                $thumbField = $__imgField.'_thumb';
                $filePath = $value;
                if (! empty($dataValue[$thumbField]) && is_string($dataValue[$thumbField])) {
                    $maybeThumb = (string) $dataValue[$thumbField];
                    $thumbFs = \Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Assets\AssetPathHelper::toPath($maybeThumb);
                    $filePath = file_exists($thumbFs) ? $maybeThumb : $value;
                } else {
                    // Build conventional thumb path and use it if exists
                    $parts = explode('/', $value);
                    $lastSrc = array_key_last($parts);
                    $lastFile = $parts[$lastSrc] ?? '';
                    if ($lastSrc !== null) {
                        unset($parts[$lastSrc]);
                    }
                    $maybeThumb = implode('/', $parts).'/thumb/tnail_'.$lastFile;
                    $thumbFs = \Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Assets\AssetPathHelper::toPath($maybeThumb);
                    if (file_exists($thumbFs)) {
                        $filePath = $maybeThumb;
                    }
                }

                // Render <img> if image; otherwise fallback to last path segment
                $ext = strtolower(pathinfo((string) $value, PATHINFO_EXTENSION));
                $allowed = in_array($ext, ['jpg', 'jpeg', 'png', 'gif'], true);
                if ($allowed) {
                    $fs = \Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Assets\AssetPathHelper::toPath((string) $value);
                    if (true === file_exists($fs)) {
                        return canvastack_unescape_html("<center><img class=\"cdy-img-thumb\" src=\"{$filePath}\" alt=\"{$imgSrc}\" /></center>");
                    }
                    $parts = explode('/', (string) $value);
                    $lastSrc = array_key_last($parts);
                    $lastFile = $parts[$lastSrc] ?? (string) $value;
                    $info = "This File [ {$lastFile} ] Do Not or Never Exist!";

                    return canvastack_unescape_html("<div class=\"show-hidden-on-hover missing-file\" title=\"{$info}\"><i class=\"fa fa-warning\"></i>&nbsp;{$lastFile}</div><!--div class=\"hide\">{$info}</div-->");
                }

                $seg = explode('/', $value);
                $lastIdx = array_key_last($seg);

                return $seg[$lastIdx] ?? $value;
            });
        }

        if (! empty($order_by)) {
            $orderBy = $order_by;
            // CRITICAL: Skip ordering for number_lists column as it doesn't exist in database
            if ($orderBy['column'] !== 'number_lists') {
                $datatables->order(function ($query) use ($orderBy, $table_name) {
                    // CRITICAL: Handle ambiguous columns by prefixing with main table name
                    $orderColumn = $orderBy['column'];
                    
                    // Check if column needs table prefix to avoid ambiguity
                    if (!empty($table_name) && !str_contains($orderColumn, '.')) {
                        // List of columns that commonly exist in multiple tables and cause ambiguity
                        $ambiguousColumns = ['id', 'active', 'status', 'created_at', 'updated_at', 'deleted_at', 'name'];
                        
                        if (in_array($orderColumn, $ambiguousColumns)) {
                            $orderColumn = "{$table_name}.{$orderColumn}";
                        }
                    }
                    
                    $query->orderBy($orderColumn, $orderBy['order']);
                });
            }
        } else {
            // CRITICAL: Check if columns configuration exists for this table
            if (!empty($table_name) && isset($data->datatables->columns[$table_name]['lists']) && !empty($data->datatables->columns[$table_name]['lists'])) {
                $firstColumn = $data->datatables->columns[$table_name]['lists'][0];
                // CRITICAL: Skip number_lists column and use next available column or fallback to id
                if ($firstColumn === 'number_lists') {
                    // Find next valid column or use 'id' as fallback
                    $validColumn = 'id'; // Default fallback
                    if (count($data->datatables->columns[$table_name]['lists']) > 1) {
                        $validColumn = $data->datatables->columns[$table_name]['lists'][1];
                    }
                    $orderBy = ['column' => $validColumn, 'order' => 'desc'];
                } else {
                    $orderBy = ['column' => $firstColumn, 'order' => 'desc'];
                }
                
                $datatables->order(function ($query) use ($orderBy, $table_name) {
                    // CRITICAL: Handle ambiguous columns by prefixing with main table name
                    $orderColumn = $orderBy['column'];
                    
                    // Check if column needs table prefix to avoid ambiguity
                    if (!empty($table_name) && !str_contains($orderColumn, '.')) {
                        // List of columns that commonly exist in multiple tables and cause ambiguity
                        $ambiguousColumns = ['id', 'active', 'status', 'created_at', 'updated_at', 'deleted_at', 'name'];
                        
                        if (in_array($orderColumn, $ambiguousColumns)) {
                            $orderColumn = "{$table_name}.{$orderColumn}";
                        }
                    }
                    
                    $query->orderBy($orderColumn, $orderBy['order']);
                });
            } else {
                // Fallback to default ordering if no columns configuration exists
                \Log::warning("Datatables::process - No columns configuration found for table: {$table_name}. Using default ordering.");
                $orderBy = ['column' => $firstField, 'order' => 'desc'];
                $datatables->order(function ($query) use ($orderBy, $table_name) {
                    // CRITICAL: Handle ambiguous columns by prefixing with main table name
                    $orderColumn = $orderBy['column'];
                    
                    // Check if column needs table prefix to avoid ambiguity
                    if (!empty($table_name) && !str_contains($orderColumn, '.')) {
                        // List of columns that commonly exist in multiple tables and cause ambiguity
                        $ambiguousColumns = ['id', 'active', 'status', 'created_at', 'updated_at', 'deleted_at', 'name'];
                        
                        if (in_array($orderColumn, $ambiguousColumns)) {
                            $orderColumn = "{$table_name}.{$orderColumn}";
                        }
                    }
                    
                    $query->orderBy($orderColumn, $orderBy['order']);
                });
            }
        }

        $object_called = get_object_called_name($model);
        $columnFactory = new ColumnFactory();
        $rowModel = [];

        // Use only a single sample row to register column detectors and closures
        try {
            $sampleQuery = (clone $model);
        } catch (\Throwable $e) {
            $sampleQuery = $model;
        }
        try {
            $sampleRows = $sampleQuery->limit(1)->get();
        } catch (\Throwable $e) {
            $sampleRows = $model->get()->take(1);
        }

        foreach ($sampleRows as $modelData) {
            if ('builder' === $object_called) {
                // For Query Builder, $modelData is already a stdClass object
                if (is_object($modelData) && method_exists($modelData, 'getAttributes')) {
                    $rowModel = (object) $modelData->getAttributes();
                } else {
                    // $modelData is already a stdClass from Query Builder
                    $rowModel = $modelData;
                }
            } else {
                $rowModel = $modelData;
            }

            $columnFactory->applyRowDetectors($datatables, $rowModel);

            // Data Relational
            if (empty($joinFields)) {
                if (!empty($table_name) && ! empty($column_data[$table_name]['relations'])) {
                    foreach ($column_data[$table_name]['relations'] as $relField => $relData) {
                        $dataRelations = $relData['relation_data'];
                        $datatables->editColumn($relField, function ($data) use ($dataRelations) {
                            $dataID = intval($data['id']);
                            if (! empty($dataRelations[$dataID]['field_value'])) {
                                return $dataRelations[$dataID]['field_value'];
                            } else {
                                return null;
                            }
                        });
                    }
                }
            }

            if (! empty($rowModel->flag_status)) {
                $datatables->editColumn('flag_status', function ($model) {
                    return canvastack_unescape_html(canvastack_form_internal_flag_status($model->flag_status));
                });
            }
            if (! empty($rowModel->active)) {
                $datatables->editColumn('active', function ($model) {
                    return canvastack_form_set_active_value($model->active);
                });
            }
            if (! empty($rowModel->update_status)) {
                $datatables->editColumn('update_status', function ($model) {
                    return canvastack_form_set_active_value($model->update_status);
                });
            }
            if (! empty($rowModel->request_status)) {
                $datatables->editColumn('request_status', function ($model) {
                    return canvastack_form_request_status(true, $model->request_status);
                });
            }
            if (! empty($rowModel->ip_address)) {
                $datatables->editColumn('ip_address', function ($model) {
                    if ('::1' == $model->ip_address) {
                        return canvastack_form_get_client_ip();
                    } else {
                        return $model->ip_address;
                    }
                });
            }
        }

        // Columns formatting & row attributes — delegated via ColumnFactory
        $__context = \Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Contracts\TableContext::fromLegacy(
            $method,
            $data,
            function_exists('request') ? (request()->all() ?? []) : []
        );
        $columnFactory->applyFormatters($datatables, $__context);

        // Action buttons & privileges — extracted
        \Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Actions\ActionButtonsResolver::apply(
            $datatables,
            \Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Contracts\TableContext::fromLegacy(
                $method,
                $data,
                function_exists('request') ? (request()->all() ?? []) : []
            ),
            $privileges,
            $action_list,
            $buttonsRemoval,
            $removed_privileges
        );

        // Clean Inspector call - automatically extracts all relevant context from $this
        \Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Inspector\Core\Inspector::inspect($this);

        // Ensure action column is always treated as raw (defensive re-assertion)
        if (method_exists($datatables, 'rawColumns')) {
            $datatables->rawColumns(array_values(array_unique(array_merge(['action'], $allRaw ?? []))));
        }

        $tableData = [];
        if (true === $index_lists) {
            // 'ga ada id, jadi di index'
            $tableData = $datatables->addIndexColumn()->make(true);
        } else {
            // 'ada id, jadi ga di index'
            $tableData = $datatables->make();
        }

        return $tableData;
    }

    public function init_filter_datatables($get = [], $post = [], $connection = null)
    {
        $service = new \Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Query\FilterQueryService();

        return $service->run($get, $post, $connection);
    }
}
