<?php

use Illuminate\Support\Facades\DB;

/**
 * Created on 13 Apr 2021
 * Time Created : 04:05:22
 *
 * @filesource	Table.php
 *
 * @author    wisnuwidi@canvastack.com - 2021
 * @copyright wisnuwidi
 *
 * @email     wisnuwidi@canvastack.com
 */
if (! function_exists('canvastack_filter_data_normalizer')) {

    /**
     * Normalizing Data Filters
     *
     * @param  array  $filters
     * @return array
     */
    function canvastack_filter_data_normalizer($filters = [])
    {
        return \Canvastack\Canvastack\Library\Components\Utility\Canvatility::filterNormalize((array) $filters);
    }
}

if (! function_exists('canvastack_get_model_table')) {

    /**
     * Get Table Name From Data Model
     *
     * @param  object  $model
     * @param  bool  $find
     * @return object|array
     */
    function canvastack_get_model_table($model, $find = false)
    {
        $model = canvastack_get_model($model, $find);

        return $model->getTable();
    }
}

if (! function_exists('canvastack_get_all_tables')) {

    /**
     * Get All Table Lists From Host Connection
     *
     * @param  string  $connection
     * @return object|array
     */
    function canvastack_get_all_tables($connection = null)
    {
        return \Canvastack\Canvastack\Library\Components\Utility\Canvatility::getAllTables($connection);
    }
}

if (! function_exists('canvastack_set_connection_separator')) {

    /**
     * Set Separator Connection
     *
     * @param  string  $separator
     * @return array
     */
    function canvastack_set_connection_separator($separator = '--diycon--')
    {
        return $separator;
    }
}

if (! function_exists('canvastack_check_table_columns')) {

    /**
     * Check if Table Column(s) Exist
     *
     * @param  string  $field_name
     * @return array
     */
    function canvastack_check_table_columns($table_name, $field_name, $db_connection = null)
    {
        return \Canvastack\Canvastack\Library\Components\Utility\Canvatility::hasColumn($table_name, $field_name, $db_connection);
    }
}

if (! function_exists('canvastack_get_table_columns')) {

    /**
     * Get Table Column(s)
     *
     * @param  string  $table_name
     * @return array
     */
    function canvastack_get_table_columns($table_name, $db_connection = null)
    {
        return \Canvastack\Canvastack\Library\Components\Utility\Canvatility::getColumns($table_name, $db_connection);
    }
}

if (! function_exists('canvastack_get_table_column_type')) {

    /**
     * Get Table Column(s)
     *
     * @param  string  $table_name
     * @param  string  $field_name
     * @return string
     */
    function canvastack_get_table_column_type($table_name, $field_name, $db_connection = null)
    {
        return \Canvastack\Canvastack\Library\Components\Utility\Canvatility::getColumnType($table_name, $field_name, $db_connection);
    }
}

if (! function_exists('canvastack_temp_table')) {

    /**
     * Create Temporary Table (delegated)
     */
    function canvastack_temp_table($table_name, $sql, $strict = true, $conn = 'mysql')
    {
        return \Canvastack\Canvastack\Library\Components\Utility\Db\TempTable::create((string) $table_name, (string) $sql, (bool) $strict, (string) $conn);
    }
}

if (! function_exists('canvastack_model_processing_table')) {

    /**
     * Call Model Process Data Table (delegated)
     */
    function canvastack_model_processing_table($data, $name)
    {
        return \Canvastack\Canvastack\Library\Components\Utility\Db\ModelProcessor::process((array) $data, (string) $name);
    }
}

if (! function_exists('canvastack_set_formula_columns')) {

    function canvastack_set_formula_columns($columns, $data)
    {
        // Delegate to Utility with added validations while preserving legacy insertion rules
        return \Canvastack\Canvastack\Library\Components\Utility\Table\FormulaColumns::set((array) $columns, (array) $data);
    }
}

if (! function_exists('canvastack_modal_content_html')) {

    function canvastack_modal_content_html($name, $title, $elements)
    {
        // Delegate to Utility to centralize HTML building while preserving markup
        return \Canvastack\Canvastack\Library\Components\Utility\Canvatility::modalContentHtml($name, $title, (array) $elements);
    }
}

if (! function_exists('canvastack_clear_json')) {

    function canvastack_clear_json($data)
    {
        return \Canvastack\Canvastack\Library\Components\Utility\Canvatility::clearJson((string) $data);
    }
}

