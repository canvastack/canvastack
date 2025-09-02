<?php

namespace Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Traits;

use Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Columns\ColumnsConfigurator;

/**
 * ColumnsConfigTrait
 *
 * Grouping helpers to configure/inspect column meta.
 * Extracted from Objects without behavior changes.
 */
trait ColumnsConfigTrait
{
    /**
     * Merge Columns
     */
    public function mergeColumns($label, $merged_columns = [], $label_position = 'top')
    {
        ColumnsConfigurator::mergeColumns($this->variables, $label, $merged_columns, $label_position);
    }

    /**
     * Hidden columns setter.
     */
    public function setHiddenColumns($fields = [])
    {
        ColumnsConfigurator::setHiddenColumns($this->variables, $fields);
    }

    /**
     * Force columns to be treated as raw HTML.
     */
    public function forceRawColumns($fields)
    {
        ColumnsConfigurator::forceRawColumns($this->variables, $fields);

        return $this;
    }

    /**
     * Mark columns as image fields; flagged as raw.
     */
    public function setFieldAsImage($fields)
    {
        ColumnsConfigurator::setFieldAsImage($this->variables, $fields);

        return $this;
    }

    /**
     * Getters
     */
    public function getForcedRawColumns(): array
    {
        return (array) ($this->variables['raw_columns_forced'] ?? []);
    }

    public function getImageFields(): array
    {
        return (array) ($this->variables['image_fields'] ?? []);
    }
}
