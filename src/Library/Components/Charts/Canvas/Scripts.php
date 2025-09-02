<?php

namespace Canvastack\Canvastack\Library\Components\Charts\Canvas;

/**
 * Created on May 23, 2023
 *
 * Time Created : 4:34:23 PM
 *
 * @filesource  Scripts.php
 *
 * @author      wisnuwidi@gmail.com - 2023
 * @copyright   wisnuwidi@gmail.com,
 *              canvastack@gmail.com
 *
 * @email       wisnuwidi@gmail.com
 */
trait Scripts
{
    public $script_chart = [];

    protected function filterTableInjectionScript($data)
    {
        $formIdentity = $data['identity']['filter_table'];
        $formIDString = str_replace('-', '', $formIdentity);
        $chartIdentity = $data['identity']['chart_info'];

        foreach ($chartIdentity as $chartInfo) {
            unset($chartIdentity);
            $chartIdentity[$formIdentity] = $chartInfo;
        }

        $chartIDCode = $chartIdentity[$formIdentity]['code'];
        $chartIDString = $chartIdentity[$formIdentity]['string'];
        $submitFilterButton = str_replace('_cdyFILTERForm', '_submitFilterButton', "{$formIdentity}");
        $urli = $this->chartURI[$chartIdentity[$formIdentity]['code']].'&diyChartDataFilter='.$chartIDString.'&diyChartData='.$chartIDCode;

        unset($chartIdentity[$formIdentity]['source']);
        unset($chartIdentity[$formIdentity]['code']);
        unset($chartIdentity[$formIdentity]['string']);

        $data['datatables']['source'] = 'diyChartDataFilter::'.canvastack_encrypt($data['datatables']['source']);

        $dataPosts = [];
        $dataPosts[$chartIDCode]['info'] = $chartIdentity[$formIdentity];
        $dataPosts[$chartIDCode]['params'] = $data['datatables'];
        $dataPosts[$chartIDCode]['params']['filter']['where'] = [];
        $jsonDataPosts = json_encode($dataPosts);

        $script = '<script type="text/javascript">';
        $script .= '$(document).ready(function() {';

        $script .= "var ajaxFromTable{$formIDString} = {};";
        $script .= "$('#{$submitFilterButton}').click(function() {";
        $script .= "var postFromTable{$chartIDString} = [ {$jsonDataPosts} ];";
        $script .= "ajaxFromTable{$formIDString} = $('#{$formIdentity}').serializeArray();";

        $script .= "if ('_token' === ajaxFromTable{$formIDString}[0].name) {";
        $script .= "$.each(postFromTable{$chartIDString}, function(i, chartObj) {";

        $script .= '$.each(chartObj, function(i, chartObjData) {console.log(chartObjData);';
        $script .= "$.each(ajaxFromTable{$formIDString}, function(index, item) {";

        $script .= "if ('_token' !== item.name && '' != item.value) {";
        $script .= 'chartObjData.params.filter.where.push({';
        $script .= 'field_name: item.name,';
        $script .= "operator: '=',";
        $script .= 'value: item.value';
        $script .= '});';
        $script .= '}';

        $script .= '});';
        $script .= '});';

        $script .= '});';
        $script .= '}';

        // Remove All Series @https://stackoverflow.com/questions/48590737/how-to-efficiently-remove-all-series-from-highchart-highstock-and-then-add-many/48645230#48645230
        $script .= "for (var i = {$chartIDString}.series.length - 1; i >= 0; i--) { {$chartIDString}.series[i].remove(false); }";

        $script .= "requestData{$chartIDString} ('{$urli}', { postFromTable{$chartIDString} });";
        $script .= '});';

        $script .= '});';
        $script .= '</script>';

        $this->script_chart['js'] = $script;
    }

    protected function ajaxProcess($identity, $url, $dataValues, $postData)
    {

        $data = [];
        $data['identity'] = $identity;
        $data['url'] = $url;
        $data['dataValues'] = $dataValues;
        $data['postData'] = $postData;

        $token = csrf_token();
        $script = "function requestData{$data['identity']} (urliReq, dataValues) {";

        $script .= "if ('object' == typeof urliReq) {";
        $script .= "var urliReq = '{$url}';";
        $script .= "var dataValues = {$dataValues};";
        $script .= '}';

        $script .= '$.ajax({';
        $script .= 'url: urliReq,';
        $script .= "type: 'POST',";
        $script .= "headers: {'X-CSRF-TOKEN': '{$token}'},";
        $script .= "dataType: 'json',";
        $script .= 'data: dataValues,';

        $script .= 'success: function(data) {';

        $script .= "$.each(data.category.{$postData}, function(i, chart) {";
        $script .= "{$data['identity']}.xAxis[0].setCategories(chart);";
        $script .= '});';

        $script .= "$.each(data.series.{$postData}.series, function(i, chart) {";
        $script .= "{$data['identity']}.addSeries({";
        $script .= 'name: chart.name,';
        $script .= 'data: chart.data,';
        $script .= 'type: chart.type';
        $script .= '});';
        $script .= '});';
        //	$script .= "{$data['identity']}.redraw();";

        $script .= '},';

        $script .= 'cache: false';
        $script .= '});';

        $script .= '}';

        return $script;
    }

