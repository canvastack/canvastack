<?php

namespace Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Traits;

use Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Columns\RelationsAndMetaShaper;

/**
 * ListBuilderTrait
 *
 * Build datatable list configuration and meta wiring (delegates shaping to shapers/managers).
 */
trait ListBuilderTrait
{
    public $modelProcessing = [];

    public $tableName = [];

    public $tableID = [];

    private function check_column_exist($table_name, $fields, $connection = 'mysql')
    {
        $fieldset = [];
        foreach ($fields as $field) {
            if (canvastack_check_table_columns($table_name, $field, $connection)) {
                $fieldset[] = $field;
            }
        }

        return $fieldset;
    }

    public function lists(string $table_name = null, $fields = [], $actions = true, $server_side = true, $numbering = true, $attributes = [], $server_side_custom_url = false)
    {
        if (! empty($this->variables['model_processing'])) {
            if ($table_name !== $this->variables['model_processing']['table']) {
                $table_name = $this->variables['model_processing']['table'];
            }
            $this->modelProcessing[$table_name] = $this->variables['model_processing'];
        }

        if (null === $table_name) {
            if (! empty($this->variables['table_data_model'])) {
                if ('sql' === $this->variables['table_data_model']) {
                    $sql = $this->variables['query'];
                    $table_name = canvastack_get_table_name_from_sql($sql);
                    $this->params[$table_name]['query'] = $sql;
                } else {
                    $table_name = canvastack_get_model_table($this->variables['table_data_model']);
                }
            }
            $this->variables['table_name'] = $table_name;
        }
        $this->tableName = $table_name;
        $this->records['index_lists'] = $numbering;

        if (is_array($fields)) {
            $recola = [];
            foreach ($fields as $icol => $cols) {
                if (canvastack_string_contained($cols, ':')) {
                    $split_cname = explode(':', $cols);
                    $this->labels[$split_cname[0]] = $split_cname[1];
                    $recola[$icol] = $split_cname[0];
                } else {
                    $recola[$icol] = $cols;
                }
            }
            $fields = $recola;
            $fieldset_added = $fields;

            if (! empty($fields)) {
                if (! canvastack_string_contained($table_name, 'view_')) {
                    $fields = $this->check_column_exist($table_name, $fields, $this->connection);

                    if (empty($fields) && ! empty($this->modelProcessing)) {
                        if (! empty($recola)) {
                            $fields = $recola;
                        }
                        if (! canvastack_schema('hasTable', $table_name)) {
                            canvastack_model_processing_table($this->modelProcessing, $table_name);
                        }
                        $fields = canvastack_get_table_columns($table_name);
                    }
                }
            } elseif (! empty($this->variables['table_fields'])) {
                $fields = $this->check_column_exist($table_name, $this->variables['table_fields']);
            } else {
                $fields = canvastack_get_table_columns($table_name, $this->connection);
                if (empty($fields) && ! empty($this->modelProcessing)) {
                    if (! canvastack_schema('hasTable', $table_name)) {
                        canvastack_model_processing_table($this->modelProcessing, $table_name);
                    }
                    $fields = canvastack_get_table_columns($table_name);
                }
            }

            if (! isset($this->columns[$table_name]) || ! is_array($this->columns[$table_name])) {
                $this->columns[$table_name] = [];
            }
            if (! isset($this->labels) || ! is_array($this->labels)) {
                $this->labels = [];
            }
            $columnsMeta = &$this->columns[$table_name];
            $labelsRef = &$this->labels;
            $fields = RelationsAndMetaShaper::apply(
                $fields,
                $fieldset_added,
                (array) ($this->relational_data ?? []),
                (string) $table_name,
                $columnsMeta,
                $labelsRef
            );
            
            // Debug: Check if foreign_keys are stored in columns
            \Log::info("ListBuilderTrait: After RelationsAndMetaShaper", [
                'table' => $table_name,
                'has_foreign_keys' => isset($columnsMeta['foreign_keys']),
                'foreign_keys' => $columnsMeta['foreign_keys'] ?? null,
                'columns_keys' => array_keys($columnsMeta)
            ]);
        }

        $search_columns = false;
        if (! empty($this->search_columns)) {
            if ($this->all_columns === $this->search_columns) {
                $search_columns = $fields;
            } else {
                $search_columns = $this->search_columns;
            }
        }
        $this->search_columns = $search_columns;

        if (false === $actions) {
            $actions = [];
        }
        $this->columns[$table_name]['lists'] = $fields;
        $this->columns[$table_name]['actions'] = $actions;

        if (! empty($this->variables['text_align'])) {
            $this->columns[$table_name]['align'] = $this->variables['text_align'];
        }
        if (! empty($this->variables['merged_columns'])) {
            $this->columns[$table_name]['merge'] = $this->variables['merged_columns'];
        }
        if (! empty($this->variables['column_width'])) {
            $this->columns[$table_name]['width'] = $this->variables['column_width'];
        }
        if (! empty($this->variables['background_color'])) {
            $this->columns[$table_name]['bgcolor'] = $this->variables['background_color'];
        }
        if (! empty($this->variables['format_data'])) {
            $this->columns[$table_name]['formatter'] = $this->variables['format_data'];
        }
        if (! empty($this->variables['add_table_attributes'])) {
            $this->columns[$table_name]['attributes'] = $this->variables['add_table_attributes'];
        }
        if (! empty($this->variables['orderby_column'])) {
            $this->columns[$table_name]['orderby'] = $this->variables['orderby_column'];
        }
        if (! empty($this->variables['fixed_columns'])) {
            $this->columns[$table_name]['fixedcolumns'] = $this->variables['fixed_columns'];
        }
        if (! empty($this->variables['searchable_columns'])) {
            $this->columns[$table_name]['searchable'] = $this->variables['searchable_columns'];
        }
        if (! empty($this->variables['filter_groups'])) {
            $this->columns[$table_name]['filterGroups'] = $this->variables['filter_groups'];
        }
        $this->columns[$table_name]['clickable'] = [$this->variables['clickable_columns'] ?? []];
        if (! empty($this->variables['raw_columns_forced'])) {
            $this->columns[$table_name]['raw_columns'] = $this->variables['raw_columns_forced'];
        }
        if (! empty($this->variables['image_fields'])) {
            $this->columns[$table_name]['image_fields'] = $this->variables['image_fields'];
        }

        $this->columns[$table_name]['table']['server_side'] = $server_side;
        $this->columns[$table_name]['table']['attributes'] = $attributes;
        $this->columns[$table_name]['table']['index'] = $this->records['index_lists'] ?? $numbering;
        $this->columns[$table_name]['table']['name'] = $table_name;
        $this->columns[$table_name]['table']['server_side_custom_url'] = $server_side_custom_url;

        $this->element_name['table'] = $this->element_name['table'] ?? 'datatable';
        $this->tableID[$table_name] = $this->tableID[$table_name] ?? canvastack_datatable_id("{$this->element_name['table']}::{$table_name}");
        $this->elements[$this->tableID[$table_name]] = view("components.{$this->element_name['table']}")
            ->with('id', $this->tableID[$table_name])
            ->with('table', $this->columns[$table_name])
            ->with('connection', $this->connection)
            ->with('source', $table_name)
            ->with('scripts', $this->filter_scripts)
            ->render();

        return $this->render($this->elements);
    }
}
