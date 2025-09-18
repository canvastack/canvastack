<?php

namespace Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Lists;

use Canvastack\Canvastack\Core\Craft\Includes\SafeLogger;

class FieldsAndLabelsParser
{
    /**
     * Parse fields with optional inline labels using colon separator (e.g., "name:Nama").
     * Mutates $labels via reference (legacy-compatible).
     * Returns [normalizedFields, fieldsetAdded]
     */
    public static function parse(array $fields, array &$labels): array
    {
        if (app()->environment(['local', 'testing'])) {
            SafeLogger::debug('FieldsAndLabelsParser: Parsing fields with labels', [
                'fields_count' => count($fields),
                'existing_labels_count' => count($labels)
            ]);
        }

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