if (! function_exists('canvastack_table_action_button')) {

    /**
     * Set Action Button URL Used For create_action_buttons() Function
     *
     * created @Sep 6, 2018
     * author: wisnuwidi
     *
     * @param  array  $row_data
     * @param  string  $current_url
     * @param  bool|array  $action
     * 	: true,
     * 	: false,
     * 	: index|insert|update|delete,
     * 	: show|create|modify|destroy,
     * 	: [index, insert, update, delete],
     * 	: [show, create, modify, destroy]
     * @return string
     */
    function canvastack_table_action_button($row_data, $field_target, $current_url, $action, $removed_button = null)
    {
        return \Canvastack\Canvastack\Library\Components\Utility\Canvatility::tableActionButtons($row_data, (string) $field_target, (string) $current_url, $action, $removed_button);
    }
}

if (! function_exists('canvastack_add_action_button_by_string')) {

    function canvastack_add_action_button_by_string($action, $is_array = false)
    {
        // Delegate to Utility to centralize logic
        return \Canvastack\Canvastack\Library\Components\Utility\Canvatility::addActionButtonByString($action, $is_array);
    }
}

if (! function_exists('create_action_buttons')) {

    /**
     * Action Button(s) Builder
     *
     * created @Sep 6, 2018
     * author: wisnuwidi
     *
     * @param  string  $view
     * @param  string  $edit
     * @param  string  $delete
     * @param  string  $add_action
     * @param  string  $as_root
     * @return string
     */
    function create_action_buttons($view = false, $edit = false, $delete = false, $add_action = [], $as_root = false)
    {
        // Delegate to Utility to centralize HTML building while preserving markup
        return \Canvastack\Canvastack\Library\Components\Utility\Canvatility::createActionButtons($view, $edit, $delete, $add_action, $as_root);
    }
}

if (! function_exists('canvastack_table_row_attr')) {
    /**
     * Set Default Row Attributes for Table
     *
     * @param  string  $str_value
     * @param  string  $attributes
     * 		=> colspan=2|id=idLists OR ['colspan' => 2, 'id' => 'idLists']
     * @return string
     */
    function canvastack_table_row_attr($str_value, $attributes)
    {
        return \Canvastack\Canvastack\Library\Components\Utility\Canvatility::tableRowAttr((string) $str_value, $attributes);
    }
}

if (! function_exists('canvastack_attributes_to_string')) {
    /**
     * Convert attributes array to HTML attribute string (delegated generic helper).
     */
    function canvastack_attributes_to_string($attributes)
    {
        return \Canvastack\Canvastack\Library\Components\Utility\Canvatility::attributesToString($attributes);
    }
}

if (! function_exists('canvastack_generate_table')) {

    /**
     * Table Builder
     *
     * @param  string  $title
     * @param  string  $title_id
     * @param  array  $header
     * @param  array  $body
     * @param  array  $attributes
     * @param  string  $numbering
     * @param  string  $containers
     * 		: draw <div> container box, defalult true
     * @param  string  $server_side
     * @param  bool|string|array  $server_side_custom_url
     * @return string
     */
    function canvastack_generate_table($title = false, $title_id = false, $header = [], $body = [], $attributes = [], $numbering = false, $containers = true, $server_side = false, $server_side_custom_url = false)
    {
        // Delegate to Utility Html TableUi for centralized renderer preserving markup
        return \Canvastack\Canvastack\Library\Components\Utility\Canvatility::generateTable($title, $title_id, (array) $header, (array) $body, (array) $attributes, $numbering, $containers, $server_side, $server_side_custom_url);
    }

    function tableColumn($header, $hIndex, $hList)
    {
        // Delegate to Utility to centralize column header rendering
        return \Canvastack\Canvastack\Library\Components\Utility\Canvatility::tableColumn((array) $header, (int) $hIndex, $hList);
    }
}

if (! function_exists('canvastack_draw_query_map_page_table')) {

    function canvastack_draw_query_map_page_table($name, $field_id, $value_id, $data, $buffers, $fieldbuff)
    {
        // Delegate to Utility Html MappingUi renderer for parity with legacy output
        return \Canvastack\Canvastack\Library\Components\Utility\Html\MappingUi::render([
            'name' => (string) $name,
            'field_id' => (string) $field_id,
            'value_id' => (string) $value_id,
            'data' => (array) $data,
            'buffers' => $buffers,
            'fieldbuff' => $fieldbuff,
        ]);
    }
}