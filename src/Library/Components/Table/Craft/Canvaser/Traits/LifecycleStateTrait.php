<?php

namespace Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Traits;

/**
 * LifecycleStateTrait
 *
 * Variables lifecycle, clearing, config hooks, and object-wide state holders.
 */
trait LifecycleStateTrait
{
    private function clearVariables($clear_set = true)
    {
        $this->clear_variables = $clear_set;
        if (true === $this->clear_variables) {
            $this->clear_all_variables();
        }
    }

    public function clear($clear_set = true)
    {
        return $this->clearVariables($clear_set);
    }

    public function clearVar($name)
    {
        $this->variables[$name] = [];
    }

    private function clear_all_variables()
    {
        $this->variables['on_load'] = [];
        $this->variables['url_value'] = [];
        $this->variables['merged_columns'] = [];
        $this->variables['text_align'] = [];
        $this->variables['background_color'] = [];
        $this->variables['attributes'] = [];
        $this->variables['orderby_column'] = [];
        $this->variables['sortable_columns'] = [];
        $this->variables['clickable_columns'] = [];
        $this->variables['searchable_columns'] = [];
        $this->variables['filter_groups'] = [];
        $this->variables['column_width'] = [];
        $this->variables['format_data'] = [];
        $this->variables['add_table_attributes'] = [];
        $this->variables['fixed_columns'] = [];
        $this->variables['model_processing'] = [];
    }

    public function set_regular_table()
    {
        $this->tableType = 'regular';
    }
}
