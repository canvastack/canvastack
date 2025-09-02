<?php

namespace Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Traits;

/**
 * AlignAndStyleTrait
 *
 * Text alignment, background color, width and table-level attributes helpers.
 */
trait AlignAndStyleTrait
{
    public function setAlignColumns(string $align, $columns = [], $header = true, $body = true)
    {
        $this->variables['text_align'][$align] = ['columns' => $columns, 'header' => $header, 'body' => $body];
    }

    public function setRightColumns($columns = [], $header = true, $body = true)
    {
        $this->setAlignColumns('right', $columns, $header, $body);
    }

    public function setCenterColumns($columns = [], $header = true, $body = false)
    {
        $this->setAlignColumns('center', $columns, $header, $body);
    }

    public function setLeftColumns($columns = [], $header = true, $body = true)
    {
        $this->setAlignColumns('left', $columns, $header, $body);
    }

    public function setBackgroundColor($color, $text_color = null, $columns = null, $header = true, $body = false)
    {
        $this->variables['background_color'][$color] = ['code' => $color, 'text' => $text_color, 'columns' => $columns, 'header' => $header, 'body' => $body];
    }

    public function setColumnWidth($field_name, $width = false)
    {
        $this->variables['column_width'][$field_name] = $width;
    }

    public function addAttributes($attributes = [])
    {
        $this->variables['add_table_attributes'] = $attributes;
    }

    public function setWidth(int $width, string $measurement = 'px')
    {
        return $this->addAttributes(['style' => "min-width:{$width}{$measurement};"]);
    }
}
