<?php

namespace Canvastack\Canvastack\Controllers\Core\Craft;

use Canvastack\Canvastack\Library\Components\Charts\Objects as Chart;
use Canvastack\Canvastack\Models\Admin\System\DynamicTables;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Created on 24 Mar 2021
 * Time Created : 17:56:08
 *
 * @filesource Action.php
 *
 * @author    wisnuwidi@canvastack.com - 2021
 * @copyright wisnuwidi
 *
 * @email     wisnuwidi@canvastack.com
 */
/**
 * Trait untuk operasi Action di CanvaStack.
 * Menangani CRUD, validasi, dan integrasi komponen.
 */
trait Action
{
    public $model = [];
    public $model_path = null;
    public $model_table = null;
    public $model_id;
    public $model_data;
    public $model_original;
    public $softDeletedModel = false;
    public $is_softdeleted = false;
    public $validations = [];
    public $uploadTrack;
    public $stored_id;
    public $store_routeback = true;
    public $filter_datatables_string = null;

    /**
     * Service untuk Action logic.
     *
     * @var \Canvastack\Canvastack\Services\ActionService
     */
    public $service;

    public function index()
    {
        $this->setPage();

        if (! empty($this->model_table)) {
            $this->table->searchable();
            $this->table->clickable();
            $this->table->sortable();

            $this->table->lists($this->model_table);
        }

        return $this->render();
    }

    public function create()
    {
        return $this->render();
    }

    private function RENDER_DEFAULT_SHOW($id)
    {
        $model_data = $this->model->find($id);

        $this->form->model($this->model, $this->model->find($id));
        foreach ($model_data->getAttributes() as $field => $value) {
            if ('id' !== $field) {
                if ('active' === $field) {
                    $this->form->selectbox($field, active_box(), $model_data->active, ['disabled']);
                } elseif ('flag_status' === $field) {
                    $this->form->selectbox($field, flag_status(), $model_data->flag_status, ['disabled']);
                } else {
                    $this->form->text($field, $value, ['disabled']);
                }
            }
        }
        $this->form->close();

        return $this->render();
    }

    public function show($id)
    {
        $this->form->addAttributes(['readonly', 'disabled', 'class' => 'form-show-only']);

        return $this->create();
    }

    public function edit($id)
    {
        $this->setPage('&nbsp;');
        if (! empty($this->getModel($id))) {
            $model = $this->getModel($id);
            $model->find($id);

            if (! empty($model->getAttributes())) {
                return $this->create();
            }
        }
    }

