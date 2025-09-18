<?php

namespace Canvastack\Canvastack\Core\Craft\Action;

use Canvastack\Canvastack\Library\Components\Charts\Objects as Chart;
use Canvastack\Canvastack\Models\Admin\System\DynamicTables;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * CRUD Operations Trait
 * 
 * Handles basic CRUD operations: index, create, show, edit, store, update, destroy
 * Extracted from Action.php for better organization
 */
trait CrudOperations
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->setPage();

        if (! empty($this->model_table)) {
            $this->table->searchable();
            $this->table->clickable();
            $this->table->sortable();

            $this->table->lists($this->model_table);
            
            // Auto-inject delete assets for index pages with tables
            $this->injectDeleteAssets();
        }

        return $this->render();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return $this->render();
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $this->form->addAttributes(['readonly', 'disabled', 'class' => 'form-show-only']);

        return $this->create();
    }

    /**
     * Show the form for editing the specified resource.
     */
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

    /**
     * Store a newly created resource in storage.
     */
    public function insert_data(\Illuminate\Http\Request $request, $routeback = true)
    {
        // Environment-aware logging for debugging
        if (app()->environment(['local', 'testing'])) {
            Log::debug('CrudOperations insert_data called', ['request_type' => get_class($request)]);
        }

        // Always validate the request, regardless of type
        $validator = Validator::make($request->all(), $this->validations);
        if ($validator->fails()) {
            // Log validation errors without exposing sensitive data
            if (app()->environment(['local', 'testing'])) {
                Log::warning('Validation failed during insert', ['errors' => $validator->errors()]);
            } else {
                Log::warning('Validation failed during insert operation');
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $result = $this->INSERT_DATA_PROCESSOR($request, $routeback);
        
        // Ensure stored_id is set and redirect properly
        if ($routeback && !empty($this->stored_id)) {
            return $this->routeBackAfterAction('store', $this->stored_id);
        }
        
        return $result;
    }

    /**
     * Handle store operation dengan service.
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

    /**
     * Update the specified resource in storage.
     */
    public function update_data(\Illuminate\Http\Request $request, $id, $routeback = true)
    {
        // Environment-aware logging for debugging
        if (app()->environment(['local', 'testing'])) {
            Log::debug('CrudOperations update_data called', ['request_type' => get_class($request), 'id' => $id]);
        }

        // Always validate the request, regardless of type
        $validator = Validator::make($request->all(), $this->validations);
        if ($validator->fails()) {
            // Log validation errors without exposing sensitive data
            if (app()->environment(['local', 'testing'])) {
                Log::warning('Validation failed during update', ['errors' => $validator->errors()]);
            } else {
                Log::warning('Validation failed during update operation');
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $result = $this->UPDATE_DATA_PROCESSOR($request, $id, $routeback);
        
        return $result;
    }

    /**
     * Handle update operation dengan service.
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
        // Environment-aware logging for debugging
        if (app()->environment(['local', 'testing'])) {
            Log::debug('CrudOperations update called', ['request_type' => get_class($request)]);
        }

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
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, $id)
    {
        // Use DynamicDeleteTrait's destroy method for universal handling
        return $this->dynamicDestroy($request, $id);
    }
    
    /**
     * Restore the specified soft deleted resource.
     */
    public function restore(Request $request, $id)
    {
        // Use DynamicDeleteTrait's restore method
        return $this->dynamicRestore($request, $id);
    }

    /**
     * Find model by ID.
     */
    public function model_find($id)
    {
        try {
            $this->model_data = $this->model->findOrFail($id);
        } catch (ModelNotFoundException $e) {
            $this->model_data = null;
            // Log model not found without exposing trace in production
            if (app()->environment(['local', 'testing'])) {
                Log::warning('Model not found in model_find', ['id' => $id, 'trace' => $e->getTraceAsString()]);
            } else {
                Log::warning('Model not found in model_find', ['id' => $id]);
            }
        }

        if (true === $this->softDeletedModel) {
            if (! is_null($this->model_data->deleted_at)) {
                $this->is_softdeleted = true;
            }
        }
    }

    /**
     * Get Model instance.
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
                        // Log error without exposing trace in production
                        if (app()->environment(['local', 'testing'])) {
                            Log::error('Model not found with ID: ' . $find, ['trace' => $e2->getTraceAsString()]);
                        } else {
                            Log::error('Model not found', ['id' => $find]);
                        }
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
                    // Log error without exposing trace in production
                    if (app()->environment(['local', 'testing'])) {
                        Log::error('Model not found with ID: ' . $find, ['trace' => $e->getTraceAsString()]);
                    } else {
                        Log::error('Model not found', ['id' => $find]);
                    }
                    abort(404, 'Resource not found.');
                }
            }
            return canvastack_get_model($model, $find);
        }
    }

    /**
     * Get Table Name By Model.
     */
    protected function getModelTable($find = false)
    {
        return $this->getModel($find)->getTable();
    }

    /**
     * Process insert data.
     */
    private function INSERT_DATA_PROCESSOR(\Illuminate\Http\Request $request, $routeback = true)
    {
        // Environment-aware logging for debugging
        if (app()->environment(['local', 'testing'])) {
            Log::debug('INSERT_DATA_PROCESSOR called', ['request_type' => get_class($request)]);
        }

        if ($request instanceof \Canvastack\Canvastack\Http\Requests\ActionFormRequest) {
            if (app()->environment(['local', 'testing'])) {
                Log::debug('Request is ActionFormRequest, setting rules');
            }
            $request->setRules($this->validations);
        }

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
            // Log error without exposing trace in production
            if (app()->environment(['local', 'testing'])) {
                Log::error('Error during insert: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            } else {
                Log::error('Error during insert operation', ['message' => $e->getMessage()]);
            }
            return redirect()->back()->with('error', 'Failed to insert data. Please try again.');
        }
    }

    /**
     * Process update data.
     */
    private function UPDATE_DATA_PROCESSOR(\Illuminate\Http\Request $request, $id, $routeback = true)
    {
        $model = $this->getModel($id);

        try {
            // check if any input file type submited
            $data = $this->checkFileInputSubmited($request);

            canvastack_update($model, $data);
            $this->stored_id = intval($id);
        } catch (\Exception $e) {
            // Log error without exposing trace in production
            if (app()->environment(['local', 'testing'])) {
                Log::error('Error during update: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            } else {
                Log::error('Error during update operation', ['message' => $e->getMessage()]);
            }
            return redirect()->back()->with('error', 'Failed to update data. Please try again.');
        }
    }

    /**
     * Default show rendering.
     */
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

    /**
     * Check access untuk datatables processing.
     */
    private function CHECK_DATATABLES_ACCESS_PROCESSOR()
    {
        if (! empty($_POST['draw']) && ! empty($_POST['columns'][0]['data']) && ! empty($_POST['length'])) {
            // Environment-aware logging for debugging
            if (app()->environment(['local', 'testing'])) {
                Log::debug('Datatables access check', ['has_injection' => !empty($this->objectInjection)]);
            }
        }
    }

    /**
     * Redirect Back After Submit Data Process.
     */
    private function routeBackAfterAction($function_name, $id = false)
    {
        $currentRoute = current_route();
        // Environment-aware logging for debugging
        if (app()->environment(['local', 'testing'])) {
            Log::debug('Route back after action', ['current_route' => $currentRoute, 'function' => $function_name, 'id' => $id]);
        }
        
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
        
        // Environment-aware logging for debugging
        if (app()->environment(['local', 'testing'])) {
            Log::debug('Redirect to', ['route' => $routeBack]);
        }
        return redirect($routeBack);
    }
}