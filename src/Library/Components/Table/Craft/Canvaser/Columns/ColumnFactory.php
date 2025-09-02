<?php

namespace Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Columns;

use Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Contracts\ColumnFactoryInterface;
use Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Contracts\TableContext;

/**
 * ColumnFactory — thin orchestrator over per-column modules/renderers.
 *
 * Goal: keep Datatables orchestrator thin by delegating column registrations
 * (image detection, formatters, row attributes & actions are handled by their
 * own modules; this factory only coordinates calls in the same order as before).
 */
final class ColumnFactory implements ColumnFactoryInterface
{
    public function applyRowDetectors($datatables, $rowModel): void
    {
        // 1) Image columns (preserves legacy behavior via ImageColumnRenderer)
        ImageColumnRenderer::apply($datatables, $rowModel);
    }

    public function applyFormatters($datatables, TableContext $context): void
    {
        // 2) Column formatting & formula
        \Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Columns\FormatterApplier::apply(
            $datatables,
            $context
        );

        // 3) Row attributes (clickable/rlp)
        \Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Row\RowAttributesBuilder::apply(
            $datatables,
            $context
        );
    }
}
