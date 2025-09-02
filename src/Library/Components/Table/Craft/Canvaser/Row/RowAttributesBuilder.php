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
            if (count($data->datatables->columns[$table]['clickable']) >= 1) {
                $rlp = function ($model) {
                    return canvastack_unescape_html(encode_id(intval($model->id)));
                };
            }
            $rowAttributes['class'] = 'row-list-url';
            $rowAttributes['rlp'] = $rlp;
        }

        $datatables->setRowAttr($rowAttributes);
    }
}
