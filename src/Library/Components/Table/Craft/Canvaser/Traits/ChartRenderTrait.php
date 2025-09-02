<?php

namespace Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Traits;

use Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Assets\ScriptsConsolidator;

/**
 * ChartRenderTrait
 *
 * Chart options, render draw helpers, and element sync for charts/tables.
 */
trait ChartRenderTrait
{
    public $filter_scripts = [];

    private $chartOptions = [];

    private $syncElements = false;

    private function chartCanvas()
    {
        // Keep behavior identical, avoid relying on class 'use' in consumers
        return new \Canvastack\Canvastack\Library\Components\Charts\Objects();
    }

    public function chartOptions($option_name, $option_values = [])
    {
        $this->chartOptions[$option_name] = $option_values;
    }

    public function chart($chart_type, $fieldsets, $format, $category = null, $group = null, $order = null)
    {
        $bridge = \Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Charts\ChartBridge::invoke(
            (string) $chart_type,
            (array) $fieldsets,
            $format,
            $category,
            $group,
            $order,
            $this->connection,
            (string) $this->tableName,
            (array) ($this->chartOptions ?? [])
        );
        if (! empty($this->chartOptions)) {
            unset($this->chartOptions);
        }

        /** @var \Canvastack\Canvastack\Library\Components\Charts\Objects $chart */
        $chart = $bridge['chart'];
        $chart->syncWith($this);

        $this->element_name['chart'] = $bridge['chartLibrary'];
        $tableIdentity = $this->tableID[$this->tableName];
        $canvas = [];
        $canvas['chart'][$tableIdentity] = $bridge['elements'];
        $initTable = [];
        $initTable['chart'] = $this->tableID[$this->tableName];

        $tableElement = $this->elements[$tableIdentity];
        $canvasElement = $canvas['chart'][$tableIdentity];
        $defaultPageFilters = [];
        if (! empty($this->filter_contents[$tableIdentity]['conditions']['where'])) {
            $defaultPageFilters = $this->filter_contents[$tableIdentity]['conditions']['where'];
        }

        $this->syncElements[$tableIdentity]['identity']['chart_info'] = $bridge['identities'];
        $this->syncElements[$tableIdentity]['identity']['filter_table'] = "{$tableIdentity}_cdyFILTERForm";

        $this->syncElements[$tableIdentity]['datatables']['type'] = $chart_type;
        $this->syncElements[$tableIdentity]['datatables']['source'] = $this->tableName;
        $this->syncElements[$tableIdentity]['datatables']['fields'] = $fieldsets;
        $this->syncElements[$tableIdentity]['datatables']['format'] = $format;
        $this->syncElements[$tableIdentity]['datatables']['category'] = $category;
        $this->syncElements[$tableIdentity]['datatables']['group'] = $group;
        $this->syncElements[$tableIdentity]['datatables']['order'] = $order;
        $this->syncElements[$tableIdentity]['datatables']['page_filter'] = ['where' => $defaultPageFilters];

        $chart->modifyFilterTable($this->syncElements[$tableIdentity]);

        $syncElements = [];
        $syncElements['chart'][$tableIdentity] = $tableElement.$bridge['script_js'].implode('', $canvasElement);

        $this->draw($initTable, $syncElements);
    }

    private function draw($initial, $data = [])
    {
        if ($data) {
            $multiElements = [];
            if (is_array($initial)) {
                foreach ($initial as $syncElements) {
                    if (is_array($data)) {
                        foreach ($data as $dataValue) {
                            $initData = $dataValue[$syncElements];
                            if (is_array($initData)) {
                                $multiElements[$syncElements] = implode('', $initData);
                            } else {
                                $multiElements[$syncElements] = $initData;
                            }
                        }
                    }
                    $this->elements[$syncElements] = $multiElements[$syncElements];
                }
            } else {
                $this->elements[$initial] = $data;
            }

            if (! empty($this->filter_object->add_scripts)) {
                $normalized = ScriptsConsolidator::normalize($this->filter_object->add_scripts);
                $this->filter_scripts['css'] = $normalized['css'] ?? [];
                $this->filter_scripts['js'] = $normalized['js'] ?? [];
            }
        } else {
            $this->elements[] = $initial;
        }
    }

    public function render($object)
    {
        $tabObj = '';
        if (true === is_array($object)) {
            $tabObj = implode('', $object);
        }

        if (true === canvastack_string_contained($tabObj, $this->opentabHTML)) {
            return $this->renderTab($object);
        } else {
            return $object;
        }
    }
}
