<?php

namespace Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Query;

use Canvastack\Canvastack\Library\Components\Table\Exceptions\SecurityException;

/**
 * FilterQueryService — builds the dynamic SQL used by legacy init_filter_datatables().
 *
 * This preserves the exact legacy behavior (including quirks) while moving
 * the logic out of the Datatables orchestrator.
 */
final class FilterQueryService
{
    /** @var array */
    private $bindings = [];

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

        // Build extra filter queries from _diyF (SECURE VERSION)
        $filterQueries = [];
        if (! empty($filters)) {
            foreach ($filters as $n => $filter) {
                $fqFieldName = $filter['field_name'] ?? '';
                $fqDataValue = $filter['value'] ?? '';

                // Validate field name for security
                $this->validateFieldName($fqFieldName);

                if (is_array($fqDataValue)) {
                    // Use parameter binding for arrays
                    $placeholders = str_repeat('?,', count($fqDataValue) - 1) . '?';
                    $filterQueries[$n] = "`{$fqFieldName}` IN ({$placeholders})";
                    $this->bindings = array_merge($this->bindings, $fqDataValue);
                } else {
                    // Use parameter binding for single values
                    $filterQueries[$n] = "`{$fqFieldName}` = ?";
                    $this->bindings[] = $fqDataValue;
                }
            }
        }

        // Base wheres from remaining POST pairs (SECURE VERSION)
        $wheres = [];
        foreach ($post as $key => $value) {
            // Validate field name for security
            $this->validateFieldName($key);
            $wheres[] = "`{$key}` = ?";
            $this->bindings[] = $value;
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
                    // Validate field name to prevent SQL injection
                    $this->validateFieldName($_field);
                    $previousdata[] = "`{$_field}` = ?";
                    $this->bindings[] = $_value;
                }
            }

            if (! empty($previousdata)) {
                $wherePrevious = ' AND '.implode(' AND ', $previousdata);
            }
        }

        // Validate table and target field names
        $this->validateTableName($table);
        $this->validateFieldName($target);

        if (! empty($fKeys)) {
            $sql = "SELECT DISTINCT `{$target}` FROM `{$table}` {$fKeyQs} WHERE {$wheres}{$wherePrevious}";
        } else {
            $sql = "SELECT DISTINCT `{$target}` FROM `{$table}` WHERE {$wheres}{$wherePrevious}";
        }

        return $this->executeSecureQuery($sql, $connection);
    }

    /**
     * Validate field name to prevent SQL injection
     *
     * @param string $fieldName
     * @throws \InvalidArgumentException
     */
    private function validateFieldName(string $fieldName): void
    {
        // Check basic format - must start with letter, contain only alphanumeric and underscore
        if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $fieldName)) {
            throw new \InvalidArgumentException("Invalid field name format: {$fieldName}");
        }

        // Check length limit
        if (strlen($fieldName) > 64) {
            throw new \InvalidArgumentException("Field name too long: {$fieldName}");
        }

        // Optional: Check against whitelist if configured
        $allowedFields = config('canvastack.security.allowed_fields', []);
        if (!empty($allowedFields) && !in_array($fieldName, $allowedFields, true)) {
            \Log::warning("Unauthorized field access attempt: {$fieldName}", [
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]);
            throw new SecurityException("Field not allowed: {$fieldName}");
        }
    }

    /**
     * Validate table name to prevent SQL injection
     *
     * @param string $tableName
     * @throws \InvalidArgumentException
     */
    private function validateTableName(string $tableName): void
    {
        // Check basic format
        if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $tableName)) {
            throw new \InvalidArgumentException("Invalid table name format: {$tableName}");
        }

        // Check length limit
        if (strlen($tableName) > 64) {
            throw new \InvalidArgumentException("Table name too long: {$tableName}");
        }

        // Optional: Check against whitelist if configured
        $allowedTables = config('canvastack.security.allowed_tables', []);
        if (!empty($allowedTables) && !in_array($tableName, $allowedTables, true)) {
            \Log::warning("Unauthorized table access attempt: {$tableName}", [
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]);
            throw new SecurityException("Table not allowed: {$tableName}");
        }
    }

    /**
     * Execute secure query with parameter binding
     *
     * @param string $sql
     * @param mixed $connection
     * @return mixed
     */
    private function executeSecureQuery(string $sql, $connection = null)
    {
        try {
            // Use Laravel's DB facade with parameter binding
            if ($connection) {
                return \DB::connection($connection)->select($sql, $this->bindings);
            } else {
                return \DB::select($sql, $this->bindings);
            }
        } catch (\Exception $e) {
            // Log security-related database errors
            \Log::error("Database query error in FilterQueryService", [
                'error' => $e->getMessage(),
                'sql' => $sql,
                'bindings_count' => count($this->bindings),
                'ip' => request()->ip()
            ]);
            
            // Re-throw with safe message to prevent information disclosure
            throw new \RuntimeException("Database query failed");
        }
    }
}
