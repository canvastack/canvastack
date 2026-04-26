<?php
namespace Canvastack\Canvastack\Models\Core;

use Illuminate\Database\Eloquent\Model as BaseModel;
/**
 * Created on Dec 16, 2022
 * 
 * Time Created : 12:27:59 AM
 * Filename     : Model.php
 *
 * @filesource Model.php
 *
 * @author     wisnuwidi @CanvaStack - 2022
 * @copyright  wisnuwidi
 * @email      wisnuwidi@canvastack.com
 */
 
class Model extends BaseModel {
    /**
     * Get the current connection name for the model.
     * 
     * @return string|null
     */
    public function getConnectionName()
    {
        return $this->connection;
    }
}