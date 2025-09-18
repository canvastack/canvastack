<?php

namespace Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Columns;

use Canvastack\Canvastack\Core\Craft\Includes\SafeLogger;

/**
 * ColumnsConfigurator
 *
 * Purpose: centralize columns-related variable mutations used by legacy Objects.php
 * without changing behavior. All methods write into the provided $variables array
 * exactly like legacy implementation.
 */
final class ColumnsConfigurator
{
    /**
     * Merge multiple columns into a single logical column label.
     */
    public static function mergeColumns(array &$variables, string $label, array $mergedColumns = [], string $labelPosition = 'top'): void
    {
        if (app()->environment(['local', 'testing'])) {
            SafeLogger::debug('ColumnsConfigurator: Merging columns', [
                'label' => $label,
                'merged_columns_count' => count($mergedColumns),
                'label_position' => $labelPosition
            ]);
        }

        $variables['merged_columns'][$label] = [
            'position' => $labelPosition,
            'counts' => count($mergedColumns),
            'columns' => $mergedColumns,
        ];
    }

    /**
     * Hide specific columns (by field name).
     */
    public static function setHiddenColumns(array &$variables, array $fields = []): void
    {
        $variables['hidden_columns'] = $fields;
    }

    /**
     * Force columns to be treated as raw HTML strings.
     * Accepts string or array; normalizes to unique string values.
     */
    public static function forceRawColumns(array &$variables, $fields): void
    {
        if (is_string($fields)) {
            $fields = [$fields];
        }
        $variables['raw_columns_forced'] = array_values(
            array_unique(array_map('strval', (array) $fields))
        );
    }

    /**
     * Mark columns as image fields; typically rendered via <img> downstream.
     * Accepts string or array; normalizes to unique string values.
     */
    public static function setFieldAsImage(array &$variables, $fields): void
    {
        if (is_string($fields)) {
            $fields = [$fields];
        }
        $variables['image_fields'] = array_values(
            array_unique(array_map('strval', (array) $fields))
        );
    }
}
