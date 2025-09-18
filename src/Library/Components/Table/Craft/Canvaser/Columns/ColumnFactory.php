<?php

namespace Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Columns;

use Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Contracts\ColumnFactoryInterface;
use Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Contracts\TableContext;
use Canvastack\Canvastack\Core\Craft\Includes\SafeLogger;

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
        if (app()->environment(['local', 'testing'])) {
            SafeLogger::debug('ColumnFactory: Applying row detectors', [
                'row_model' => is_object($rowModel) ? get_class($rowModel) : gettype($rowModel)
            ]);
        }

        // 1) Image columns (preserves legacy behavior via ImageColumnRenderer)
        ImageColumnRenderer::apply($datatables, $rowModel);

        if (app()->environment(['local', 'testing'])) {
            SafeLogger::debug('ColumnFactory: Row detectors applied successfully');
        }
    }

    public function applyFormatters($datatables, TableContext $context): void
    {
        if (app()->environment(['local', 'testing'])) {
            SafeLogger::debug('ColumnFactory: Applying formatters', [
                'table_name' => $context->tableName ?? 'unknown',
                'context_type' => get_class($context)
            ]);
        }

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

        if (app()->environment(['local', 'testing'])) {
            SafeLogger::debug('ColumnFactory: Formatters and row attributes applied successfully');
        }
    }
}
