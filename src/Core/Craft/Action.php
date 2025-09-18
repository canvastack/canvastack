<?php

namespace Canvastack\Canvastack\Core\Craft;

use Canvastack\Canvastack\Core\Craft\Action\CrudOperations;
use Canvastack\Canvastack\Core\Craft\Action\FileOperations;
use Canvastack\Canvastack\Core\Craft\Action\DataOperations;
use Canvastack\Canvastack\Core\Craft\Action\ValidationHandler;
use Canvastack\Canvastack\Library\Components\Utility\Db\DynamicDeleteTrait;

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
 * 
 * Trait ini telah direfactor menjadi beberapa trait yang lebih fokus:
 * - CrudOperations: Operasi CRUD dan manajemen model
 * - FileOperations: Operasi upload dan manajemen file
 * - DataOperations: Export data dan filtering
 * - ValidationHandler: Validasi dan redirect handling
 */
trait Action
{
    use CrudOperations;
    use FileOperations;
    use DataOperations;
    use ValidationHandler;
    use DynamicDeleteTrait;
    
    // Shared properties untuk semua operations
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
    protected $actionService;

    /**
     * Initialize Action trait dengan dependencies yang diperlukan.
     * Method ini dapat dipanggil dari controller untuk setup awal.
     */
    protected function initializeAction()
    {
        // Initialize shared properties jika diperlukan
        if (empty($this->model)) {
            $this->model = [];
        }
        
        if (empty($this->validations)) {
            $this->validations = [];
        }
    }

    /**
     * Get current model instance.
     * Helper method untuk mendapatkan model yang sedang aktif.
     *
     * @return mixed
     */
    public function getCurrentModel()
    {
        return $this->model_data ?? null;
    }

    /**
     * Check if current model is soft deleted.
     *
     * @return bool
     */
    public function isSoftDeleted()
    {
        return $this->is_softdeleted;
    }

    /**
     * Get stored ID from last operation.
     *
     * @return mixed
     */
    public function getStoredId()
    {
        return $this->stored_id;
    }

    /**
     * Set model path untuk operasi dinamis.
     *
     * @param string $path
     * @return $this
     */
    public function setModelPath($path)
    {
        $this->model_path = $path;
        return $this;
    }

    /**
     * Set model table untuk operasi dinamis.
     *
     * @param string $table
     * @return $this
     */
    public function setModelTable($table)
    {
        $this->model_table = $table;
        return $this;
    }

    /**
     * Reset semua properties ke state awal.
     * Berguna untuk cleanup setelah operasi selesai.
     */
    public function resetActionState()
    {
        $this->model_id = null;
        $this->model_data = null;
        $this->model_original = null;
        $this->stored_id = null;
        $this->is_softdeleted = false;
        $this->uploadTrack = null;
        $this->filter_datatables_string = null;
    }
}