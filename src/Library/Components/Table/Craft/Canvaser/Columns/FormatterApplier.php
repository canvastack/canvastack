<?php

namespace Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Columns;

use Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Contracts\TableContext;
use Canvastack\Canvastack\Core\Craft\Includes\SafeLogger;

/**
 * Apply column-level formatters: formula and format_data
 * Mirrors legacy behavior in Datatables orchestrator without changing output.
 */
final class FormatterApplier
{
    /** @param  \Yajra\DataTables\DataTableAbstract  $datatables */
    public static function apply($datatables, TableContext $ctx): void
    {
        $data = $ctx->data;
        $table = $ctx->tableName;

        if (app()->environment(['local', 'testing'])) {
            SafeLogger::debug('FormatterApplier: Starting column formatting', [
                'table_name' => $table,
                'has_formula' => !empty($data->datatables->formula[$table]),
                'has_format_data' => !empty($data->datatables->columns[$table]['format_data'])
            ]);
        }

        // 1) Formula mapping
        if (! empty($data->datatables->formula[$table])) {
            $data_formula = $data->datatables->formula[$table];
            // Update visible lists to include formula columns
            $data->datatables->columns[$table]['lists'] = canvastack_set_formula_columns(
                $data->datatables->columns[$table]['lists'],
                $data_formula
            );

            foreach ($data_formula as $formula) {
                $datatables->editColumn($formula['name'], function ($row) use ($formula) {
                    // Legacy-compatible Formula evaluation
                    $logic = new \Canvastack\Canvastack\Library\Components\Table\Craft\Formula($formula, $row);

                    return $logic->calculate();
                });
            }
        }

        // 2) Data formatting mapping
        if (! empty($data->datatables->columns[$table]['format_data'])) {
            $data_format = $data->datatables->columns[$table]['format_data'];

            foreach ($data_format as $field => $format) {
                $datatables->editColumn($format['field_name'], function ($row) use ($field, $format) {
                    if ($field === $format['field_name']) {
                        // Support both Eloquent model and Builder row array
                        $dataValue = method_exists($row, 'getAttributes') ? $row->getAttributes() : (array) $row;
                        if (! empty($dataValue[$field])) {
                            return canvastack_format(
                                $dataValue[$field],
                                $format['decimal_endpoint'],
                                $format['separator'],
                                $format['format_type']
                            );
                        }
                    }
                });
            }
        }
    }
}
