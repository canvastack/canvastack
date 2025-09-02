<?php

namespace Canvastack\Canvastack\Library\Components\Chart\Includes;

/**
 * Created on Oct 19, 2022
 *
 * Time Created : 5:20:45 PM
 *
 * @filesource	DataConstructions.php
 *
 * @author     wisnuwidi@canvastack.com - 2022
 * @copyright  wisnuwidi
 *
 * @email      wisnuwidi@canvastack.com
 */
trait DataConstructions
{
    private function construct()
    {
        if (! empty($this->params)) {
            foreach ($this->params as $chartType => $chartData) {
                foreach ($chartData as $identifier => $sourceConstruct) {
                    $sourceData = $sourceConstruct['construct'];

                    $sourceGroup = [];
                    if (! empty($sourceData['group'])) {
                        if (canvastack_string_contained($sourceData['group'], ',')) {
                            $sourceGroup = explode(',', str_replace(' ', '', $sourceData['group']));
                        } else {
                            $sourceGroup = [$sourceData['group']];
                        }
                    }

                    if (! empty($sourceData['order'])) {
                        if (canvastack_string_contained($sourceData['order'], ',')) {
                            $dataOrders = explode(',', str_replace(' ', '', $sourceData['order']));
                        } else {
                            $dataOrders = [$sourceData['order']];
                        }

                        foreach ($dataOrders as $orderData) {
                            if (canvastack_string_contained($orderData, '::')) {
                                $splitOrder = explode('::', $orderData);
                                $sourceOrder[$splitOrder[0]] = "`{$splitOrder[0]}` {$splitOrder[1]}";
                            }
                        }
                    }

                    if (! empty($sourceData['format'])) {

                        $formatData = [];
                        $formatData['param_as'] = [];
                        $formatData['calculation_format'] = [];

                        $sQueryData = [];
                        $sQueryData['fields'] = [];
                        $sQueryData['group'] = [];
                        $sQueryData['un_group'] = [];
                        $sQueryData['order'] = [];
                        $sQueryData['data'] = [];

                        $data_format = explode('|', $sourceData['format']);

                        foreach ($data_format as $format_info) {
                            if (! empty($format_info)) {
                                if (empty(canvastack_string_contained($format_info, 'name:')) && empty(canvastack_string_contained($format_info, 'data:'))) {
                                    $format_info = explode(',', $format_info);
                                }

                                if (is_array($format_info)) {
                                    $sQueryData['fields']['type'] = 'multi_values';
                                    $formatData['param_as']['label'] = 'name';
                                    $formatData['param_as']['data_value'] = 'data';

                                    foreach ($format_info as $formatDataInfo) {
                                        $formatInfo = "data:{$formatDataInfo}";

                                        if (canvastack_string_contained($formatInfo, '::')) {
                                            $mathSlices = explode('::', $formatInfo);
                                            if (! empty($mathSlices)) {
                                                foreach ($mathSlices as $as_calc_format) {
                                                    if (canvastack_string_contained($as_calc_format, ':')) {
                                                        $slice = explode(':', $as_calc_format);
                                                        $sQueryData['un_group'][$slice[1]] = $slice[1];
                                                    } else {
                                                        $formatData['calculation_format'] = $as_calc_format;
                                                    }
                                                }
                                                $slicesData = explode(':', $mathSlices[0]);
                                                $sQueryData['fields'][$slicesData[1]] = "{$mathSlices[1]}({$slicesData[1]}) AS `data_value`";
                                            }
                                        } else {
                                            $slice = explode(':', $formatInfo);
                                            $sQueryData['fields'][$slice[1]] = $slice[1];
                                        }
                                    }

                                } else {
                                    if (canvastack_string_contained($format_info, '::')) {
                                        $mathSlices = explode('::', $format_info);
                                        if (! empty($mathSlices)) {
                                            foreach ($mathSlices as $as_calc_format) {
                                                if (canvastack_string_contained($as_calc_format, ':')) {
                                                    $slice = explode(':', $as_calc_format);

                                                    $formatData['param_as'][$slice[1]] = $slice[0];
                                                    $sQueryData['un_group'][$slice[1]] = $slice[1];
                                                } else {
                                                    $formatData['calculation_format'] = $as_calc_format;
                                                }
                                            }

                                            $slicesData = explode(':', $mathSlices[0]);
                                            $sQueryData['fields'][$slicesData[1]] = "{$mathSlices[1]}({$slicesData[1]}) AS `{$slicesData[1]}`";
                                        }
                                    } else {

                                        $slice = explode(':', $format_info);
                                        $formatData['param_as'][$slice[1]] = $slice[0];
                                        $sQueryData['fields'][$slice[1]] = $slice[1];
                                    }
                                }
                            }
                        }

                        $fieldsets = [];
                        $multiValues = false;
                        if (! empty($sQueryData['fields']['type']) && ('multi_values' === $sQueryData['fields']['type'])) {
                            $multiValues = true;
                        }
                        foreach ($sourceData['fieldsets'] as $fieldset) {
                            if (empty($sQueryData['fields'][$fieldset])) {
                                $fieldsets[$fieldset] = "`{$fieldset}`";
                            } else {
                                $fieldsets[$fieldset] = $sQueryData['fields'][$fieldset];
                            }

                            if (! empty($sourceGroup)) {
                                foreach ($sourceGroup as $group) {
                                    if (! empty($fieldsets[$group])) {
                                        $sQueryData['group'][$group] = $fieldsets[$group];
                                    } else {
                                        $sQueryData['group'][$group] = "`{$group}`";
                                    }
                                }
                            }

                            if (! empty($sQueryData['un_group'][$fieldset])) {
                                unset($sQueryData['group'][$fieldset]);
                            }

                            if (! empty($sourceOrder)) {
                                foreach ($sourceOrder as $field_order => $order) {
                                    if (! canvastack_string_contained($order, '`')) {
                                        $str_order = "`{$order}`";
                                    } else {
                                        $str_order = $order;
                                    }

                                    $sQueryData['order'][$field_order] = $str_order;
                                }
                            }
                        }

                        $str_field = implode(', ', $fieldsets);
                        $str_filters = '';
                        $str_group = '';
                        $str_order = '';

                        if ($multiValues) {
                            $fieldsetMultiValues = [];
                            $fieldsetMultiValues['for_fields'] = [];
                            $fieldsetMultiValues['for_values'] = [];

                            foreach ($fieldsets as $fieldLabel => $fieldset) {
                                if (! canvastack_string_contained($fieldset, ') AS')) {
                                    $fieldsetMultiValues['for_fields'][str_replace('`', '', $fieldset)] = $fieldset;
                                } else {
                                    $labelNames = [];
                                    foreach (explode('_', $fieldLabel) as $labelName) {
                                        $labelName = ucwords($labelName);
                                        if (strlen($labelName) <= 4) {
                                            $labelName = strtoupper($labelName);
                                        }
                                        $labelNames[] = $labelName;
                                    }

                                    $labelName = "'".implode(' ', $labelNames)."' AS `label`";

                                    $fieldsetMultiValues['for_values'][$fieldLabel]['label'] = $labelName;
                                    $fieldsetMultiValues['for_values'][$fieldLabel]['values'] = $fieldset;
                                }
                            }

                            $str_field = [];
                            foreach ($fieldsetMultiValues['for_values'] as $field_info => $field_data) {
                                $str_field[$field_info] = implode(', ', array_merge($fieldsetMultiValues['for_fields'], $field_data));
                            }
                        }

                        if (! empty($sourceGroup)) {
                            $str_group = ' GROUP BY '.implode(', ', $sQueryData['group']);
                        }
                        if (! empty($sourceOrder)) {
                            $str_order = ' ORDER BY '.implode(', ', $sQueryData['order']);
                        }
                        /*
                        $sourceFilters = ['region' => 'SOUTH CENTRAL JAVA'];
                        if (!empty($sourceFilters)) $str_filters = ' WHERE ' . array_keys($sourceFilters)[0] . '=' . "'" . array_values($sourceFilters)[0] . "'";
                         */

                        // DATA LINE HERE
                        if (! $multiValues) {
                            $sql = "SELECT {$str_field} FROM {$sourceData['source']}{$str_filters}{$str_group}{$str_order};";
                        } else {
                            $sqli = [];
                            if (! empty($str_field) && is_array($str_field)) {
                                foreach ($str_field as $str_field_info) {
                                    $sqli[] = "SELECT {$str_field_info} FROM {$sourceData['source']}{$str_filters}{$str_group}";
                                }
                            }
                            $sql = implode(' UNION ALL ', $sqli)."{$str_order};";
                        }

                        $queryData = canvastack_query($sql, 'SELECT');
                        $sQueryData['data'] = self::manipulate($chartType, $queryData, $formatData['param_as'], $sourceData['category']);

                        $buffers = [];
                        $buffers['data'] = array_merge_recursive($sQueryData['data'], $this->params[$chartType][$identifier]['attributes']);

                        $this->addParams($chartType, $identifier, 'buffers', $buffers);
                        $this->build($chartType, $identifier, $buffers);
                    }
                }
            }
        }
    }

