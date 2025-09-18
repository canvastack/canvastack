<?php

namespace Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Query;

use Canvastack\Canvastack\Models\Admin\System\DynamicTables;
use Canvastack\Canvastack\Core\Craft\Includes\SafeLogger;

/**
 * ModelQueryBridge — unify resolution for model/sql sources and runModel processing.
 * Mirrors legacy Datatables::process behavior exactly.
 */
final class ModelQueryBridge
{
    /**
     * @param  object  $data Datatables context (legacy object carrying datatables config)
     * @param  string  $name Table name key from method difta
     * @param  array  $method Method array containing processed_order from DataTables request
     * @return array{model_data:mixed, table_name:string, order_by:array}
     */
    public static function resolve($data, string $name, array $method = []): array
    {
        if (app()->environment(['local', 'testing'])) {
            SafeLogger::debug('ModelQueryBridge: Starting model query resolution', [
                'name' => $name,
                'has_processed_order' => !empty($method['processed_order']),
                'has_model_data' => !empty($data->datatables->model[$name])
            ]);
        }

        $model_data = null;
        $table_name = '';
        $order_by = [];

        // CRITICAL: Check for processed_order from DataTables POST request FIRST (highest priority)
        if (!empty($method['processed_order'])) {
            $order_by = $method['processed_order'];
            if (app()->environment(['local', 'testing'])) {
                SafeLogger::info('ModelQueryBridge: Using processed order from DataTables request (highest priority)', [
                    'processed_order' => $order_by,
                    'name' => $name
                ]);
            }
        }

        if (! empty($data->datatables->model[$name]) && 
            isset($data->datatables->model[$name]['type']) && 
            isset($data->datatables->model[$name]['source'])) {
            
            $model_type = $data->datatables->model[$name]['type'];
            $model_source = $data->datatables->model[$name]['source'];

            if ('model' === $model_type) {
                // Validate that model_source is a valid object with getTable method
                if (is_object($model_source) && method_exists($model_source, 'getTable')) {
                    $model_data = $model_source;
                    $table_name = $model_data->getTable();
                } else {
                    \Log::warning('ModelQueryBridge::resolve - Invalid model source', [
                        'name' => $name,
                        'model_source_type' => gettype($model_source),
                        'model_source_is_object' => is_object($model_source),
                        'model_source_has_getTable' => is_object($model_source) ? method_exists($model_source, 'getTable') : false,
                        'model_source_value' => is_array($model_source) ? 'array[' . count($model_source) . ']' : (is_object($model_source) ? get_class($model_source) : $model_source)
                    ]);
                    // Keep model_data as null to trigger fallback
                }
            }

            // Only use column orderby if no processed_order was set earlier
            if (empty($order_by) && !empty($table_name) && ! empty($data->datatables->columns[$table_name]['orderby'])) {
                $order_by = $data->datatables->columns[$table_name]['orderby'];
                \Log::info('ModelQueryBridge::resolve - Using column orderby as fallback', [
                    'column_orderby' => $order_by,
                    'table_name' => $table_name
                ]);
            }

            // DEVELOPMENT STATUS | @WAITINGLISTS
            if ('sql' === $model_type) {
                $model_data = new DynamicTables($model_source);
            }
        } else if ((empty($name) || empty($data->datatables->model[$name])) && !empty($data->datatables->model)) {
            // Fallback: if name is empty/invalid but model data exists, try to use the first available model
            $firstModelKey = array_key_first($data->datatables->model);
            if ($firstModelKey !== null && 
                isset($data->datatables->model[$firstModelKey]['type']) && 
                isset($data->datatables->model[$firstModelKey]['source'])) {
                
                $model_type = $data->datatables->model[$firstModelKey]['type'];
                $model_source = $data->datatables->model[$firstModelKey]['source'];
                $name = $firstModelKey; // Update name to use the found key
                
                if ('model' === $model_type) {
                    $model_data = $model_source;
                    $table_name = $model_data->getTable();
                }
                
                // Only use column orderby if no processed_order was set earlier
                if (empty($order_by) && !empty($table_name) && ! empty($data->datatables->columns[$table_name]['orderby'])) {
                    $order_by = $data->datatables->columns[$table_name]['orderby'];
                    \Log::info('ModelQueryBridge::resolve - Using column orderby as fallback (fallback model)', [
                        'column_orderby' => $order_by,
                        'table_name' => $table_name
                    ]);
                }
                
                if ('sql' === $model_type) {
                    $model_data = new DynamicTables($model_source);
                }
            }
        }

        // Check if any $this->table->runModel() called
        if (!empty($table_name) && ! empty($data->datatables->modelProcessing[$table_name])) {
            canvastack_model_processing_table($data->datatables->modelProcessing, $table_name);
        }

        // CRITICAL: Ensure model_data is not null
        if (is_null($model_data)) {
            \Log::error('ModelQueryBridge::resolve - No valid model found', [
                'input_name' => $name,
                'has_datatables_model' => !empty($data->datatables->model),
                'datatables_model_keys' => !empty($data->datatables->model) ? array_keys($data->datatables->model) : [],
                'table_name' => $table_name,
            ]);
            
            // If table_name is empty, try to use the input name as table name
            if (empty($table_name) && !empty($name)) {
                $table_name = $name;
                \Log::info('ModelQueryBridge::resolve - Using input name as table name: ' . $table_name);
            }
            
            // Try to create a basic model as fallback
            if (!empty($table_name)) {
                try {
                    // Create a basic Eloquent model for the table
                    $model_data = \DB::table($table_name);
                    \Log::info('ModelQueryBridge::resolve - Created fallback DB query for table: ' . $table_name);
                    
                    // CRITICAL: Check for column orderby in fallback path too
                    if (empty($order_by) && !empty($data->datatables->columns[$table_name]['orderby'])) {
                        $order_by = $data->datatables->columns[$table_name]['orderby'];
                        \Log::info('ModelQueryBridge::resolve - Using column orderby in fallback path', [
                            'column_orderby' => $order_by,
                            'table_name' => $table_name
                        ]);
                    }
                    
                } catch (\Exception $e) {
                    \Log::error('ModelQueryBridge::resolve - Failed to create fallback model', [
                        'table_name' => $table_name,
                        'error' => $e->getMessage()
                    ]);
                    throw new \Exception("No valid model found and cannot create fallback for table: {$table_name}");
                }
            } else {
                throw new \Exception("No valid model found and no table name available for fallback");
            }
        }
        
        // Debug logging
        \Log::info('ModelQueryBridge::resolve', [
            'input_name' => $name,
            'model_data_is_null' => is_null($model_data),
            'model_data_class' => is_object($model_data) ? get_class($model_data) : gettype($model_data),
            'table_name' => $table_name,
            'has_datatables_model' => !empty($data->datatables->model),
            'datatables_model_keys' => !empty($data->datatables->model) ? array_keys($data->datatables->model) : [],
        ]);
        
        return [
            'model_data' => $model_data,
            'table_name' => $table_name,
            'order_by' => $order_by,
        ];
    }
}
