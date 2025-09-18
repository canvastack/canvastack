<?php

namespace Canvastack\Canvastack\Core\Craft\Action;

use Canvastack\Canvastack\Models\Admin\System\DynamicTables;
use Illuminate\Support\Facades\Log;

/**
 * Data Operations Trait
 * 
 * Handles data export, filtering, and model operations
 * Extracted from Action.php for better organization
 */
trait DataOperations
{
    public $model_filters = [];
    public $model_class_path = null;

    /**
     * Export datatables data.
     */
    private function exportDatatables()
    {
        // Auth check untuk keamanan export
        if (!auth()->check()) {
            abort(403, 'Unauthorized access to export data.');
        }

        if (! empty($_GET['exportDataTables'])) {
            if (true == $_GET['exportDataTables']) {
                $data = [];
                $table_source = $_GET['difta']['name'];
                $model_source = $_GET['difta']['source'];
                unset($_POST['_token']);

                if ('dynamics' === $model_source) {
                    $model = new DynamicTables(null, $this->connection);
                    $model->setTable($table_source);
                    $data[$table_source]['model'] = get_class($model);

                    $i = 0;
                    $model->chunk(1000, function ($rows) use (&$data, &$i, $table_source) {
                        foreach ($rows as $mod) {
                            foreach ($mod->getAttributes() as $fieldname => $fieldvalue) {
                                $data[$table_source]['export']['head'][$fieldname] = $fieldname;
                                $data[$table_source]['export']['values'][$i][$fieldname] = $fieldvalue;
                            }
                            $i++;
                        }
                    });
                }
            }
        }
    }

    /**
     * Filter Page
     *
     * @param  array  $filters
     * 	: [
     * 		'field_name' => 'value',
     * 		'field_name' => 'value'
     * 	  ]
     */
    public function filterPage($filters = [], $operator = '=')
    {
        if (! empty($filters)) {
            $this->model_filters = $filters;
        } else {
            if (! empty($this->filter_page)) {
                $this->model_filters = $this->filter_page;
            } else {
                $this->model_filters = $filters;
            }
        }

        foreach ($this->model_filters as $fieldname => $fieldvalue) {
            $this->table->conditions['where'][] = [
                'field_name' => $fieldname,
                'operator' => $operator,
                'value' => $fieldvalue,
            ];
        }
    }

    /**
     * Get Data Model
     *
     * @param  object  $class
     */
    protected function model($class, $filter = [])
    {
        $routeprocessor = ['store', 'update', 'delete'];
        $currentPage = last(explode('.', current_route()));

        $this->model_path = $class;
        $this->model_filters = $filter;
        $this->softDeletedModel = canvastack_is_softdeletes($class);

        $this->model = new $this->model_path();
        $this->model_table = $this->model->getTable();
        if (true === $this->softDeletedModel) {
            if (! in_array($currentPage, $routeprocessor)) {
                $this->model = $this->model::withTrashed();
            }
        }
        if (! empty($this->model_filters)) {
            $this->model = $this->model->where($this->model_filters);
        }
        $this->model_original = $this->model;

        if (! empty(canvastack_get_current_route_id())) {
            $this->model_id = canvastack_get_current_route_id();
            $this->model_find($this->model_id);
            //	$this->connection   = $this->model->getConnectionName();
        }

        if (! empty($this->form)) {
            $this->form->model = $this->model;
        }
    }

    /**
     * Set object injection untuk debugging.
     *
     * @param mixed $object
     */
    public function setObjectInjection($object)
    {
        $this->objectInjection = $object;
        // Environment-aware logging for debugging
        if (app()->environment(['local', 'testing'])) {
            Log::debug('Object injection set', ['object_type' => is_object($object) ? get_class($object) : gettype($object)]);
        }
    }
}