    private static function manipulate($type, $source, $parameters, $category)
    {
        $combinedType = ['dualAxesLineAndColumn'];
        $typeBasic = $type;
        $typeCombined = null;
        $dashCombined = [null, 'Dash', 'ShortDash', 'Dot', 'ShortDot', 'ShortDashDot', 'LongDash', 'LongDashDot'];

        if (in_array($type, $combinedType)) {
            $typeBasic = 'column';
            $typeCombined = 'spline';
        }

        $paramCharts = [];
        $paramCharts['combine'] = [];
        $paramCharts['legend'] = false;
        foreach ($parameters as $param_field => $param_chart) {
            $paramCharts[$param_chart] = $param_field;
        }

        if (! empty($paramCharts['legend']) && 'true' == $paramCharts['legend']) {
            $paramCharts['legend'] = true;
        } else {
            $paramCharts['legend'] = false;
        }

        $chartData = [];
        $chartData['data'] = [];
        $chartData['category'] = [];
        $chartData['combined'] = [];

        foreach ($source as $data) {
            if (! empty($data->{$category})) {
                $chartData['category'][$data->{$category}] = $data->{$category};
            }
            if (! empty($data->{$paramCharts['name']})) {
                if (! empty($data->{$paramCharts['data']})) {
                    $chartData['data'][$data->{$paramCharts['name']}][] = intval($data->{$paramCharts['data']});
                } else {
                    $chartData['data'][$data->{$paramCharts['name']}][null] = null;
                }
            }

            if (! empty($paramCharts['combine']) && ! empty($data->{$paramCharts['combine']})) {
                if (! empty($data->{$paramCharts['combine']})) {
                    $chartData['combined'][$data->{$paramCharts['name']}][] = intval($data->{$paramCharts['combine']});
                } else {
                    $chartData['combined'][$data->{$paramCharts['name']}][null] = null;
                }
            }
        }

        $buffers = [];
        $buffers['series'] = [];
        $buffers['category'] = [];
        $buffers['combined'] = [];

        foreach ($chartData['category'] as $category) {
            $buffers['category'][] = $category;
        }

        foreach ($chartData['data'] as $name => $data) {
            $buffers['series'][] = [
                'name' => $name,
                'data' => $data,
                'type' => $typeBasic,
            ];
        }

        if (! empty($chartData['combined'])) {
            foreach ($chartData['combined'] as $name => $data) {
                $buffers['combined']['series'][] = [
                    'name' => $name,
                    'data' => $data,
                    'type' => $typeCombined,
                    'dashStyle' => $dashCombined[array_rand($dashCombined)],
                    'showInLegend' => $paramCharts['legend'],
                ];
            }
        }

        $resultData = [];
        $resultData['category'] = $buffers['category'];
        $resultData['series'] = $buffers['series'];
        if (! empty($buffers['combined'])) {
            $resultData['combined'] = $buffers['combined']['series'];
        }

        return $resultData;
    }
}
