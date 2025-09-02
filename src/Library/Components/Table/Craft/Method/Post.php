<?php

namespace Canvastack\Canvastack\Library\Components\Table\Craft\Method;

use Canvastack\Canvastack\Library\Components\Table\Craft\Elements;

/**
 * Created on Dec 28, 2022
 *
 * Time Created : 3:02:03 PM
 *
 * @filesource	Post.php
 *
 * @author     wisnuwidi@canvastack.com - 2022
 * @copyright  wisnuwidi
 *
 * @email      wisnuwidi@canvastack.com
 */
class Post
{
    use Elements;

    private $id;

    private $columns;

    private $data;

    private $server_side;

    private $filters;

    private $custom_url;

    private $config = [];

    private $configName = [
        'searching',
        'processing',
        'retrieve',
        'paginate',
        'searchDelay',
        'bDeferRender',
        'responsive',
        'lengthMenu',
        'buttons',
        'orders',
        'rowReorder',
        'dom',
    ];

    public function __construct($attr_id, $columns, $data = [], $server_side = false, $filters = false, $custom_url = false)
    {
        $this->id = $attr_id;
        $this->columns = $columns;
        $this->data = $data;
        $this->server_side = $server_side;
        $this->filters = $filters;
        $this->custom_url = $custom_url;

        $this->config();
    }

    private function setConfig($name, $value = true)
    {
        $this->config[$name] = $value;
    }

    private $buttonConfig = 'exportOptions:{columns:":visible:not(:last-child)"}';

    private function config()
    {
        foreach ($this->configName as $config) {
            $this->setConfig($config);
        }

        $this->setConfig('searchDelay', 1000);
        $this->setConfig('responsive', false);
        $this->setConfig('autoWidth', false);
        $this->setConfig('dom', 'lBfrtip');
        $this->setConfig('rowReorder', "{selector:'td:nth-child(2)'}");
        $this->setConfig('lengthMenu', [[10, 25, 50, 100, 250, 500, 1000, -1], ['10', '25', '50', '100', '250', '500', '1000', 'Show All']]);
        $this->setConfig('buttons', $this->setButtons($this->id, [
            'excel|text:"<i class=\"fa fa-external-link\" aria-hidden=\"true\"></i> <u>E</u>xcel"|key:{key:"e",altKey:true}',
            'csv|'.$this->buttonConfig,
            'pdf|'.$this->buttonConfig,
            'copy|'.$this->buttonConfig,
            'print|'.$this->buttonConfig,
        ]));
    }

    public function script()
    {

        $varTableID = explode('-', $this->id);
        $varTableID = implode('', $varTableID);
        $current_url = url(canvastack_current_route()->uri);
        $configurations = json_encode($this->config);

        $script = '<script type="text/javascript">';
        $script .= "
jQuery(function($) {
	var cody_{$varTableID}_dt = $('#{$this->id}').DataTable(
		{$configurations}
	);
});
		";
        $script .= '</script>';

        return $script;
    }
}
