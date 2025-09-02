<?php

namespace Canvastack\Canvastack\Library\Components\Charts\Canvas;

/**
 * Created on May 24, 2023
 *
 * Time Created : 11:53:27 PM
 *
 * @filesource  DataModel.php
 *
 * @author      wisnuwidi@gmail.com - 2023
 * @copyright   wisnuwidi@gmail.com,
 *              canvastack@gmail.com
 *
 * @email       wisnuwidi@gmail.com,
 *              canvastack@gmail.com
 */
trait DataModel
{
    private $chartData = [];

    private $series = [];

    private $category = [];

    private function construct($data)
    {
        if (! empty($data)) {
            if (! empty($data->info->connection)) {
                $this->connection = $data->info->connection;
            } else {
                $this->connection = 'mysql';
            }

            $sourceData = $data->params;
            $sourceGroup = [];
            if (! empty($sourceData->group)) {
                if (canvastack_string_contained($sourceData->group, ',')) {
                    $sourceGroup = explode(',', str_replace(' ', '', $sourceData->group));
                } else {
                    $sourceGroup = [$sourceData->group];
                }
            }

            if (! empty($sourceData->order)) {
                if (canvastack_string_contained($sourceData->order, ',')) {
                    $dataOrders = explode(',', str_replace(' ', '', $sourceData->order));
                } else {
                    $dataOrders = [$sourceData->order];
                }

                foreach ($dataOrders as $orderData) {
                    if (canvastack_string_contained($orderData, '::')) {
                        $splitOrder = explode('::', $orderData);
                        $sourceOrder[$splitOrder[0]] = "`{$splitOrder[0]}` {$splitOrder[1]}";
                    }
                }
            }

            if (! empty($sourceData->format)) {

                $formatData = [];
                $formatData['param_as'] = [];
                $formatData['calculation_format'] = [];

                $sQueryData = [];
                $sQueryData['fields'] = [];
                $sQueryData['group'] = [];
                $sQueryData['un_group'] = [];
                $sQueryData['order'] = [];
                $sQueryData['data'] = [];

                $data_format = explode('|', $sourceData->format);

                foreach ($data_format as $format_info) {
                    if (! empty($format_info)) {
                        if (empty(canvastack_string_contained($format_info, 'name:')) && empty(canvastack_string_contained($format_info, 'data:'))) {
                            $format_info = explode(',', $format_info);
                        }

                        if (is_array($format_info)) {
                            $sQueryData['fields']['type'] = 'multi_values';
                            $formatData['param_as']['label'] = 'name';
                            $formatData['param_as']['data_value'] = 'data';
                            $formatData['param_as']['chart_type'] = 'type';

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
                $multiValues = false; // Query With UNION
                if (! empty($sQueryData['fields']['type']) && ('multi_values' === $sQueryData['fields']['type'])) {
                    $multiValues = true;
                }
                $chartTypeField = [];
                foreach ($sourceData->fields as $fieldset) {
                    $chartTypeField[$fieldset] = $sourceData->type;
                    if (canvastack_string_contained($fieldset, '::')) {
                        unset($chartTypeField[$fieldset]);

                        $fieldsplits = explode('::', $fieldset);
                        $fieldset = $fieldsplits[0];
                        $chartTypeField[$fieldset] = $fieldsplits[1];
                    }

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
                            $chartType = "'{$chartTypeField[$fieldLabel]}' AS chart_type";

                            $fieldsetMultiValues['for_values'][$fieldLabel]['label'] = $labelName;
                            $fieldsetMultiValues['for_values'][$fieldLabel]['values'] = $fieldset;
                            $fieldsetMultiValues['for_values'][$fieldLabel]['chart_type'] = $chartType;
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

                $dataFilters = [];
                if (! empty($data->params->filter)) {
                    foreach ($data->params->filter as $filterData) {
                        foreach ($filterData as $n => $filter) {
                            if (is_array($filter->value) && count($filter->value) > 1) {
                                $filter->operator = 'IN';
                                $filter->value = "('".implode("', '", $filter->value)."')";
                            } else {
                                $filter->value = "'{$filter->value}'";
                            }

                            if ($n <= 0) {
                                $dataFilters[] = "WHERE {$filter->field_name} {$filter->operator} {$filter->value}";
                            } else {
                                $dataFilters[] = "AND {$filter->field_name} {$filter->operator} {$filter->value}";
                            }
                        }
                    }
                }

                $pageFilters = [];
                if (! empty($data->params->page_filter)) {
                    foreach ($data->params->page_filter as $pageFilterData) {
                        foreach ($pageFilterData as $nf => $pageFilter) {
                            if (is_array($pageFilter->value) && count($pageFilter->value) > 1) {
                                $pageFilter->operator = 'IN';
                                $pageFilter->value = "('".implode("', '", $pageFilter->value)."')";
                            } else {
                                $pageFilter->value = "'{$pageFilter->value}'";
                            }

                            if ($nf <= 0) {
                                if (! empty($dataFilters)) {
                                    $pageFilters[] = "AND {$pageFilter->field_name} {$pageFilter->operator} {$pageFilter->value}";
                                } else {
                                    $pageFilters[] = "WHERE {$pageFilter->field_name} {$pageFilter->operator} {$pageFilter->value}";
                                }
                            } else {
                                $pageFilters[] = "AND {$pageFilter->field_name} {$pageFilter->operator} {$pageFilter->value}";
                            }
                        }
                    }
                }

                if (! empty($pageFilters)) {
                    $str_filters = ' '.implode(' ', $dataFilters).' '.implode(' ', $pageFilters);
                } else {
                    $str_filters = ' '.implode(' ', $dataFilters);
                }

                $sourceTable = $sourceData->source;
                if (canvastack_string_contained($sourceData->source, 'diyChartDataFilter::')) {
                    $sourceTable = canvastack_decrypt(str_replace('diyChartDataFilter::', '', $sourceData->source));
                }

                // DATA LINE HERE
                if (! $multiValues) {
                    $sql = "SELECT {$str_field} FROM {$sourceTable}{$str_filters}{$str_group}{$str_order};";
                } else {
                    $sqli = [];
                    if (! empty($str_field) && is_array($str_field)) {
                        foreach ($str_field as $str_field_info) {
                            $sqli[] = "SELECT {$str_field_info} FROM {$sourceTable}{$str_filters}{$str_group}";
                        }
                    }
                    $sql = implode(' UNION ALL ', $sqli)."{$str_order};";
                }

                $queryData = canvastack_query($sql, 'SELECT', $this->connection);
                $sQueryData['data'] = self::manipulate($sourceData->type, $queryData, $formatData['param_as'], $sourceData->category);

                $this->chartData[$this->chartPostData]['data'] = $sQueryData;
                $this->category[$this->chartPostData]['category'] = $sQueryData['data']['category'];
                $this->series[$this->chartPostData]['series'] = $sQueryData['data']['series'];

                return $this;
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
        $chartData['type'] = [];
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
                    $chartData['data'][$data->{$paramCharts['name']}][] = null;
                }
            }

            if (! empty($data->{$paramCharts['type']})) {
                if (! empty($data->{$paramCharts['type']})) {
                    $chartData['type'][$data->{$paramCharts['name']}] = $data->{$paramCharts['type']};
                } else {
                    $chartData['type'][$data->{$paramCharts['name']}] = $typeBasic;
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
            $chartType = $typeBasic;
            if (! empty($chartData['type'][$name])) {
                $chartType = $chartData['type'][$name];
            }
            if (canvastack_string_contained($chartType, 'line')) {
                $buffers['series'][] = [
                    'name' => $name,
                    'data' => $data,
                    'type' => $chartType,
                    'dashStyle' => $dashCombined[array_rand($dashCombined)],
                ];
            } else {
                $buffers['series'][] = [
                    'name' => $name,
                    'data' => $data,
                    'type' => $chartType,
                ];
            }
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
