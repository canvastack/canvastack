<?php

namespace Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Row;

use Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Contracts\TableContext;

/**
 * Apply row attributes (clickable class & rlp) as in legacy orchestrator.
 */
final class RowAttributesBuilder
{
    /** @param  \Yajra\DataTables\DataTableAbstract  $datatables */
    public static function apply($datatables, TableContext $ctx): void
    {
        $data = $ctx->data;
        $table = $ctx->tableName;

        $rlp = false;
        $rowAttributes = ['class' => null, 'rlp' => null];

        if (! empty($data->datatables->columns[$table]['clickable'])) {
            $clickable = $data->datatables->columns[$table]['clickable'];
            
            // Handle both array and boolean clickable values
            if (is_array($clickable) && count($clickable) >= 1) {
                $rlp = function ($model) {
                    return canvastack_unescape_html(encode_id(intval($model->id)));
                };
            } elseif (is_bool($clickable) && $clickable) {
                $rlp = function ($model) {
                    return canvastack_unescape_html(encode_id(intval($model->id)));
                };
            }
            
            // CRITICAL: Add both row-list-url and clickable classes for proper functionality
            $rowAttributes['class'] = 'row-list-url clickable';
            $rowAttributes['rlp'] = $rlp;
        }

        $datatables->setRowAttr($rowAttributes);
    }
}
