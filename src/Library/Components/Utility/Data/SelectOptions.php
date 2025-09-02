<?php

namespace Canvastack\Canvastack\Library\Components\Utility\Data;

final class SelectOptions
{
    /**
     * Build options array suitable for select boxes from data iterable
     * Supports key_label in form "fieldA|fieldB" => concatenated by ' - '
     */
    public static function fromData($object, $key_value, $key_label, $set_null_array = true): array
    {
        $options = [0 => ''];
        if ($set_null_array === true) {
            $options[] = '';
        }

        $keyLabels = [];
        if (is_string($key_label) && str_contains($key_label, '|')) {
            $keyLabels = explode('|', $key_label);
        } else {
            $keyLabels[] = $key_label;
        }

        foreach ($object as $row) {
            $label = isset($row[$keyLabels[0]]) ? $row[$keyLabels[0]] : null;
            if (!empty($keyLabels[1]) && isset($row[$keyLabels[1]]) && $row[$keyLabels[1]] !== '') {
                $label = $row[$keyLabels[0]].' - '.$row[$keyLabels[1]];
            }
            $options[$row[$key_value]] = $label;
        }

        return $options;
    }
}