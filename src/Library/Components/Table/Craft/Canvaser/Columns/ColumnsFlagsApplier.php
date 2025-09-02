<?php

namespace Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Columns;

class ColumnsFlagsApplier
{
    /**
     * Apply flags/attributes from variables into columns meta for given table.
     * Mutates $columns and $variables (for arrays that must be reset) by reference.
     */
    public static function apply(string $table_name, array &$columns, array &$variables): void
    {
        if (! isset($columns[$table_name])) {
            $columns[$table_name] = [];
        }

        if (! empty($variables['text_align'])) {
            $columns[$table_name]['align'] = $variables['text_align'];
        }
        if (! empty($variables['merged_columns'])) {
            $columns[$table_name]['merge'] = $variables['merged_columns'];
        }
        if (! empty($variables['orderby_column'])) {
            $columns[$table_name]['orderby'] = $variables['orderby_column'];
        }
        if (! empty($variables['clickable_columns'])) {
            $columns[$table_name]['clickable'] = $variables['clickable_columns'];
        }
        if (! empty($variables['sortable_columns'])) {
            $columns[$table_name]['sortable'] = $variables['sortable_columns'];
        }
        if (! empty($variables['searchable_columns'])) {
            $columns[$table_name]['searchable'] = $variables['searchable_columns'];
        }
        if (! empty($variables['filter_groups'])) {
            $columns[$table_name]['filter_groups'] = $variables['filter_groups'];
        }
        if (! empty($variables['format_data'])) {
            $columns[$table_name]['format_data'] = $variables['format_data'];
        }
        if (! empty($variables['hidden_columns'])) {
            $columns[$table_name]['hidden_columns'] = $variables['hidden_columns'];
            $variables['hidden_columns'] = [];
        }
        if (! empty($variables['raw_columns_forced'])) {
            $columns[$table_name]['raw_columns_forced'] = $variables['raw_columns_forced'];
            $variables['raw_columns_forced'] = [];
        }
        if (! empty($variables['image_fields'])) {
            $columns[$table_name]['image_fields'] = $variables['image_fields'];
            $variables['image_fields'] = [];
        }
    }
}