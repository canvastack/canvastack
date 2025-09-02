<?php

namespace Canvastack\Canvastack\Library\Components\Charts;

use Canvastack\Canvastack\Library\Components\Charts\Canvas\Charts;
use Canvastack\Canvastack\Library\Components\Charts\Canvas\DataModel;
use Canvastack\Canvastack\Library\Components\Form\Elements\Tab;

/**
 * Created on May 23, 2023
 *
 * Time Created : 4:29:05 PM
 *
 * @filesource  Objects.php
 *
 * @author      wisnuwidi@gmail.com - 2023
 * @copyright   wisnuwidi@gmail.com,
 *              canvastack@gmail.com
 *
 * @email       wisnuwidi@gmail.com
 */
class Objects extends Charts
{
    use Tab, DataModel;

    public $elements = [];

    public $element_name = [];

    public $params = [];

    public $connection;

    public $chartLibrary = 'highcharts';

    /**
     * --[openTabHTMLForm]--
     */
    private $opentabHTML = '--[openTabHTMLForm]--';

    public function __construct()
    {
        parent::__construct();

        $this->element_name['chart'] = $this->chartLibrary;
    }

    public function use($chart)
    {
        $this->chartLibrary = $chart;
    }

    public function connection($db_connection)
    {
        $this->connection = $db_connection;
    }

    public function draw($initial, $data = [])
    {
        if ($data) {
            $this->elements[$initial] = $data;
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

    public $identities = [];

    protected $sourceIdentity;

    protected function setParams($type, $source, $fieldsets, $format, $category = null, $group = null, $order = null, $options = [])
    {
        $this->sourceIdentity = canvastack_clean_strings("Cocanvastack_{$this->chartLibrary}_".$source.'_'.canvastack_random_strings(50, false));

        $this->identities[$this->sourceIdentity]['connection'] = $this->connection;
        $this->identities[$this->sourceIdentity]['code'] = $this->sourceIdentity;
        $this->identities[$this->sourceIdentity]['source'] = $source;
        $this->identities[$this->sourceIdentity]['string'] = str_replace('-', '', $this->sourceIdentity);

        $this->params[$this->sourceIdentity]['type'] = $type;
        $this->params[$this->sourceIdentity]['source'] = $source;
        $this->params[$this->sourceIdentity]['fields'] = $fieldsets;
        $this->params[$this->sourceIdentity]['format'] = $format;
        $this->params[$this->sourceIdentity]['category'] = $category;
        $this->params[$this->sourceIdentity]['group'] = $group;
        $this->params[$this->sourceIdentity]['order'] = $order;
        $this->params[$this->sourceIdentity]['filter'] = [];
        $this->params[$this->sourceIdentity]['options'] = [];

        if (! empty($options)) {
            foreach ($options as $opt_label => $opt_values) {
                $this->params[$this->sourceIdentity]['options'][$opt_label] = $opt_values;
            }
        }

        if (! empty($this->sync)) {
            $sync = $this->sync;
            unset($this->sync);
            $this->params[$this->sourceIdentity]['filter'] = $sync['filter'];
        }

        if (! empty($this->attributes)) {
            $attributes = [];
            $attributes[$this->sourceIdentity] = $this->attributes;
            unset($this->attributes);
            $this->attributes = $attributes;
        }
    }

    protected function setAttributes($function_name, $attributes)
    {
        $this->attributes[$function_name] = $attributes;
    }

    protected $post = [];

    protected $chartPostData = 'diyChartData';

    public function process($post)
    {
        if (! empty($_GET['diyChartDataFilter'])) {
            $postFilter = [];
            $chartIdentityFilter = 'postFromTable'.$_GET['diyChartDataFilter'];
            $chartIdentityCode = $_GET['diyChartData'];
            $postFilter = $post[$chartIdentityFilter][0][$chartIdentityCode];
            unset($post);

            $post = [];
            $post[$this->chartPostData] = canvastack_encrypt(json_encode($postFilter));
        }

        $this->construct(json_decode(canvastack_decrypt($post[$this->chartPostData])));

        echo json_encode(['category' => $this->category, 'series' => $this->series]);
        exit;
    }

    public function modifyFilterTable($data)
    {
        return $this->filterTableInjectionScript($data);
    }
}
