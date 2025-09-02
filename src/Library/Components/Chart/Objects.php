<?php

namespace Canvastack\Canvastack\Library\Components\Chart;

use Canvastack\Canvastack\Library\Components\Chart\Canvas\Area\Canvas as Area;
use Canvastack\Canvastack\Library\Components\Chart\Canvas\Bar\Canvas as Bar;
use Canvastack\Canvastack\Library\Components\Chart\Canvas\Column\Canvas as Column;
use Canvastack\Canvastack\Library\Components\Chart\Canvas\Combinations\DualAxesLineAndColumn as DualAxes;
use Canvastack\Canvastack\Library\Components\Chart\Canvas\Line\Canvas as Line;
use Canvastack\Canvastack\Library\Components\Chart\Includes\DataConstructions;
use Canvastack\Canvastack\Library\Components\Chart\Includes\Scripts;

/**
 * Created on Oct 10, 2022
 *
 * Time Created : 1:15:13 PM
 *
 * @filesource	Objects.php
 *
 * @author     wisnuwidi@canvastack.com - 2022
 * @copyright  wisnuwidi
 *
 * @email      wisnuwidi@canvastack.com
 */
class Objects extends Charts
{
    use DataConstructions, Scripts;
    use Column, Bar, Line, Area;
    use DualAxes;

    public $elements = [];

    public $params = [];

    private $identities = [];

    private $chartInfo = 'highcharts';

    public function __construct()
    {
        $this->element_name['chart'] = $this->chartInfo;
        $this->charts = new Charts();
    }

    public function render($object)
    {
        return $object;
    }

    private function draw($initial, $data = [])
    {
        if ($data) {
            $this->elements[$initial] = $data;
        }
    }

    private function setPrefix($function_name, $source)
    {
        $prefix = 'canvastack-charts';
        $random = $prefix.'-'.$this->chartInfo.'-'.canvastack_random_strings(50, false);

        $this->identities['prefix'][$function_name][$source] = $random;
    }

    private function addAttributes($function_name, $identify)
    {
        if (! empty($this->attributes)) {
            $this->addParams($function_name, $identify, 'attributes', $this->attributes);
            unset($this->attributes);
        }
    }

    protected function setParams($function_name, $source, $fieldsets, $format, $category, $order = null, $group = null)
    {
        $this->setPrefix($function_name, $source);
        $identify = $this->identities['prefix'][$function_name][$source];

        $this->params[$function_name][$identify]['construct']['source'] = $source;
        $this->params[$function_name][$identify]['construct']['fieldsets'] = $fieldsets;
        $this->params[$function_name][$identify]['construct']['format'] = $format;
        $this->params[$function_name][$identify]['construct']['category'] = $category;
        $this->params[$function_name][$identify]['construct']['group'] = $group;
        $this->params[$function_name][$identify]['construct']['order'] = $order;

        $this->setTitle($function_name, $identify, $source);
        $this->addAttributes($function_name, $identify);
    }

    protected function addParams($function_name, $identify, $param_name, $data)
    {
        $this->params[$function_name][$identify][$param_name] = $data;
    }

    private function setTitle($function_name, $identify, $title = null)
    {
        if (canvastack_string_contained($title, 't_view')) {
            $setTitle = ucwords(str_replace('_', ' ', str_replace('t_view_', '', $title)));
        } else {
            $setTitle = ucwords(str_replace('_', ' ', $title));
        }

        $this->params[$function_name][$identify]['attributes'] = ['title' => ['text' => $setTitle]];
    }

    private function build($type, $identity, $data)
    {
        $canvas = [];
        if (! empty($data['data']['canvas'])) {
            $canvas = $data['data']['canvas'];
        }

        $attributes = [];
        if (! empty($canvas)) {
            foreach ($canvas as $attr_name => $attr_value) {
                $attributes[] = "{$attr_name}=\"{$attr_value}\"";
            }
        }
        $attributes = ' '.implode(' ', $attributes);

        $scriptName = "{$type}_script";
        $this->elements[$identity] = '<div id="'.$identity.'"'.$attributes.'></div>';
        $this->{$scriptName}($identity, $data);

        $this->draw($this->elements);
    }
}
