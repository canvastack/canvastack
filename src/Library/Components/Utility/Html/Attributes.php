<?php

namespace Canvastack\Canvastack\Library\Components\Utility\Html;

class Attributes
{
    /**
     * Convert attributes array (or scalar) to HTML attribute string.
     * Examples: ['id' => 'tbl', 'class' => 'x'] => 'id="tbl" class="x"'
     * - true boolean values output as bare attribute (e.g., disabled)
     * - null values are skipped
     * - non-array inputs are trimmed and returned as-is (BC behavior)
     */
    public static function toString($attributes): string
    {
        if (!is_array($attributes)) {
            return trim((string) $attributes);
        }

        $parts = [];
        foreach ($attributes as $key => $value) {
            if (is_bool($value)) {
                if ($value === true) {
                    $parts[] = $key;
                }
                continue;
            }
            if ($value === null) {
                continue;
            }
            $parts[] = $key.'="'.(string) $value.'"';
        }
        return implode(' ', $parts);
    }
}