<?php

namespace Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Lists;

class FieldsAndLabelsParser
{
    /**
     * Parse fields with optional inline labels using colon separator (e.g., "name:Nama").
     * Mutates $labels via reference (legacy-compatible).
     * Returns [normalizedFields, fieldsetAdded]
     */
    public static function parse(array $fields, array &$labels): array
    {
        $recola = [];
        foreach ($fields as $icol => $cols) {
            if (canvastack_string_contained($cols, ':')) {
                $split_cname = explode(':', $cols);
                $labels[$split_cname[0]] = $split_cname[1];
                $recola[$icol] = $split_cname[0];
            } else {
                $recola[$icol] = $cols;
            }
        }
        return [$recola, $recola];
    }
}