    public function insert_data(\Illuminate\Http\Request $request, $routeback = true)
    {
        Log::info('Insert data called with type: ' . get_class($request));

        // Always validate the request, regardless of type
        $validator = Validator::make($request->all(), $this->validations);
        if ($validator->fails()) {
            Log::warning('Validation failed during insert', ['errors' => $validator->errors(), 'data' => $request->all()]);
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $result = $this->INSERT_DATA_PROCESSOR($request, $routeback);
        
        // Ensure stored_id is set and redirect properly
        if ($routeback && !empty($this->stored_id)) {
            return $this->routeBackAfterAction('store', $this->stored_id);
        }
        
        return $result;
    }

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

    private $objectInjection = [];

    /**
     * Set object injection untuk debugging.
     *
     * @param mixed $object
     */
    public function setObjectInjection($object)
    {
        $this->objectInjection = $object;
        Log::debug('Object injection set', ['object' => $object]);
    }

    /**
     * Check access untuk datatables processing.
     */
    private function CHECK_DATATABLES_ACCESS_PROCESSOR()
    {
        if (! empty($_POST['draw']) && ! empty($_POST['columns'][0]['data']) && ! empty($_POST['length'])) {
            Log::debug('Datatables access check', ['injection' => $this->objectInjection]);
        }
    }

    private function INSERT_DATA_PROCESSOR(\Illuminate\Http\Request $request, $routeback = true)
    {
        Log::info('INSERT_DATA_PROCESSOR called with type: ' . get_class($request));

        if ($request instanceof \Canvastack\Canvastack\Http\Requests\ActionFormRequest) {
            Log::info('Request is ActionFormRequest, setting rules', ['validations' => $this->validations]);
            $request->setRules($this->validations);
        }

        // Rest of the method remains the same

        // RENDER CHARTS
        if (! empty($this->data['components']->chart)) {
            if (! empty($_GET['renderCharts']) && 'false' != $_GET['renderCharts']) {
                $chart = new Chart();

                return $chart->process($_POST);
            }
        }

        $this->CHECK_DATATABLES_ACCESS_PROCESSOR();
        if (! empty($this->exportRedirection) && true == $_POST['exportData']) {
            echo redirect($this->exportRedirection);
            exit;
        }

        $model = null;
        $this->store_routeback = $routeback;

        $req = $request->all();
        // Check if this is a filter request specifically
        if (isset($req['filters']) && ! empty($req['filters']) && 'true' === $req['filters']) {
            $this->filterDataTable($request);
            return null;
        }
        
        // Process normal insert/store operations
        // Set dynamic rules untuk FormRequest
        if ($request instanceof \Canvastack\Canvastack\Http\Requests\ActionFormRequest) {
            $request->setRules($this->validations);
        }

        try {
            if (empty($model)) {
                $model = $this->getModel();
            }
            if ('Builder' === class_basename($model)) {
                $model = $this->model_path;
            }

            // check if any input file type submited
            $data = $this->checkFileInputSubmited($request);
            $this->stored_id = canvastack_insert($model, $data, true);
            
            // Ensure proper redirect after successful insert
            if ($this->store_routeback && !empty($this->stored_id)) {
                return $this->routeBackAfterAction('store', $this->stored_id);
            }
        } catch (\Exception $e) {
            Log::error('Error during insert: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return redirect()->back()->with('error', 'Failed to insert data. Please try again.');
        }
    }

    /**
     * Handle store operation dengan service.
     *
     * @param ActionFormRequest $request
     * @return mixed
     */
    protected function store(\Illuminate\Http\Request $request)
    {
        if ($this->service === null) {
            // Get the original model instance, not the builder
            $modelInstance = $this->getModel();
            if ('Builder' === class_basename($modelInstance)) {
                $modelInstance = new $this->model_path();
            }
            $this->service = new \Canvastack\Canvastack\Services\ActionService($modelInstance, $this->validations, $this->softDeletedModel);
        }
        if (!$request instanceof \Canvastack\Canvastack\Http\Requests\ActionFormRequest) {
            $request->validate($this->validations);
        }
        // Auth check untuk keamanan
        if (!auth()->check()) {
            abort(403, 'Unauthorized access to store action.');
        }

        $this->stored_id = $this->service->handleStore($request);

        if (true === $this->store_routeback) {
            return $this->routeBackAfterAction(__FUNCTION__, $this->stored_id);
        } else {
            return $this->stored_id;
        }
    }

    public function update_data(\Illuminate\Http\Request $request, $id, $routeback = true)
    {
        Log::info('Update data called with type: ' . get_class($request));

        if (!$request instanceof \Canvastack\Canvastack\Http\Requests\ActionFormRequest) {
            $validator = Validator::make($request->all(), $this->validations);
            if ($validator->fails()) {
                return self::redirect('edit', $validator->errors(), 'failed');
            }
        }

        return $this->UPDATE_DATA_PROCESSOR($request, $id, $routeback);
    }

    /**
     * Set Validation Data
     *
     * @param  array  $roles
     * @param  array  $on_update
     */
    public function setValidations($roles = [], $on_update = [])
    {
        $this->validations = $roles;
        if (! empty($on_update) && canvastack_array_contained_string(['edit', 'update'], explode('.', current_route()))) {
            unset($this->validations);
            $this->validations = $on_update;
        }
        $this->form->setValidations($this->validations);
    }

    private static $validation_messages = [];

    private static $validation_rules = [];

    // Method validation manual dihapus; gunakan FormRequest untuk auto-validasi

    public static function redirect($to, $message_data = [], $status_info = true)
    {
        $message = null;
        if (! empty($message_data)) {
            if (is_object($message_data) && 'Request' === class_basename($message_data)) {
                if ($message_data->allFiles()) {
                    $message = $message_data->all();
                    $files = [];
                    foreach ($message_data->allFiles() as $filename => $filedata) {
                        $files[$filename] = $filedata;
                        unset($message[$filename]);
                    }
                    // Files Need Re-Check Again!!!
                } else {
                    $message = $message_data->all();
                }
            } else {
                $message = $message_data;
            }
        }

        $status = false;
        if (! empty($status_info)) {
            if (! in_array($status_info, ['success', true]) || 'failed' === $status_info) {
                $status = 'failed';
            } else {
                $status = 'success';
            }
        } else {
            $status = $status_info;
        }

        $compact = [];
        $compact['message'] = null;
        $compact['status'] = false;

        if (! empty($message)) {
            $compact['message'] = compact('message');
        }
        if (! empty($status)) {
            $compact['status'] = compact('status');
        }

        return canvastack_redirect($to, $compact['message'], $compact['status']);
    }

    private function UPDATE_DATA_PROCESSOR(\Illuminate\Http\Request $request, $id, $routeback = true)
    {
        Log::info('UPDATE_DATA_PROCESSOR called with type: ' . get_class($request));

        if ($request instanceof \Canvastack\Canvastack\Http\Requests\ActionFormRequest) {
            Log::info('Request is ActionFormRequest in update, setting rules', ['validations' => $this->validations]);
            $request->setRules($this->validations);
        }

        // Rest of the method remains the same
        // Set dynamic rules untuk FormRequest
        if ($request instanceof \Canvastack\Canvastack\Http\Requests\ActionFormRequest) {
            $request->setRules($this->validations);
        }

        $model = $this->getModel($id);

        try {
            // check if any input file type submited
            $data = $this->checkFileInputSubmited($request);

            canvastack_update($model, $data);
            $this->stored_id = intval($id);
        } catch (\Exception $e) {
            Log::error('Error during update: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return redirect()->back()->with('error', 'Failed to update data. Please try again.');
        }
    }

    /**
     * Handle update operation dengan service.
     *
     * @param ActionFormRequest $request
     * @param int $id
     * @return mixed
     */
    protected function update(\Illuminate\Http\Request $request, $id)
    {
        if ($this->service === null) {
            // Get the original model instance, not the builder
            $modelInstance = $this->getModel();
            if ('Builder' === class_basename($modelInstance)) {
                $modelInstance = new $this->model_path();
            }
            $this->service = new \Canvastack\Canvastack\Services\ActionService($modelInstance, $this->validations, $this->softDeletedModel);
        }
        Log::info('Update called with type: ' . get_class($request));

        if (!$request instanceof \Canvastack\Canvastack\Http\Requests\ActionFormRequest) {
            $validator = Validator::make($request->all(), $this->validations);
            if ($validator->fails()) {
                return self::redirect('edit', $validator->errors(), 'failed');
            }
        }
        // Auth check untuk keamanan
        if (!auth()->check()) {
            abort(403, 'Unauthorized access to update action.');
        }

        $this->stored_id = $this->service->handleUpdate($request, $id);

        if (true === $this->store_routeback) {
            return $this->routeBackAfterAction(__FUNCTION__, $id);
        } else {
            return $this->stored_id;
        }
    }

    /**
     * Handle destroy operation dengan service.
     *
     * @param Request $request
     * @param int $id
     * @return mixed
     */
    protected function destroy(Request $request, $id)
    {
        if ($this->service === null) {
            // Get the original model instance, not the builder
            $modelInstance = $this->getModel();
            if ('Builder' === class_basename($modelInstance)) {
                $modelInstance = new $this->model_path();
            }
            $this->service = new \Canvastack\Canvastack\Services\ActionService($modelInstance, $this->validations, $this->softDeletedModel);
        }
        $this->service->handleDestroy($request, $id);

        return $this->routeBackAfterAction(__FUNCTION__);
    }

    public function model_find($id)
    {
        try {
            $this->model_data = $this->model->findOrFail($id);
        } catch (ModelNotFoundException $e) {
            $this->model_data = null;
            Log::warning('Model not found in model_find for ID: ' . $id, ['trace' => $e->getTraceAsString()]);
        }

        if (true === $this->softDeletedModel) {
            if (! is_null($this->model_data->deleted_at)) {
                $this->is_softdeleted = true;
            }
        }
    }

    public $model_filters = [];

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

    public $model_class_path = null;

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
     * Redirect page after login
     *
     * created @Aug 18, 2018
     * author: wisnuwidi
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function firstRedirect()
    {
        $group_id = null;
        if (! empty($this->session_auth['group_id'])) {
            $group_id = intval($this->session_auth['group_id']);
        }
        if (1 === intval($group_id)) {
            // root group as internal
            return redirect()->intended($this->rootPage);
        } else {
            // admin and/or another group except root group as external
            return redirect()->intended($this->adminPage);
        }
    }

    /**
     * Get Model
     *
     * @param  bool  $find
     * @return object
     */
    protected function getModel($find = false)
    {
        $model = [];
        if ('Builder' === class_basename($this->model)) {
            $model = $this->model_original;
        } else {
            $model = $this->model;
        }

        if (true === $this->softDeletedModel) {
            if (false !== $find) {
                try {
                    return $model->findOrFail($find);
                } catch (ModelNotFoundException $e) {
                    try {
                        return $model::withTrashed()->findOrFail($find);
                    } catch (ModelNotFoundException $e2) {
                        Log::error('Model not found with ID: ' . $find, ['trace' => $e2->getTraceAsString()]);
                        abort(404, 'Resource not found.');
                    }
                }
            } else {
                return canvastack_get_model($model, $find);
            }
        } else {
            if (false !== $find) {
                try {
                    return $model->findOrFail($find);
                } catch (ModelNotFoundException $e) {
                    Log::error('Model not found with ID: ' . $find, ['trace' => $e->getTraceAsString()]);
                    abort(404, 'Resource not found.');
                }
            }
            return canvastack_get_model($model, $find);
        }
    }

    /**
     * Get Table Name By Model
     *
     * @param  bool  $find
     * @return string
     */
    protected function getModelTable($find = false)
    {
        return $this->getModel($find)->getTable();
    }

    /**
     * Redirect Back After Sumbit Data Process
     *
     * @param  string  $function_name
     * @param  int  $id
     * @return \Illuminate\Routing\Redirector|\Illuminate\Http\RedirectResponse
     */
    private function routeBackAfterAction($function_name, $id = false)
    {
        $currentRoute = current_route();
        Log::info('Route back after action', ['current_route' => $currentRoute, 'function' => $function_name, 'id' => $id]);
        
        if (! empty($id)) {
            // For store operations, replace 'store' with '{id}/edit'
            if ($function_name === 'store') {
                $routeBack = str_replace('.store', ".{$id}.edit", $currentRoute);
            } else {
                $routeBack = str_replace(".{$function_name}", ".{$id}.edit", $currentRoute);
            }
        } else {
            // Remove function name from route
            $routeBack = str_replace(".{$function_name}", '', $currentRoute);
        }
        
        // Convert dots to slashes for URL
        $routeBack = str_replace('.', '/', $routeBack);
        
        Log::info('Redirect to', ['route' => $routeBack]);
        return redirect($routeBack);
    }

    /**
     * Set Upload Path URL
     *
     * @return mixed
     */
    private function setUploadURL()
    {
        $currentRoute = explode('.', current_route());
        unset($currentRoute[array_key_last($currentRoute)]);
        $currentRoute = implode('.', $currentRoute);

        return str_replace('.', '/', str_replace('.'.__FUNCTION__, '', $currentRoute));
    }

    /**
     * Check If any input type file submited or not
     *
     * @return object|\Illuminate\Http\Request
     */
    private function checkFileInputSubmited(Request $request)
    {
        if (! empty($request->files)) {

            foreach ($request->files as $inputname => $file) {
                if ($request->hasfile($inputname)) {
                    // if any file type submited
                    $file = $this->fileAttributes;

                    return $this->uploadFiles($this->setUploadURL(), $request, $file);
                } else {
                    // if no one file type submited
                    return $request;
                }
            }

            // if no one file type submited
            return $request;

        } else {
            // if no one file type submited
            return $request;
        }
    }
}
