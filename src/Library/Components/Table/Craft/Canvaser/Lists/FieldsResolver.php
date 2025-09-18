<?php

namespace Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Lists;

use Canvastack\Canvastack\Core\Craft\Includes\SafeLogger;

class FieldsResolver
{
    /**
     * Resolve fields list including existence checks and modelProcessing fallback.
     * $context is an associative array to access required callbacks and state.
     * Required keys:
     * - connection (string)
     * - variables (array ref)
     * - modelProcessing (array ref)
     */
    public static function resolve(string $table_name, array $fields, array $fieldset_added, array &$context): array
    {
        if (app()->environment(['local', 'testing'])) {
            SafeLogger::debug('FieldsResolver: Resolving fields for table', [
                'table_name' => $table_name,
                'fields_count' => count($fields),
                'fieldset_added_count' => count($fieldset_added),
                'connection' => $context['connection'] ?? 'mysql'
            ]);
        }

        $connection = $context['connection'] ?? 'mysql';
        $variables =& $context['variables'];
        $modelProcessing =& $context['modelProcessing'];

        if (! empty($fields)) {
            if (! canvastack_string_contained($table_name, 'view_')) {
                $fields = self::checkColumnExist($table_name, $fields, $connection);

                if (empty($fields) && ! empty($modelProcessing)) {
                    if (! empty($fieldset_added)) {
                        $fields = $fieldset_added;
                    }
                    if (! canvastack_schema('hasTable', $table_name)) {
                        canvastack_model_processing_table($modelProcessing, $table_name);
                    }
                    $fields = canvastack_get_table_columns($table_name);
                }
            }
        } elseif (! empty($variables['table_fields'])) {
            $fields = self::checkColumnExist($table_name, $variables['table_fields'], $connection);
        } else {
            $fields = canvastack_get_table_columns($table_name, $connection);
            if (empty($fields) && ! empty($modelProcessing)) {
                if (! canvastack_schema('hasTable', $table_name)) {
                    canvastack_model_processing_table($modelProcessing, $table_name);
                }
                $fields = canvastack_get_table_columns($table_name);
            }
        }

        return $fields;
    }

    private static function checkColumnExist(string $table_name, array $fields, string $connection): array
    {
        $fieldset = [];
        foreach ($fields as $field) {
            if (canvastack_check_table_columns($table_name, $field, $connection)) {
                $fieldset[] = $field;
            }
        }
        return $fieldset;
    }
}