    private function setTitle($title = null)
    {
        if (canvastack_string_contained($title, 't_view')) {
            $setTitle = ucwords(str_replace('_', ' ', str_replace('t_view_', '', $title)));
        } else {
            $setTitle = ucwords(str_replace('_', ' ', $title));
        }

        return $setTitle;
    }

    protected function canvascipt($identity_chart, $identity_string, $params = [], $attributes = [])
    {
        $title = json_encode(['text' => $this->setTitle($params['source'])]);
        $subtitle = '{}';
        $axisTitle = '{}';

        $yAxisMin = 'min: 0,';
        if (! empty($params['options']['negative_values']) && true === $params['options']['negative_values']) {
            $yAxisMin = '';
        }

        $options = [];
        $options['column']['pointPadding'] = 0.2;
        $options['column']['borderWidth'] = 0;

        if ('area' === $params['type']) {
            //	$options['area']['pointStart']        = 0;
            $options['area']['marker']['enabled'] = false;
            $options['area']['marker']['symbol'] = 'circle';
            $options['area']['marker']['radius'] = 2;
            $options['area']['marker']['states']['hover']['enabled'] = true;
        }

        if (! empty($params['options']['stack']) && false !== $params['options']['stack']) {
            if (true === $params['options']['stack']) {
                $options['series']['stacking'] = 'normal';
            } else {
                //	$options['series']['pointStart']      = -51003;
                $options['area']['stacking'] = $params['options']['stack'];
                $options['area']['marker']['enabled'] = false;
            }
        }
        //	dd($options);
        if (! empty($options)) {
            $opts = [];
            foreach ($options as $optLabel => $optValues) {
                $opts[$optLabel] = $optValues;
            }
            $jsonOptions = json_encode($opts);
            unset($options);
            $options = $jsonOptions;
        }

        $tooltipFormatPoint = '';
        $tooltip = '{';
        $tooltip .= "headerFormat: '<span style=\"font-size:10px\">{point.key}</span><table>',";
        $tooltip .= "pointFormat : '<tr><td style=\"color:{series.color};padding:0\">{series.name}: </td>' + '<td style=\"padding:0\"><b>{point.y:.1f} {$tooltipFormatPoint}</b></td></tr>',";
        $tooltip .= "footerFormat: '</table>',";
        $tooltip .= 'shared: true,';
        $tooltip .= 'useHTML: true';
        $tooltip .= '}';

        $plotOptions = $options;

        if (! empty($attributes)) {
            if (! empty($attributes['title'])) {
                $title = json_encode($attributes['title']);
            }
            if (! empty($attributes['subtitle'])) {
                $subtitle = json_encode($attributes['subtitle']);
            }
            if (! empty($attributes['axisTitle'])) {
                $axisTitle = json_encode($attributes['axisTitle']);
            }
            if (! empty($attributes['tooltip'])) {
                $tooltip = json_encode($attributes['tooltip']);
            }
            if (! empty($attributes['plotOptions'])) {
                $plotOptions = json_encode($attributes['plotOptions']);
            }
        }

        $canvas = "var filterData{$identity_string} = [];";
        $canvas .= "var {$identity_string} = new Highcharts.chart({";

        $canvas .= 'chart: {';
        $canvas .= "renderTo: '{$identity_chart}',";
        $canvas .= "type: '{$params['type']}',";
        $canvas .= 'events: {';
        $canvas .= "load: requestData{$identity_string}";
        $canvas .= '}';
        $canvas .= '},';

        $canvas .= 'dataSource: {';
        $canvas .= "data: filterData{$identity_string}";
        $canvas .= '},';

        $canvas .= "title: {$title},";
        $canvas .= "subtitle: {$subtitle},";

        $canvas .= 'xAxis: {';
        //	$canvas .= "categories: [],";
        $canvas .= 'crosshair: true';
        $canvas .= '},';

        $canvas .= 'yAxis: {';
        $canvas .= $yAxisMin;
        $canvas .= "title: {$axisTitle}";
        $canvas .= '},';

        $canvas .= "tooltip: {$tooltip},";
        $canvas .= "plotOptions: {$plotOptions},";

        $canvas .= 'responsive: {';

        $canvas .= 'rules: [{';

        $canvas .= 'condition: {';
        $canvas .= 'maxWidth: 500';
        $canvas .= '},';

        $canvas .= 'chartOptions: {';
        $canvas .= 'legend: {';
        $canvas .= 'enabled: false';
        $canvas .= '}';
        $canvas .= '}';

        $canvas .= '}]';

        $canvas .= '}';

        $canvas .= '});';

        return $canvas;
    }
}
