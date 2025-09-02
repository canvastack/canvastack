<?php

namespace Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Query;

use Canvastack\Canvastack\Models\Admin\System\DynamicTables;

/**
 * ModelQueryBridge — unify resolution for model/sql sources and runModel processing.
 * Mirrors legacy Datatables::process behavior exactly.
 */
final class ModelQueryBridge
{
    /**
     * @param  object  $data Datatables context (legacy object carrying datatables config)
     * @param  string  $name Table name key from method difta
     * @return array{model_data:mixed, table_name:string, order_by:array}
     */
    public static function resolve($data, string $name): array
    {
        $model_data = null;
        $table_name = '';
        $order_by = [];

        if (! empty($data->datatables->model[$name])) {
            $model_type = $data->datatables->model[$name]['type'];
            $model_source = $data->datatables->model[$name]['source'];

            if ('model' === $model_type) {
                $model_data = $model_source;
                $table_name = $model_data->getTable();
            }

            if (! empty($data->datatables->columns[$table_name]['orderby'])) {
                $order_by = $data->datatables->columns[$table_name]['orderby'];
            }

            // DEVELOPMENT STATUS | @WAITINGLISTS
            if ('sql' === $model_type) {
                $model_data = new DynamicTables($model_source);
            }
        }

        // Check if any $this->table->runModel() called
        if (! empty($data->datatables->modelProcessing[$table_name])) {
            canvastack_model_processing_table($data->datatables->modelProcessing, $table_name);
        }

        return [
            'model_data' => $model_data,
            'table_name' => $table_name,
            'order_by' => $order_by,
        ];
    }
}
