<?php

namespace Canvastack\Canvastack\Library\Components\Utility\Table;

final class Filters
{
    /**
     * Normalize filters structure as legacy canvastack_filter_data_normalizer
     */
    public static function normalize(array $filters = []): array
    {
        $filterData = [];
        foreach ($filters as $filter) {
            if (is_array($filter['value'])) {
                foreach ($filter['value'] as $val) {
                    $filterData[$filter['field_name']]['value'][][] = $val;
                }
            } else {
                $filterData[$filter['field_name']]['value'][][] = $filter['value'];
            }
        }

        $_filters = [];
        foreach ($filterData as $node => $nodeValues) {
            $_filters[$node]['field_name'] = $node;
            $_filters[$node]['operator'] = '=';
            foreach ($nodeValues['value'] as $values) {
                $_filters[$node]['value'][] = $values[0];
            }
        }
        $filterData = [];
        foreach ($_filters as $data) {
            $filterData[] = $data;
        }
        return $filterData;
    }
}