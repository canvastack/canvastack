<?php

namespace Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Columns;

use Canvastack\Canvastack\Core\Craft\Includes\SafeLogger;

/**
 * FixedColumnsManager
 *
 * Purpose: manage fixed columns (left/right) flags in legacy variables array
 * exactly as implemented in Objects.php, preserving behavior.
 */
final class FixedColumnsManager
{
    /**
     * Set fixed columns positions (left/right) when provided.
     */
    public static function setFixedColumns(array &$variables, $leftPos = null, $rightPos = null): void
    {
        if (app()->environment(['local', 'testing'])) {
            SafeLogger::debug('FixedColumnsManager: Setting fixed columns', [
                'left_position' => $leftPos,
                'right_position' => $rightPos,
                'has_left' => !empty($leftPos),
                'has_right' => !empty($rightPos)
            ]);
        }

        if (! empty($leftPos)) {
            $variables['fixed_columns']['left'] = $leftPos;
        }
        if (! empty($rightPos)) {
            $variables['fixed_columns']['right'] = $rightPos;
        }
    }

    /**
     * Clear previously set fixed columns.
     */
    public static function clearFixedColumns(array &$variables): void
    {
        if (! empty($variables['fixed_columns'])) {
            unset($variables['fixed_columns']);
        }
    }
}
