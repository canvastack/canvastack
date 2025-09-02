<?php

namespace Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Traits;

/**
 * Shared helpers for targeting all columns and validating column sets.
 */
trait ColumnSetTrait
{
    /**
     * Marker to indicate operations should target all columns
     */
    protected $all_columns = 'all::columns';

    /**
     * Normalize requested columns into an array understood by consumers.
     * - null => ['all::columns' => true]
     * - false => ['all::columns' => false]
     * - array|string => returned as-is
     *
     * @param  mixed  $columns
     * @return array|mixed
     */
    protected function checkColumnSet($columns)
    {
        if (empty($columns)) {
            if (false === $columns) {
                $value = [$this->all_columns => false];
            } else {
                $value = [$this->all_columns => true];
            }
        } else {
            $value = $columns;
        }

        return $value;
    }
}
