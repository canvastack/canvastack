<?php

namespace Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Pipeline\Stages;

use Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Contracts\PipelineStageInterface;
use Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\DTO\DatatablesContext;

class ResolveModelStage implements PipelineStageInterface
{
    /**
     * Minimal Phase 3 logic:
     * - Use inferred tableName and request params to fetch counts and data via DB.
     * - Works best when tableName is resolved by adapter; falls back safely.
     * - This is generic and does not yet apply all legacy filters/joins.
     */
    public function execute(DatatablesContext $context): DatatablesContext
    {
        try {
            // Requirements
            if (empty($context->tableName)) {
                return $context; // cannot resolve without a table
            }

            // Request params
            $req = function_exists('request') ? request() : null;
            $draw = (int) ($req ? $req->get('draw', 0) : 0);
            $start = (int) ($req ? $req->get('start', $context->start) : $context->start);
            $length = (int) ($req ? $req->get('length', $context->length) : $context->length);
            $search = $req ? (string) ($req->input('search.value', '')) : '';

            // DB builder / connection
            if (! function_exists('db')) {
                // Laravel helper not available? try resolve via app
                $db = function_exists('app') ? app('db') : null;
            } else {
                $db = db();
            }
            if (! $db) {
                return $context;
            }

            // Prefer model-defined builder to ensure correct connection and scopes
            $table = $context->tableName;
            $base = null;
            $schema = null;
            $connectionName = null;
            try {
                if (isset($context->datatables->model[$table])) {
                    $modelDef = $context->datatables->model[$table];
                    if (is_array($modelDef)) {
                        // 1) Eloquent model source → use its connection
                        if (($modelDef['type'] ?? null) === 'model' && is_object($modelDef['source'])) {
                            $eloq = $modelDef['source'];
                            $table = $eloq->getTable();
                            $connectionName = method_exists($eloq, 'getConnectionName') ? $eloq->getConnectionName() : null;
                            $base = $eloq->newQuery();
                            try {
                                $schema = $eloq->getConnection()->getSchemaBuilder();
                            } catch (\Throwable $e) {
                                $schema = null;
                            }
                        }
                        // 2) Explicit connection on model definition (e.g., SQL type)
                        if (! $connectionName && isset($modelDef['connection']) && is_string($modelDef['connection']) && $modelDef['connection'] !== '') {
                            $connectionName = $modelDef['connection'];
                        }
                    }
                }
            } catch (\Throwable $e) {
                // fall back to DB builder
            }
            // 3) Fallback: try generic datatables-level connection hint if present
            if (! $connectionName) {
                try {
                    if (isset($context->datatables->connection) && is_string($context->datatables->connection)) {
                        $connectionName = $context->datatables->connection;
                    } elseif (isset($context->datatables->variables['connection']) && is_string($context->datatables->variables['connection'])) {
                        $connectionName = $context->datatables->variables['connection'];
                    }
                } catch (\Throwable $e) { /* ignore */
                }
            }

            if (! $base) {
                // Honor resolved $connectionName when building query
                if ($connectionName) {
                    try {
                        $conn = $db->connection($connectionName);
                        $base = $conn->table($table);
                        try {
                            $schema = $conn->getSchemaBuilder();
                        } catch (\Throwable $e) {
                            $schema = null;
                        }
                    } catch (\Throwable $e) {
                        // Fall back to default connection if named connection fails
                        $base = $db->table($table);
                        try {
                            $schema = $db->connection()->getSchemaBuilder();
                        } catch (\Throwable $e2) {
                            $schema = null;
                        }
                    }
                } else {
                    $base = $db->table($table);
                    try {
                        $schema = $db->connection()->getSchemaBuilder();
                    } catch (\Throwable $e) {
                        $schema = null;
                    }
                }
            }

            // Dynamic connection autodetect: probe configured connections when base is invalid
            try {
                // Quick probe on current base (handles tables or views)
                (clone $base)->limit(1)->get();
            } catch (\Throwable $e) {
                try {
                    $connections = [];
                    try {
                        $connections = array_keys((array) config('database.connections', []));
                    } catch (\Throwable $e2) {
                        $connections = [];
                    }
                    // Ensure default connection is tried first
                    try {
                        $defaultConn = (string) config('database.default');
                        if ($defaultConn && ! in_array($defaultConn, $connections, true)) {
                            array_unshift($connections, $defaultConn);
                        }
                    } catch (\Throwable $e2) { /* ignore */
                    }

                    foreach ($connections as $name) {
                        try {
                            $conn = $db->connection($name);
                            $test = $conn->table($table);
                            (clone $test)->limit(1)->get(); // probe
                            $base = $test;
                            $connectionName = $name;
                            try {
                                $schema = $conn->getSchemaBuilder();
                            } catch (\Throwable $e3) {
                                $schema = null;
                            }
                            if (function_exists('logger')) {
                                try {
                                    logger()->debug('[DT Pipeline] autodetected connection', ['table' => $table, 'conn' => $name]);
                                } catch (\Throwable $e4) {
                                }
                            }
                            break;
                        } catch (\Throwable $e3) {
                            // continue probing next connection
                            continue;
                        }
                    }
                } catch (\Throwable $eIgnore) { /* best-effort only */
                }
            }

            // Total count
            // Defensive: ensure base is a Builder before counting
            try {
                $recordsTotal = (clone $base)->count();
            } catch (\Throwable $e) {
                $recordsTotal = 0;
                if (function_exists('logger')) {
                    try {
                        logger()->debug('[DT Pipeline] count(recordsTotal) failed', ['table' => $table, 'conn' => $connectionName, 'err' => $e->getMessage()]);
                    } catch (\Throwable $e2) {
                    }
                }
            }

            // Apply very basic search on all string-like columns if available in datatables columns
            $filtered = clone $base;

            // Map legacy foreign_keys (joins) and select joined fields
            try {
                if (isset($context->datatables->columns[$table]['foreign_keys']) && is_array($context->datatables->columns[$table]['foreign_keys'])) {
                    $selects = ["{$table}.*"];
                    foreach ($context->datatables->columns[$table]['foreign_keys'] as $fkey1 => $fkey2) {
                        $parts = explode('.', $fkey1);
                        $joinTable = $parts[0] ?? null;
                        if (! $joinTable) {
                            continue;
                        }
                        $filtered = $filtered->leftJoin($joinTable, $fkey1, '=', $fkey2);
                        // Try select columns from joined table using resolved schema (model/app connection)
                        $cols = [];
                        try {
                            if ($schema) {
                                $cols = (array) $schema->getColumnListing($joinTable);
                            }
                        } catch (\Throwable $e) {
                            $cols = [];
                        }
                        if (! empty($cols)) {
                            foreach ($cols as $col) {
                                if ($col === 'id') {
                                    $selects[] = "{$joinTable}.{$col} as {$joinTable}_{$col}";
                                } else {
                                    $selects[] = "{$joinTable}.{$col}";
                                }
                            }
                        }
                    }
                    $filtered = $filtered->select($selects);
                }
            } catch (\Throwable $e) { /* ignore joins if any error */
            }

            $columns = [];
            if (property_exists($context, 'datatables') && isset($context->datatables->columns[$table]['lists'])) {
                $lists = $context->datatables->columns[$table]['lists'];
                if (is_array($lists)) {
                    $columns = $lists;
                }
            }

            if ($search !== '' && ! empty($columns)) {
                $filtered->where(function ($q) use ($columns, $search) {
                    foreach ($columns as $idx => $col) {
                        // Global search: basic LIKE across whitelisted columns
                        if ($idx === 0) {
                            $q->where($col, 'like', "%{$search}%");
                        } else {
                            $q->orWhere($col, 'like', "%{$search}%");
                        }
                    }
                });
            }

            // Per-column search mapping (DataTables columns[i][search][value])
            try {
                if ($req) {
                    $reqCols = $req->input('columns', []);
                    if (is_array($reqCols) && ! empty($reqCols)) {
                        // Build a simple whitelist from known datatables columns.lists
                        $allowed = array_values(array_filter($columns, function ($c) {
                            return is_string($c) && $c !== '';
                        }));
                        foreach ($reqCols as $cdef) {
                            if (! is_array($cdef)) {
                                continue;
                            }
                            $colName = $cdef['data'] ?? null;
                            $colSearchVal = '';
                            if (isset($cdef['search']) && is_array($cdef['search'])) {
                                $colSearchVal = (string) ($cdef['search']['value'] ?? '');
                            }
                            $searchableRaw = $cdef['searchable'] ?? true;
                            $isSearchable = is_bool($searchableRaw) ? $searchableRaw : (strtolower((string) $searchableRaw) !== 'false');
                            if (! $colName || $colSearchVal === '' || ! $isSearchable) {
                                continue;
                            }

                            // Basic sanitization for dotted names; prevent injection via column name
                            if (! preg_match('/^[A-Za-z0-9_\.]+$/', $colName)) {
                                continue;
                            }

                            // Only allow columns present in whitelist, or dotted names (table.col) that appear in whitelist without aliasing
                            $isAllowed = in_array($colName, $allowed, true);
                            if (! $isAllowed && strpos($colName, '.') !== false) {
                                // If whitelist contains the same dotted column, allow it
                                $isAllowed = in_array($colName, $allowed, true);
                            }
                            if (! $isAllowed) {
                                continue;
                            }

                            // Apply AND where per column (DataTables semantics: per-column filters ANDed)
                            $filtered->where($colName, 'like', '%'.$colSearchVal.'%');
                        }
                    }
                }
            } catch (\Throwable $e) { /* ignore per-column mapping errors */
            }

            // Apply legacy static conditions defined on datatables config (where)
            try {
                if (isset($context->datatables->conditions[$table]['where']) && is_array($context->datatables->conditions[$table]['where'])) {
                    $whereO = [];
                    $whereIn = [];
                    foreach ($context->datatables->conditions[$table]['where'] as $conditional) {
                        $field = $conditional['field_name'] ?? null;
                        $op = $conditional['operator'] ?? '=';
                        $val = $conditional['value'] ?? null;
                        if ($field === null) {
                            continue;
                        }
                        if (! is_array($val)) {
                            $whereO[] = [$field, $op, $val];
                        } else {
                            $whereIn[$field] = $val;
                        }
                    }
                    if (! empty($whereO)) {
                        $filtered->where($whereO);
                    }
                    if (! empty($whereIn)) {
                        foreach ($whereIn as $f => $vals) {
                            $filtered->whereIn($f, $vals);
                        }
                    }
                }
            } catch (\Throwable $e) { /* ignore */
            }

            // Map legacy filters (where/applied) similar to legacy Datatables::process()
            // Use context->filters or fallback to request()->all()
            $incoming = $context->filters;
            if (empty($incoming) && $req) {
                $incoming = $req->all();
            }
            $reserved = ['renderDataTables', 'draw', 'columns', 'order', 'start', 'length', 'search', 'difta', '_token', '_', 'filters'];
            $fstrings = [];
            if (is_array($incoming)) {
                foreach ($incoming as $name => $value) {
                    if ($value === '' || in_array($name, $reserved, true)) {
                        continue;
                    }
                    if (! is_array($value)) {
                        $fstrings[] = [$name => urldecode((string) $value)];
                    } else {
                        foreach ($value as $val) {
                            if ($val === '' || $val === null) {
                                continue;
                            }
                            $fstrings[] = [$name => urldecode((string) $val)];
                        }
                    }
                }
            }
            $filterMap = [];
            foreach ($fstrings as $pair) {
                foreach ($pair as $k => $v) {
                    $filterMap[$k][] = $v;
                }
            }
            // Build conditions: prefer last value per field (legacy behavior)
            $fconds = [];
            foreach ($filterMap as $field => $values) {
                foreach ($values as $v) {
                    $fconds[$field] = $v;
                }
            }
            $filtersApplied = false;
            if (! empty($fconds)) {
                $filtered->where($fconds);
                $filtersApplied = true;
            }

            try {
                $recordsFiltered = (clone $filtered)->count();
                // Legacy parity: when filters are applied, legacy sets total == filtered
                if (isset($filtersApplied) && $filtersApplied) {
                    $recordsTotal = $recordsFiltered;
                }
                if (function_exists('logger')) {
                    try {
                        logger()->debug('[DT Pipeline] counts', ['table' => $table, 'conn' => $connectionName, 'recordsTotal' => $recordsTotal, 'recordsFiltered' => $recordsFiltered]);
                    } catch (\Throwable $e2) {
                    }
                }
            } catch (\Throwable $e) {
                $recordsFiltered = 0;
                if (function_exists('logger')) {
                    try {
                        logger()->debug('[DT Pipeline] count(recordsFiltered) failed', ['table' => $table, 'conn' => $connectionName, 'err' => $e->getMessage()]);
                    } catch (\Throwable $e2) {
                    }
                }
            }

            // Multi-order mapping: iterate request.order[] and apply sequential orderBy
            $ordersApplied = false;
            if ($req) {
                $reqCols = $req->input('columns', []);
                $orderReqs = $req->input('order', []);
                if (is_array($orderReqs) && ! empty($orderReqs)) {
                    $allowed = array_values(array_filter($columns, function ($c) {
                        return is_string($c) && $c !== '';
                    }));
                    // Discover actual DB columns for safety (avoid pseudo columns like DT_RowIndex)
                    $schemaCols = [];
                    try {
                        if ($schema) {
                            $schemaCols = (array) $schema->getColumnListing($table);
                        }
                    } catch (\Throwable $e) {
                        $schemaCols = [];
                    }
                    $reservedPseudo = ['DT_RowIndex', 'action', 'no'];
                    foreach ($orderReqs as $o) {
                        if (! is_array($o)) {
                            continue;
                        }
                        $idx = $o['column'] ?? null;
                        if ($idx === null || ! isset($reqCols[$idx]['data'])) {
                            continue;
                        }
                        $colName = (string) $reqCols[$idx]['data'];
                        $dir = strtolower((string) ($o['dir'] ?? 'asc')) === 'desc' ? 'desc' : 'asc';
                        // sanitize and whitelist + schema allowlist, and skip pseudo columns
                        if ($colName === '' || in_array($colName, $reservedPseudo, true) || ! preg_match('/^[A-Za-z0-9_\.]+$/', $colName)) {
                            continue;
                        }
                        $isAllowed = in_array($colName, $allowed, true);
                        if (! $isAllowed) {
                            // allow if present in schema (support dotted table.col)
                            $colBase = strpos($colName, '.') !== false ? explode('.', $colName)[1] ?? $colName : $colName;
                            $isAllowed = in_array($colBase, $schemaCols, true);
                        }
                        if (! $isAllowed) {
                            continue;
                        }
                        $filtered = $filtered->orderBy($colName, $dir);
                        $ordersApplied = true;
                    }
                }
            }
            if (! $ordersApplied) {
                $defaultCol = null;
                // Prefer first request column that is allowed or exists in schema; skip pseudo columns
                if ($req) {
                    $reqCols = $req->input('columns', []);
                    if (is_array($reqCols)) {
                        $allowed = array_values(array_filter($columns, function ($c) {
                            return is_string($c) && $c !== '';
                        }));
                        $schemaCols = [];
                        try {
                            if ($schema) {
                                $schemaCols = (array) $schema->getColumnListing($table);
                            }
                        } catch (\Throwable $e) {
                            $schemaCols = [];
                        }
                        $reservedPseudo = ['DT_RowIndex', 'action', 'no'];
                        foreach ($reqCols as $cdef) {
                            $cname = is_array($cdef) ? ($cdef['data'] ?? null) : null;
                            if (! $cname || in_array($cname, $reservedPseudo, true) || ! preg_match('/^[A-Za-z0-9_\.]+$/', (string) $cname)) {
                                continue;
                            }
                            $ok = (! empty($allowed) && in_array($cname, $allowed, true));
                            if (! $ok) {
                                $colBase = strpos((string) $cname, '.') !== false ? explode('.', (string) $cname)[1] ?? (string) $cname : (string) $cname;
                                $ok = in_array($colBase, $schemaCols, true);
                            }
                            if ($ok) {
                                $defaultCol = (string) $cname;
                                break;
                            }
                        }
                    }
                }
                // Fallback: first declared whitelist column, else first schema column (prefer id), else skip ordering
                if ($defaultCol === null) {
                    if (! empty($columns)) {
                        $defaultCol = (string) reset($columns);
                    } else {
                        $schemaCols = $schemaCols ?? [];
                        if (empty($schemaCols)) {
                            try {
                                if ($schema) {
                                    $schemaCols = (array) $schema->getColumnListing($table);
                                }
                            } catch (\Throwable $e) {
                                $schemaCols = [];
                            }
                        }
                        if (! empty($schemaCols)) {
                            $defaultCol = in_array('id', $schemaCols, true) ? 'id' : (string) reset($schemaCols);
                        }
                    }
                }
                if ($defaultCol) {
                    $filtered = $filtered->orderBy($defaultCol, 'asc');
                }
            }

            // Page data with optional column formatters mapping
            $formatRules = [];
            try {
                if (isset($context->datatables->columns[$table]['format_data']) && is_array($context->datatables->columns[$table]['format_data'])) {
                    $formatRules = $context->datatables->columns[$table]['format_data'];
                }
            } catch (\Throwable $e) {
                $formatRules = [];
            }

            try {
                $rows = $filtered->offset($start)
                    ->limit($length)
                    ->get();
            } catch (\Throwable $e) {
                if (function_exists('logger')) {
                    try {
                        logger()->debug('[DT Pipeline] rows fetch failed', ['table' => $table, 'conn' => $connectionName, 'err' => $e->getMessage()]);
                    } catch (\Throwable $e2) {
                    }
                }
                // fallback: no rows
                $rows = collect([]);
            }

            $data = $rows->map(function ($row) use ($formatRules) {
                // Normalize row into associative array
                if (is_object($row)) {
                    if (method_exists($row, 'getAttributes')) {
                        $arr = (array) $row->getAttributes();
                    } elseif (method_exists($row, 'toArray')) {
                        $arr = (array) $row->toArray();
                    } else {
                        $arr = (array) $row;
                    }
                } else {
                    $arr = (array) $row;
                }

                if (! empty($formatRules) && is_array($formatRules)) {
                    foreach ($formatRules as $field => $fmt) {
                        $fname = $fmt['field_name'] ?? $field;
                        if ($fname === null || $fname === '' || ! array_key_exists($fname, $arr)) {
                            continue;
                        }
                        $val = $arr[$fname];
                        if ($val === null || $val === '') {
                            continue;
                        }
                        $dec = isset($fmt['decimal_endpoint']) ? (int) $fmt['decimal_endpoint'] : 0;
                        $sep = $fmt['separator'] ?? '.';
                        $typ = $fmt['format_type'] ?? 'number';
                        try {
                            if (function_exists('canvastack_format')) {
                                $arr[$fname] = \canvastack_format($val, $dec, $sep, $typ);
                            } else {
                                // Fallback basic number formatting when numeric
                                if (is_numeric($val)) {
                                    $primary = ($sep === '.') ? '.' : ',';
                                    $secondary = ($sep === '.') ? ',' : '.';
                                    $arr[$fname] = number_format((float) $val, $dec, $primary, $secondary);
                                }
                            }
                        } catch (\Throwable $e) { /* ignore formatting errors per cell */
                        }
                    }
                }

                return $arr;
            })
                ->toArray();

            // Fill response if empty
            if (empty($context->response)) {
                $context->response = [
                    'draw' => $draw,
                    'recordsTotal' => $recordsTotal,
                    'recordsFiltered' => $recordsFiltered,
                    'data' => $data,
                ];
            } else {
                // If response exists, enrich minimally
                $context->response['recordsTotal'] = $recordsTotal;
                $context->response['recordsFiltered'] = $recordsFiltered;
                $context->response['data'] = $data;
            }
        } catch (\Throwable $e) {
            // fail silently, let pipeline continue
        }

        return $context;
    }
}
