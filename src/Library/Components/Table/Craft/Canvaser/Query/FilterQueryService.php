<?php

namespace Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Query;

/**
 * FilterQueryService — builds the dynamic SQL used by legacy init_filter_datatables().
 *
 * This preserves the exact legacy behavior (including quirks) while moving
 * the logic out of the Datatables orchestrator.
 */
final class FilterQueryService
{
    /**
     * Execute the legacy filter options query builder.
     *
     * @param  mixed  $connection
     * @return mixed
     */
    public function run(array $get = [], array $post = [], $connection = null)
    {
        if (empty($get['filterDataTables'])) {
            return null;
        }

        if (! empty($post['grabCoDIYC'])) {
            $connection = $post['grabCoDIYC'];
            unset($post['grabCoDIYC']);
        }

        $filters = [];
        if (! empty($post['_diyF'])) {
            $filters = $post['_diyF'];
            unset($post['_diyF']);
        }

        $fdata = explode('::', $post['_fita'] ?? '::::');
        $table = $fdata[1] ?? '';
        $target = $fdata[2] ?? '';
        $prev = $fdata[3] ?? '#null';

        $fKeys = [];
        $fKeyQs = [];
        if (! empty($post['_forKeys'])) {
            $fKeys = json_decode($post['_forKeys'], true);

            if (! empty($fKeys) && is_array($fKeys)) {
                $fKeyQ = [];
                foreach ($fKeys as $fqs => $fqt) {
                    $tqs = explode('.', (string) $fqs);
                    $tqs = $tqs[0] ?? (string) $fqs;

                    $tqt = explode('.', (string) $fqt);
                    $tqt = $tqt[0] ?? (string) $fqt;

                    $fKeyQ[] = "LEFT JOIN {$tqs} ON {$fqs} = {$fqt}";
                }

                if (! empty($fKeyQ)) {
                    $fKeyQs = implode(' ', $fKeyQ);
                }
            }
        }

        // Cleanup reserved keys from POST
        unset($post['filterDataTables']);
        unset($post['_fita']);
        unset($post['_token']);
        unset($post['_n']);
        if (! empty($post['_forKeys'])) {
            unset($post['_forKeys']);
        }

        // Build extra filter queries from _diyF
        $filterQueries = [];
        if (! empty($filters)) {
            foreach ($filters as $n => $filter) {
                $fqFieldName = $filter['field_name'] ?? '';
                $fqDataValue = $filter['value'] ?? '';

                if (is_array($fqDataValue)) {
                    $fQdataValue = implode("', '", $fqDataValue);
                    $filterQueries[$n] = "`{$fqFieldName}` IN ('{$fQdataValue}')";
                } else {
                    $filterQueries[$n] = "`{$fqFieldName}` = '{$fqDataValue}'";
                }
            }
        }

        // Base wheres from remaining POST pairs
        $wheres = [];
        foreach ($post as $key => $value) {
            $wheres[] = "`{$key}` = '{$value}'";
        }
        if (! empty($filterQueries)) {
            $wheres = array_merge_recursive($wheres, $filterQueries);
        }
        $wheres = implode(' AND ', $wheres);

        // Previous filter chain
        $wherePrevious = null;
        if ('#null' !== $prev) {
            $previous = explode('#', (string) $prev);
            $preFields = explode('|', $previous[0] ?? '');
            $preFieldt = explode('|', $previous[1] ?? '');

            $prevields = [];
            foreach ($preFields as $idf => $prev_field) {
                $prevields[$idf] = $prev_field;
            }

            $previeldt = [];
            foreach ($preFieldt as $idd => $prev_field_data) {
                $previeldt[$idd] = $prev_field_data;
            }

            $previousData = [];
            foreach ($prevields as $idp => $prev_data) {
                $previousData[$prev_data] = $previeldt[$idp] ?? null;
            }

            $previousdata = [];
            foreach ($previousData as $_field => $_value) {
                if ($_field !== '') {
                    $previousdata[] = "`{$_field}` = '{$_value}'";
                }
            }

            if (! empty($previousdata)) {
                $wherePrevious = ' AND '.implode(' AND ', $previousdata);
            }
        }

        if (! empty($fKeys)) {
            $sql = "SELECT DISTINCT `{$target}` FROM `{$table}` {$fKeyQs} WHERE {$wheres}{$wherePrevious}";
        } else {
            $sql = "SELECT DISTINCT `{$target}` FROM `{$table}` WHERE {$wheres}{$wherePrevious}";
        }

        return canvastack_query($sql, 'SELECT', $connection);
    }
}
