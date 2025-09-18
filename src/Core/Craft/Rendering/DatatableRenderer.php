<?php

namespace Canvastack\Canvastack\Core\Craft\Rendering;

use Canvastack\Canvastack\Library\Components\Table\Craft\Datatables;

/**
 * DatatableRenderer Trait
 * 
 * Menangani semua operasi datatables processing.
 * Extracted from View.php untuk better separation of concerns.
 * 
 * Responsibilities:
 * - Datatables rendering untuk GET dan POST requests
 * - Hybrid mode processing
 * - Filter handling
 * - DataTables parameter processing
 * - Legacy dan modern pipeline support
 * 
 * @author wisnuwidi@canvastack.com - 2021
 */
trait DatatableRenderer
{
    public $initRenderDatatablePost = [];

    /**
     * Handle datatables rendering for POST and GET.
     *
     * @param \Illuminate\Http\Request $request
     * @return mixed Return early if datatables rendered
     */
    private function handleDatatables(\Illuminate\Http\Request $request)
    {
        $validated = $request->validate([
            'renderDataTables' => 'nullable|in:true,false,1,0',
        ]);

        if (! empty($this->data['components']->table->method) && 'post' === strtolower($this->data['components']->table->method)) {
            // Handle POST method datatables - SAME PIPELINE AS GET METHOD
            if ($request->isMethod('post') && !empty($request->get('renderDataTables'))) {
                // Use the SAME pipeline as GET method to ensure consistent DT_RowAttr generation
                $filter_datatables = $this->model_filters ?? [];
                $method = $request->all();
                
                // Ensure difta key exists for POST requests
                if (!isset($method['difta'])) {
                    $method['difta'] = ['name' => array_keys($this->data['components']->table->model)[0] ?? '', 'source' => 'dynamics'];
                }
                
                return $this->initRenderDatatables($method, $this->data['components']->table, $filter_datatables);
            }
            
            $filter_datatables = $this->model_filters ?? [];
            $method = [
                'method' => 'post',
                'renderDataTables' => true,
                'difta' => ['name' => array_keys($this->data['components']->table->model)[0] ?? '', 'source' => 'dynamics'],
            ];

            $this->initRenderDatatables($method, $this->data['components']->table, $filter_datatables);

            return null;
        }

        if (! empty($validated['renderDataTables']) && 'false' != $validated['renderDataTables']) {
            $filter_datatables = $this->model_filters ?? [];
            $method = $request->all();
            
            // Ensure difta key exists for GET requests
            if (!isset($method['difta'])) {
                $method['difta'] = ['name' => array_keys($this->data['components']->table->model)[0] ?? '', 'source' => 'dynamics'];
            }
            
            return $this->initRenderDatatables($method, $this->data['components']->table, $filter_datatables);
        }

        return null;
    }

    /**
     * Initialize datatables rendering with hybrid mode support.
     *
     * @param array $method Request method data
     * @param array $data Table data configuration
     * @param array $model_filters Model filters
     * @return mixed Datatables response
     */
    private function initRenderDatatables($method, $data = [], $model_filters = [])
    {
        if (! empty($data)) {
            $dataTable = $data;
        } else {
            $dataTable = $this->data['components']->table;
        }

        if (! empty($dataTable)) {
            $Datatables = [];
            $Datatables['datatables'] = $dataTable;
            $datatables = canvastack_array_to_object_recursive($Datatables);

            $filters = [];
            if (! empty($method['filters'])) {
                if ('true' === $method['filters']) {
                    $filters = $method;
                }
            }

            // Hybrid-compare: run pipeline preflight + legacy, return legacy result
            // CRITICAL FIX: Check hybrid mode BEFORE POST method handling to ensure POST also uses hybrid mode
            if (function_exists('config') && config('canvastack.datatables.mode') === 'hybrid') {
                try {
                    $result = \Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Support\HybridCompare::run($method, $datatables, $filters, $model_filters);
                    if (function_exists('logger')) {
                        logger()->debug('[DT Hybrid] Diff', $result['diff'] ?? []);
                    }

                    return $result['legacy_result'];
                } catch (\Throwable $e) {
                    if (function_exists('logger')) {
                        logger()->warning('[DT Hybrid] Error '.$e->getMessage());
                    }
                    // fall through to legacy
                }
            }

            $DataTables = new Datatables();
            if (! empty($method['method']) && 'post' === $method['method']) {
                // CRITICAL FIX: Use the SAME pipeline as GET method for POST requests
                // This ensures DataTables parameters (draw, start, length, order, columns, search) are properly processed
                
                // Environment-aware logging for datatables debugging
                if (app()->environment(['local', 'testing'])) {
                    \Log::debug('DatatableRenderer - POST method using GET pipeline', [
                        'method_keys' => array_keys($method),
                        'has_datatables_params' => isset($method['draw'], $method['columns'])
                    ]);
                }
                
                // Use the SAME process() method as GET - this is the key fix!
                return $DataTables->process($method, $datatables, $filters, $model_filters);
            }

            return $DataTables->process($method, $datatables, $filters, $model_filters);
        }
    }
}