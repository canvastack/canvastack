<?php

namespace Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Lists;

class TableNameResolver
{
    /**
     * Resolve table name from provided value and variables state.
     * Mutates $variables and $params (legacy-compatible) via references.
     */
    public static function resolve(?string $table_name, array &$variables, array &$params, array &$modelProcessing): ?string
    {
        if (! empty($variables['model_processing'])) {
            if ($table_name !== $variables['model_processing']['table']) {
                $table_name = $variables['model_processing']['table'];
            }
            $modelProcessing[$table_name] = $variables['model_processing'];
        }

        if (null === $table_name) {
            if (! empty($variables['table_data_model'])) {
                if ('sql' === $variables['table_data_model']) {
                    $sql = $variables['query'];
                    $table_name = canvastack_get_table_name_from_sql($sql);
                    $params[$table_name]['query'] = $sql;
                } else {
                    $table_name = canvastack_get_model_table($variables['table_data_model']);
                }
            }
            $variables['table_name'] = $table_name;
        }

        return $table_name;
    }
}