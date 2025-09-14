<?php

namespace Canvastack\Canvastack\Library\Components\Table\Craft;

use Canvastack\Canvastack\Library\Components\Table\Craft\Method\Post;
use Canvastack\Canvastack\Models\Admin\System\DynamicTables;

/**
 * Created on 21 Apr 2021
 * Time Created	: 08:13:39
 *
 * @filesource	Builder.php
 *
 * @author		wisnuwidi@canvastack.com - 2021
 * @copyright	wisnuwidi
 *
 * @email		wisnuwidi@canvastack.com
 */
class Builder
{
    use Scripts;

    public $model;

    public $method = 'GET';

    protected function setMethod($method)
    {
        $this->method = $method;
    }

    protected function table($name, $columns = [], $attributes = [], $label = null)
    {
        $data = [];

        if (! empty($attributes[$name]['model'])) {
            if ('sql' === $attributes[$name]['model']) {
                $data[$name]['model'] = 'sql';
                $data[$name]['sql'] = $attributes[$name]['query'];
            } else {
                $model = new $attributes[$name]['model']();
                $data[$name]['model'] = $attributes[$name]['model'];
            }
        } else {
            $model = new DynamicTables(null, $this->connection);
            $model->setTable($name);
            $data[$name]['model'] = get_class($model);
            $attributes[$name]['model'] = get_class($model);
        }

        if (! empty($model)) {
            $this->model[$name]['type'] = 'model';
            $this->model[$name]['source'] = $model;
        } else {
            $this->model[$name]['type'] = 'sql';
            $this->model[$name]['source'] = $data[$name]['sql'];
        }

        if (! empty($attributes[$name])) {
            $tableID = $attributes[$name]['attributes']['table_id'];
            $tableClass = $attributes[$name]['attributes']['table_class'];
            $this->serverSide = $attributes[$name]['server_side']['status'];
            $this->customURL = $attributes[$name]['server_side']['custom_url'];
        }

        $data[$name]['name'] = $name;
        $data[$name]['columns'] = $columns[$name];
        $data[$name]['attributes'] = $attributes[$name];

        // FORMULATION
        if (! empty($data[$name]['attributes']['conditions']['formula'])) {
            if (! empty($data[$name]['columns']['lists'])) {
                $data[$name]['columns']['lists'] = $this->setFormulaColumns($data[$name]['columns']['lists'], $data[$name]);
            }
        }

        // RENDER DATA TABLE
        if (false !== $name) {
            $list = null;
            if (canvastack_string_contained($label, ':setLabelTable')) {
                $list = null;
                $label = str_replace(':setLabelTable', '', $label);
            } else {
                $list = ' List(s)';
            }

            if (empty($label)) {
                $titleText = ucwords(str_replace('_', ' ', $name)).$list;
            } else {
                $titleText = ucwords(str_replace('_', ' ', $label)).$list;
            }
            $tableTitle = '<div class="panel-heading"><div class="pull-left"><h3 class="panel-title">'.$titleText.'</h3></div><div class="clearfix"></div></div>';
        }

        $baseTableAttributes = ['id' => $tableID, 'class' => $tableClass];
        $tableAttributes = $baseTableAttributes;
        if (! empty($attributes[$name]['attributes']['add_attributes'])) {
            $tableAttributes = array_merge_recursive($baseTableAttributes, $attributes[$name]['attributes']['add_attributes']);
        }

        $table = '<div class="panel-body no-padding">';
        $table .= '<table'.$this->setAttributes($tableAttributes).'>';
        $table .= $this->header($data[$name]);
        $table .= '</table>';
        $table .= '</div>';
        // RENDER DATA TABLE

        $datatable_columns = $this->body($data[$name]);
        $html = '<div class="row">';
        $html .= '<div class="col-md-12">';
        $html .= '<div class="panel">'.$tableTitle.'<br />';
        $html .= '<div class="relative diy-table-box-'.$tableID.'">';
        if (! empty($this->filter_contents[$tableID]['id']) && $tableID === $this->filter_contents[$tableID]['id']) {
            $html .= '<span class="diy-dt-search-box hide" id="diy-'.$tableID.'-search-box">'.$this->filterButton($this->filter_contents[$tableID]).'</span>';
            $html .= $this->filterModalbox($this->filter_contents[$tableID]);
        }
        $html .= $table.$datatable_columns;
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    private $columnManipulated = [];

    private function checkColumnLabel($check_labels, $columns)
    {
        $labels = [];
        foreach ($columns as $icol => $vcol) {
            if (! empty($this->labels[$vcol])) {
                $this->columnManipulated[$this->labels[$vcol]] = $vcol;
                $labels[$icol] = $this->labels[$vcol];
            } else {
                $this->columnManipulated[$vcol] = $vcol;
                $labels[$icol] = $vcol;
            }
        }

        return $labels;
    }

    private function header($data = [])
    {
        $columns = $data['columns'];
        $attributes = $data['attributes'];

        $sortable = false;
        if (! empty($data['columns']['sortable'])) {
            $sortable = $data['columns']['sortable'];
        }

        $actions = false;
        $numbering = false;
        $widthColumn = [];

        // COLUMN DATA MANIPULATION
        $attributes['sortable_columns'] = $sortable;
        $attributes['attributes']['column']['id'] = [];
        $attributes['attributes']['column']['class'] = [];
        if (! empty($attributes)) {
            if (! empty($attributes['actions'])) {
                $actions = $attributes['actions'];
            }
            if (! empty($attributes['numbering'])) {
                $numbering = $attributes['numbering'];
            }

            if (! empty($attributes['attributes']['column_width'])) {
                foreach ($attributes['attributes']['column_width'] as $column_name => $column_width) {
                    $widthColumn[$column_name] = $column_width;
                }
            }
        }

        $hiddenColumn = [];
        if (! empty($data['columns']['hidden_columns'])) {
            $hiddenColumn = $data['columns']['hidden_columns'];
        }

        $alignColumn = [];
        if (! empty($columns['align'])) {
            foreach ($columns['align'] as $align => $column_data) {
                if (true === $column_data['header']) {
                    foreach ($column_data['columns'] as $field) {
                        $alignColumn['header'][$field] = $align;
                    }
                }
            }
        }

        $mergeColumn = null;
        if (! empty($columns['merge'])) {
            // Manipulation Column Merged Label
            if (! empty($this->labels)) {
                $merged_labels = [];
                foreach ($columns['merge'] as $colmergename => $colmerged) {
                    $merged_labels[$colmergename]['position'] = $colmerged['position'];
                    $merged_labels[$colmergename]['counts'] = $colmerged['counts'];
                    $merged_labels[$colmergename]['columns'] = $this->checkColumnLabel($this->labels, $colmerged['columns']);
                }
                if (! empty($merged_labels)) {
                    $columns['merge'] = $merged_labels;
                }
            }

            $mergeColumn = $columns['merge'];
        }
        if (! empty($columns['lists'])) {
            $columns = $columns['lists'];
        }
        if (true === $numbering && ! in_array('id', $columns)) {
            $number = ['number_lists'];
            $columns = array_merge($number, $columns);
        }

        if (! empty($actions)) {
            array_push($columns, 'action');
        }

        if (! empty($this->labels)) {
            $columns = $this->checkColumnLabel($this->labels, $columns);
        }

        $dataColumns = [];
        if (! empty($this->columnManipulated)) {
            $dataColumns = $this->columnManipulated;
        }

        // COLUMN DATA MANIPULATION

        // COLORING BACKGROUD
        $columnColor = [];
        $headerColor = null;
        $bgColor = null;
        if (! empty($attributes['attributes']['bg_color'])) {
            $bgColor = $attributes['attributes']['bg_color'];
        }
        $tableColor = $this->backgroundColor($bgColor);
        if (! empty($tableColor['columns'])) {
            $columnColor = $tableColor['columns'];
        }
        if (! empty($tableColor['header'])) {
            $headerColor = $tableColor['header'];
        }
        // COLORING BACKGROUD

        // BUILD HTML TABLE
        if (! empty($mergeColumn)) {
            $headerTable = '<thead>';
        } else {
            $headerTable = '<thead><tr>';
        }

        if (! empty($columns)) {
            if (is_array($columns)) {
                if (! empty($mergeColumn)) {
                    // If any set field(s) merged
                    if (! empty($alignColumn['header'])) {
                        $attributes['attributes']['column']['class'] = array_merge_recursive($attributes['attributes']['column']['class'], $alignColumn['header']);
                    }
                    $headerTable .= $this->mergeColumns($mergeColumn, $columns, $attributes);
                } else {
                    // If no one set field(s) merged
                    foreach ($columns as $column) {
                        if (! empty($dataColumns)) {
                            $id = $this->setAttributes(['id' => canvastack_decrypt(canvastack_encrypt($dataColumns[$column]))]);
                        } else {
                            $id = $this->setAttributes(['id' => canvastack_decrypt(canvastack_encrypt($column))]);
                        }
                        $class = null;
                        $classAttributes = null;

                        if (in_array($column, $hiddenColumn)) {
                            $classAttributes .= ' diy-hide-column';
                        }
                        if (! empty($alignColumn['header'][$column])) {
                            $classAttributes .= $alignColumn['header'][$column];
                        }
                        if ('action' === strtolower($column)) {
                            $classAttributes .= ' diy-column-action';
                        }
                        if (! empty($classAttributes)) {
                            $class = $this->setAttributes(['class' => $classAttributes]);
                        }

                        $headerLabel = ucwords(str_replace('_', ' ', $column));
                        if ('no' === strtolower($column) || 'id' === strtolower($column) || 'nik' === strtolower($column)) {
                            $headerTable .= "<th width=\"50\"{$headerColor}>{$headerLabel}</th>";

                        } elseif ('number_lists' === strtolower($column)) {
                            $headerTable .= '<th width="30"'.$headerColor.'>No</th>';
                            $headerTable .= '<th width="30"'.$headerColor.'>ID</th>';

                        } else {
                            $width_column = null;
                            if (! empty($widthColumn[strtolower($column)])) {
                                $width_column = ' width="'.$widthColumn[strtolower($column)].'"';
                            }

                            if (! empty($columnColor[$column])) {
                                $headerTable .= "<th{$id}{$class}{$headerColor}{$columnColor[$column]}{$width_column}>{$headerLabel}</th>";
                            } else {
                                $headerTable .= "<th{$id}{$class}{$headerColor}{$width_column}>{$headerLabel}</th>";
                            }
                        }
                    }
                }
            }
        }

        if (! empty($mergeColumn)) {
            $headerTable .= '</thead>';
        } else {
            $headerTable .= '</tr></thead>';
        }
        // BUILD HTML TABLE

        return $headerTable;
    }

    private function mergeColumns($mergeColumn = [], $columns = [], $attributes = [])
    {
        $headerTable = null;
        $mergedTable = null;
        $setMergeText = '::merge::';

        $columns = $this->checkColumnLabel($this->labels, $columns);
        $dataColumns = $this->columnManipulated;

        $columnColor = [];
        $headerColor = null;
        if (! empty($attributes['attributes']['bg_color'])) {
            $tableColor = $this->backgroundColor($attributes['attributes']['bg_color']);
        }
        if (! empty($tableColor['columns'])) {
            $columnColor = $tableColor['columns'];
        }
        if (! empty($tableColor['header'])) {
            $headerColor = $tableColor['header'];
        }

        if (! empty($mergeColumn)) {
            $mergedTable .= '<tr>';
            foreach ($columns as $index => $column) {
                $columnClass = null;
                $headerLabel = ucwords(str_replace('_', ' ', $column));

                if (! empty($mergeColumn)) {
                    foreach ($mergeColumn as $mergeLabel => $mergeData) {
                        foreach ($mergeData['columns'] as $merge_column) {
                            if ($column === $merge_column) {
                                if (! empty($dataColumns[$column])) {
                                    $id = $this->setAttributes(['id' => canvastack_decrypt(canvastack_encrypt($dataColumns[$column]))]);
                                }
                                if (! empty($attributes['attributes']['column']['class'][$merge_column])) {
                                    $columnClass = $this->setAttributes(['class' => $attributes['attributes']['column']['class'][$merge_column]]);

                                    // coloring background
                                    if (! empty($columnColor[$column])) {
                                        $mergedTable .= "<th{$id}{$columnClass}{$headerColor}{$columnColor[$column]}>{$headerLabel}</th>";
                                    } else {
                                        $mergedTable .= "<th{$id}{$columnClass}{$headerColor}>{$headerLabel}</th>";
                                    }
                                } else {
                                    if (! empty($columnColor[$column])) {
                                        $mergedTable .= "<th{$id}{$headerColor}{$columnColor[$column]}>{$headerLabel}</th>";
                                    } else {
                                        $mergedTable .= "<th{$id}{$headerColor}>{$headerLabel}</th>";
                                    }
                                }

                                unset($columns[$index]);
                                $columns[$index] = $mergeLabel.$setMergeText.$mergeData['counts'];
                            }
                        }
                    }
                }
            }
            $mergedTable .= '</tr>';

            $columns = array_unique($columns);
            ksort($columns);

            $headerTable .= '<tr>';
            foreach ($columns as $index => $column) {
                $columnClass = null;
                $headerLabel = ucwords(str_replace('_', ' ', str_replace($setMergeText, '', $column)));

                if (! empty($dataColumns[$column])) {
                    $id = $this->setAttributes(['id' => canvastack_decrypt(canvastack_encrypt($dataColumns[$column]))]);
                }
                if (str_contains($column, $setMergeText)) {
                    $merge_label = explode($setMergeText, $column);
                    $colspan = intval($merge_label[1]);
                    $headerLabel = ucwords(str_replace('_', ' ', $merge_label[0]));
                    $headerTable .= "<th class=\"merge-column\" colspan=\"{$colspan}\"{$headerColor}>{$headerLabel}</th>";
                } else {
                    if ('no' === strtolower($column) || 'id' === strtolower($column) || 'nik' === strtolower($column)) {
                        $headerTable .= "<th rowspan=\"2\" width=\"50\"{$headerColor}>{$headerLabel}</th>";

                    } elseif ('number_lists' === strtolower($column)) {
                        $headerTable .= "<th rowspan=\"2\" width=\"30\"{$headerColor}>No</th><th rowspan=\"2\" width=\"30\"{$headerColor}>ID</th>";

                    } else {
                        $classAttributes = null;
                        if (! empty($attributes['attributes']['column']['class'][$column])) {
                            $classAttributes .= $attributes['attributes']['column']['class'][$column];
                        }
                        if ('action' === strtolower($column)) {
                            $classAttributes .= ' diy-column-action';
                        }
                        if (! empty($classAttributes)) {
                            $columnClass = $this->setAttributes(['class' => $classAttributes]);
                        }

                        $width_column = null;
                        if (! empty($attributes['attributes']['column_width'][strtolower($column)])) {
                            $width_column = ' width="'.$attributes['attributes']['column_width'][strtolower($column)].'"';
                        }

                        if (! empty($columnColor[$column])) {
                            $headerTable .= "<th rowspan=\"2\"{$id}{$columnClass}{$headerColor}{$columnColor[$column]}{$width_column}>{$headerLabel}</th>";
                        } else {
                            $headerTable .= "<th rowspan=\"2\"{$id}{$columnClass}{$headerColor}{$width_column}>{$headerLabel}</th>";
                        }
                    }
                }
            }
            $headerTable .= '</tr>';

            $headerTable .= $mergedTable;
        }

        return $headerTable;
    }

    private function setColumnElements($name, $column_data, $columns)
    {
        $element = [];
        if (! empty($column_data[$name])) {
            if (! empty($column_data[$name]['all::columns'])) {
                if (true === $column_data[$name]['all::columns']) {
                    if (! empty($columns['columns']['lists'])) {
                        foreach ($columns['columns']['lists'] as $clickList) {
                            $element[$clickList] = true;
                        }
                    }
                }
            } else {
                foreach ($column_data[$name] as $clicKey) {
                    $element[$clicKey] = true;
                }
            }
        }

        return $element;
    }

    private function setFormulaColumns($columns, $data)
    {
        return canvastack_set_formula_columns($columns, $data['attributes']['conditions']['formula']);
    }

    public $filter_contents = [];

    protected $filter_object = [];

    private function body($data = [])
    {
        $datatables = [];
        $name = $data['name'];
        $attributes = $data['attributes'];
        $columnData = $data['columns'];
        $server_side = $data['attributes']['server_side']['status'];

        $actions = false;
        $numbering = false;
        if (! empty($attributes['attributes']['table_id'])) {
            $tableID = $attributes['attributes']['table_id'];
        }
        if (! empty($attributes['actions'])) {
            $actions = $attributes['actions'];
        }
        if (! empty($attributes['numbering'])) {
            $numbering = $attributes['numbering'];
        }

        $hiddenColumn = [];
        if (! empty($data['columns']['hidden_columns'])) {
            $hiddenColumn = $data['columns']['hidden_columns'];
        }

        // COLUMN DATA MANIPULATION
        $columns = $columnData['lists'];
        if (true === $numbering) {
            $number = ['number_lists'];
            $columns = array_merge($number, $columns);
        }
        if (! empty($actions)) {
            array_push($columns, 'action');
        }

        // ALIGNMENTS
        $alignment = [];
        if (! empty($columnData['align'])) {
            foreach ($columnData['align'] as $align => $col_data) {
                if (true === $col_data['body']) {
                    foreach ($col_data['columns'] as $field) {
                        $alignment['body'][$field] = $align;
                    }
                }
            }
        }

        // SORTABLE
        $sortable = $this->setColumnElements('sortable', $columnData, $data);
        // SEARCHABLE
        $searchable = $this->setColumnElements('searchable', $columnData, $data);
        // CLICKABLE
        $clickable = $this->setColumnElements('clickable', $columnData, $data);

        $dt_columns = [];
        $jsonData = [];

        $column_id = [];
        if (false !== $server_side) {
            // CRITICAL: Always use 'id' for column_id, regardless of whether it's in visible columns
            // This ensures the hidden ID column always contains actual ID data, not other field data
            $column_id['data'] = 'id';
            $column_id['name'] = 'id';
            
            // Mark as hidden column if ID is not in the visible columns list
            if (! in_array('id', $columns)) {
                $column_id['visible'] = false;
                $column_id['searchable'] = false;
                $column_id['className'] = 'control hidden-column';
            }
        }

        $formula_fields = [];
        if (! empty($data['attributes']['conditions']['formula'])) {
            foreach ($data['attributes']['conditions']['formula'] as $formula) {
                $formula_fields[$formula['name']] = $formula['name'];
            }
        }

        foreach ($columns as $column) {
            $jsonData['data'] = $column;
            $jsonData['name'] = $column;
            $jsonData['sortable'] = false;
            $jsonData['searchable'] = false;
            $jsonData['class'] = 'auto-cut-text';
            $jsonData['onclick'] = 'return false';

            if (in_array($column, $hiddenColumn)) {
                $jsonData['class'] = 'auto-cut-text diy-hide-column';
            }

            $formula_column = null;
            if (! empty($formula_fields[$column])) {
                $formula_column = $formula_fields[$column];
            }

            if ('number_lists' === $column) {
                $columnName = 'DT_RowIndex';

                $jsonData['data'] = $columnName;
                $jsonData['name'] = $columnName;
                $jsonData['class'] = 'center un-clickable';

                $dt_columns[] = $jsonData;
                if (! empty($column_id)) {
                    $dt_columns[] = $column_id;
                }
                $jsonData = [];

            } elseif ($formula_column === $column) {
                $jsonData['data'] = $column;
                $jsonData['name'] = $column;

                if (! empty($alignment['body'][$column])) {
                    $jsonData['class'] = $jsonData['class']." {$alignment['body'][$column]}";
                }

                if (! empty($clickable[$column])) {
                    unset($jsonData['onclick']);
                    $jsonData['class'] = $jsonData['class'].' clickable';
                }

                $dt_columns[] = $jsonData;

            } else {
                $jsonData['data'] = $column;
                $jsonData['name'] = $column;

                if (! empty($alignment['body'][$column])) {
                    $jsonData['class'] = $jsonData['class']." {$alignment['body'][$column]}";
                }

                if (! empty($sortable[$column])) {
                    $jsonData['sortable'] = $sortable[$column];
                }
                if (! empty($searchable[$column])) {
                    $jsonData['searchable'] = $searchable[$column];
                }
                if (! empty($clickable[$column])) {
                    unset($jsonData['onclick']);
                    $jsonData['class'] = $jsonData['class'].' clickable';
                }

                $dt_columns[] = $jsonData;
            }
        }

        $new_data_columns = [];
        foreach ($dt_columns as $dtcols) {
            if ('DT_RowIndex' === $dtcols['name']) {
                $new_data_columns[] = 'number_lists';
            } else {
                $new_data_columns[] = $dtcols['name'];
            }
        }

        $dt_info = [];
        $dt_info['searchable'] = [];
        $dt_info['name'] = $name;
        
        // Add model data for POST method
        if (!empty($this->model[$name])) {
            $dt_info['model'] = [$name => $this->model[$name]];
        }
        
        // Add columns data for POST method
        if (!empty($data['columns'])) {
            $dt_info['columns'] = [$name => $data['columns']];
        }
        
        if (! empty($data['columns']['sortable'])) {
            $dt_info['sortable'] = $data['columns']['sortable'];
        }
        if (! empty($data['attributes']['conditions'])) {
            $dt_info['conditions'] = $data['attributes']['conditions'];
            $dt_info['columns'] = $new_data_columns;
        }

        if (! empty($data['attributes']['on_load']['display_limit_rows'])) {
            $dt_info['onload_limit_rows'] = $data['attributes']['on_load']['display_limit_rows'];
        }
        if (! empty($data['attributes']['fixed_columns'])) {
            $dt_info['fixed_columns'] = $data['attributes']['fixed_columns'];
        }

        $filter = false;
        if (! empty($searchable)) {
            $filter = true;
            $dt_info['searchable'] = $data['columns']['searchable'];

            if (! empty($data['columns']['filters'])) {
                $search_data = [];
                $search_data['table_name'] = $name;
                $search_data['searchable'] = $data['columns']['searchable'];
                $search_data['columns'] = $data['columns']['filters'];

                $search_data['relations'] = [];
                if (! empty($data['columns']['relations'])) {
                    $search_data['relations'] = $data['columns']['relations'];
                }

                $search_data['foreign_keys'] = [];
                if (! empty($data['columns']['foreign_keys'])) {
                    $search_data['foreign_keys'] = $data['columns']['foreign_keys'];
                }

                if (! empty($data['columns']['filter_groups'])) {
                    $search_data['filter_groups'] = $data['columns']['filter_groups'];
                }

                if (! empty($data['attributes']['filter_model'])) {
                    $search_data['filter_model'] = $data['attributes']['filter_model'];
                }

                if (! empty($data['sql'])) {
                    $data_model = null;
                    $data_sql = $data['sql'];
                } else {
                    $data_model = $data['model'];
                    $data_sql = null;
                }

                $filterQuery = [];
                if (! empty($this->conditions['where'])) {
                    $filterQuery = $this->conditions['where'];
                }

                $searchInfo = ['id' => $tableID];
                $searchInfoAttribute = "{$searchInfo['id']}_cdyFILTER";
                $search_object = new Search("{$searchInfo['id']}_cdyFILTER", $data_model, $search_data, $data_sql, $this->connection, $filterQuery);
                $this->filter_object = $search_object;

                $dt_info['id'] = $searchInfo['id'];
                $dt_info['class'] = 'dt-button buttons-filter';
                $dt_info['attributes'] = [
                    'id' => $searchInfoAttribute,
                    'class' => "modal fade {$tableID}",
                    'role' => 'dialog',
                    'tabindex' => '-1',
                    'aria-hidden' => 'true',
                    'aria-controls' => $tableID,
                    'aria-labelledby' => $tableID,
                    'data-backdrop' => 'static',
                    'data-keyboard' => 'true',
                ];
                $dt_info['button_label'] = '<i class="fa fa-filter"></i> Filter';
                $dt_info['action_button_removed'] = $data['attributes']['buttons_removed'];
                $dt_info['modal_title'] = '<i class="fa fa-filter"></i> &nbsp; Filter';
                $dt_info['modal_content'] = $search_object->render($searchInfoAttribute, $dt_info['name'], $data['columns']['filters']);
            }
        }
        $datatables[$name]['columns'] = $dt_columns;

        $this->filter_contents[$tableID] = $dt_info;

        $filter_data = [];
        if (true === $filter) {
            $filter_data = $this->getFilterDataTables();
        }

        $dt_columns = canvastack_clear_json(json_encode($dt_columns));

        if ('GET' === $this->method) {
            $datatable = $this->datatables($tableID, $dt_columns, $dt_info, true, $filter_data);
        } else {
            // Use POST method for datatables
            $post = new Post($tableID, $dt_columns, $dt_info, true, $filter_data);
            $datatable = $post->script();
        }

        return $datatable;
    }

    private function getFilterDataTables()
    {
        $filter_strings = null;
        if (! empty($_GET['filters'])) {
            $input_filters = [];
            $_ajax_url = 'renderDataTables';
            foreach ($_GET as $name => $value) {
                if ('filters' !== $name && '' !== $value) {
                    if (! is_array($value)) {
                        if (
                            $name !== $_ajax_url &&
                            $name !== 'draw' &&
                            $name !== 'columns' &&
                            $name !== 'order' &&
                            $name !== 'start' &&
                            $name !== 'length' &&
                            $name !== 'search' &&
                            $name !== '_token' &&
                            $name !== '_'
                        ) {
                            $input_filters[] = "infil[{$name}]={$value}";
                        }
                    }
                }
            }

            $filter_strings = '&filters=true&'.implode('&', $input_filters);
        }

        return $filter_strings;
    }

    private function backgroundColor($attributes = [])
    {
        if (! empty($attributes)) {
            $tableDataColor = [];
            foreach ($attributes as $colorCode => $dataColor) {
                if (! empty($dataColor['text'])) {
                    $textColor = " color:{$dataColor['text']};";
                }
                if (! empty($dataColor['columns'])) {
                    foreach ($dataColor['columns'] as $columnName) {
                        $tableDataColor['columns'][$columnName] = $this->setAttributes(['style' => "background-color:{$colorCode} !important;{$textColor}"]);
                    }
                }

                if (empty($dataColor['columns'])) {
                    if (true === $dataColor['header']) {
                        $tableDataColor['header'] = $this->setAttributes(['style' => "background-color:{$colorCode} !important;{$textColor}"]);
                    }
                }
            }

            return $tableDataColor;
        }
    }

    private function setAttributes($attributes = [])
    {
        $textAttribute = null;
        if (is_array($attributes)) {
            foreach ($attributes as $key => $value) {
                $textAttribute .= " {$key}=\"{$value}\"";
            }
        }

        return $textAttribute;
    }
}
