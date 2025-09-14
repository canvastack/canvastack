<?php

namespace Canvastack\Canvastack\Services;

use App\Http\Requests\ActionFormRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Service untuk menangani operasi Action di CanvaStack.
 * Memisahkan logic CRUD dari trait untuk better maintainability.
 */
class ActionService
{
    /**
     * @var Model
     */
    protected Model $model;

    /**
     * @var array
     */
    protected array $validations;

    /**
     * @var bool
     */
    protected bool $softDeletedModel;

    /**
     * Constructor.
     *
     * @param Model $model
     * @param array $validations
     * @param bool $softDeletedModel
     */
    public function __construct(Model $model, array $validations = [], bool $softDeletedModel = false)
    {
        $this->model = $model;
        $this->validations = $validations;
        $this->softDeletedModel = $softDeletedModel;
    }

    /**
     * Handle store operation.
     *
     * @param ActionFormRequest $request
     * @return int|mixed
     * @throws \Exception
     */
    public function handleStore(ActionFormRequest $request)
    {
        $request->setRules($this->validations);

        $data = $this->checkFileInputSubmited($request);

        return canvastack_insert($this->model, $data, true);
    }

    /**
     * Handle update operation.
     *
     * @param ActionFormRequest $request
     * @param int $id
     * @return int
     * @throws \Exception
     */
    public function handleUpdate(ActionFormRequest $request, int $id)
    {
        $request->setRules($this->validations);

        $model = $this->getModel($id);

        $data = $this->checkFileInputSubmited($request);

        canvastack_update($model, $data);

        return $id;
    }

    /**
     * Handle delete operation.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return void
     * @throws \Exception
     */
    public function handleDestroy(\Illuminate\Http\Request $request, int $id)
    {
        $model = $this->getModel($id);

        canvastack_delete($request, $model, $id);
    }

    /**
     * Get model instance with optional find.
     *
     * @param mixed $find
     * @return Model|null
     */
    protected function getModel($find = false)
    {
        if (false !== $find) {
            try {
                return $this->model->findOrFail($find);
            } catch (ModelNotFoundException $e) {
                if ($this->softDeletedModel) {
                    try {
                        return $this->model->withTrashed()->findOrFail($find);
                    } catch (ModelNotFoundException $e2) {
                        Log::error('Model not found with ID: ' . $find, ['trace' => $e2->getTraceAsString()]);
                        abort(404, 'Resource not found.');
                    }
                }
                Log::error('Model not found with ID: ' . $find, ['trace' => $e->getTraceAsString()]);
                abort(404, 'Resource not found.');
            }
        }
        return $this->model;
    }

    /**
     * Check and handle file input.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    protected function checkFileInputSubmited(\Illuminate\Http\Request $request)
    {
        // Integrasi dengan FileUpload trait atau custom logic
        // Untuk sekarang, return $request->all(); nanti sesuaikan dengan implementasi
        return $request->all();
    }

    /**
     * Set model.
     *
     * @param Model $model
     * @return self
     */
    public function setModel(Model $model): self
    {
        $this->model = $model;
        return $this;
    }
}