<?php

namespace Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Query;

class WhereConditionsNormalizer
{
    /**
     * Normalize raw where conditions into the expected structure.
     * Legacy-compatible output.
     */
    public static function normalize(array $raw): array
    {
        $whereConds = [];
        foreach ($raw as $w) {
            $whereConds[$w['field_name']][$w['operator']]['field_name'][$w['field_name']] = $w['field_name'];
            $whereConds[$w['field_name']][$w['operator']]['operator'][$w['operator']] = $w['operator'];
            $whereConds[$w['field_name']][$w['operator']]['values'][] = $w['value'];
        }

        $whereConditions = [];
        foreach ($whereConds as $field => $ops) {
            foreach ($ops as $op => $data) {
                foreach ($data as $key => $values) {
                    if ('values' === $key) {
                        if (is_array($values)) {
                            foreach ($values as $v) {
                                if (is_array($v)) {
                                    foreach ($v as $_v) {
                                        $whereConditions[$field][$op][$key][$_v] = $_v;
                                    }
                                } else {
                                    $whereConditions[$field][$op][$key][$v] = $v;
                                }
                            }
                        }
                    } else {
                        $whereConditions[$field][$op][$key] = $values;
                    }
                }
            }
        }

        $whereConditionals = [];
        foreach ($whereConditions as $fname => $opData) {
            foreach ($opData as $op => $cond) {
                $whereConditionals[$fname][$op]['field_name'] = $fname;
                $whereConditionals[$fname][$op]['operator'] = $op;
                $whereConditionals[$fname][$op]['value'] = $cond['values'] ?? [];
            }
        }

        $whereDataConditions = [];
        foreach ($whereConditionals as $byField) {
            foreach ($byField as $set) {
                $whereDataConditions[] = $set;
            }
        }

        return $whereDataConditions;
    }
}