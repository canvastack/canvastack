<?php

namespace Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Contracts;

interface ColumnFactoryInterface
{
    /**
     * Apply row-based detectors/registrations that depend on a sample row.
     * Example: image columns detection and editColumn registration.
     *
     * @param  \Yajra\DataTables\DataTableAbstract  $datatables
     * @param  object  $rowModel
     */
    public function applyRowDetectors($datatables, $rowModel): void;

    /**
     * Apply global column formatters/registrations that depend on table context only.
     * Example: formatter applier, formula columns, etc.
     *
     * @param  \Yajra\DataTables\DataTableAbstract  $datatables
     */
    public function applyFormatters($datatables, TableContext $context): void;
}
