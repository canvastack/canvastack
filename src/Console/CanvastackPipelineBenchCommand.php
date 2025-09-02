<?php

namespace Canvastack\Canvastack\Console;

use Illuminate\Console\Command;

class CanvastackPipelineBenchCommand extends Command
{
    protected $signature = 'canvastack:pipeline:bench
                            {table : Table name to benchmark}
                            {--connection= : Connection name override}
                            {--length=30 : Page length}
                            {--start=0 : Offset}
                            {--search= : Global search value}
                            {--filters=* : Extra filters as key=value (repeatable)}
                            {--order=* : Order spec as column:dir (repeatable)}
                            {--repeat=3 : Number of runs to average}
                            {--warmup=1 : Number of warm-up runs to ignore}
                            {--probe : Run a raw DB probe (count + first row) before pipeline}
                            {--json : Output JSON summary only}';

    protected $description = 'Benchmark Datatables pipeline for a given table with optional filters and ordering.';

    public function handle(): int
    {
        $table = (string) $this->argument('table');
        if ($table === '') {
            $this->error('Table is required');

            return self::FAILURE;
        }

        $repeat = max(1, (int) $this->option('repeat'));
        $warmup = max(0, (int) $this->option('warmup'));
        $length = (int) $this->option('length');
        $start = (int) $this->option('start');
        $search = (string) ($this->option('search') ?? '');
        $conn = $this->option('connection');

        // Build faux request payload compatible with ResolveModelStage
        $columns = [];
        try {
            // Best-effort: infer columns from schema if possible
            $schema = $conn ? \DB::connection($conn)->getSchemaBuilder() : \DB::connection()->getSchemaBuilder();
            $cols = (array) $schema->getColumnListing($table);
            foreach ($cols as $c) {
                $columns[] = ['data' => $c, 'name' => $c, 'searchable' => true, 'orderable' => true, 'search' => ['value' => '', 'regex' => false]];
            }
        } catch (\Throwable $e) { /* ignore */
        }

        $order = [];
        foreach ((array) $this->option('order') as $spec) {
            // format: column:dir, e.g., id:desc
            $parts = explode(':', (string) $spec, 2);
            $col = $parts[0] ?? null;
            $dir = $parts[1] ?? 'asc';
            if ($col) {
                // Map to column index if present in $columns
                $idx = 0;
                foreach ($columns as $i => $def) {
                    if (($def['data'] ?? null) === $col) {
                        $idx = $i;
                        break;
                    }
                }
                $order[] = ['column' => $idx, 'dir' => strtolower($dir) === 'desc' ? 'desc' : 'asc'];
            }
        }

        $filters = [];
        foreach ((array) $this->option('filters') as $kv) {
            $pair = explode('=', (string) $kv, 2);
            if (count($pair) === 2) {
                $filters[$pair[0]] = $pair[1];
            }
        }

        // Build minimal context via adapter
        $adapterClass = 'Canvastack\\Canvastack\\Library\\Components\\Table\\Craft\\Datatables\\Support\\LegacyContextAdapter';
        $pipelineClass = 'Canvastack\\Canvastack\\Library\\Components\\Table\\Craft\\Datatables\\Pipeline\\DatatablesPipeline';
        $contextClass = 'Canvastack\\Canvastack\\Library\\Components\\Table\\Craft\\Datatables\\DTO\\DatatablesContext';

        if (! class_exists($adapterClass) || ! class_exists($pipelineClass) || ! class_exists($contextClass)) {
            $this->error('Pipeline classes not available.');

            return self::FAILURE;
        }

        // Simulate request parameters for the stage
        $payload = [
            'draw' => 1,
            'start' => $start,
            'length' => $length,
            'search' => ['value' => $search, 'regex' => false],
            'columns' => $columns,
            'order' => $order,
        ];

        // Inject connection hint if provided
        $dt = (object) [
            'model' => [$table => ['type' => 'sql', 'source' => $table, 'connection' => $conn]],
            'columns' => [$table => ['lists' => array_map(fn ($c) => $c['data'], $columns)]],
            'variables' => ['connection' => $conn],
        ];

        $data = (object) ['datatables' => $dt, 'table_name' => $table];
        $method = ['table' => $table, 'difta' => ['name' => $table]];

        $adapter = new $adapterClass();
        $pipeline = new $pipelineClass();

        // Optional raw DB probe
        $probe = null;
        if ($this->option('probe')) {
            try {
                $builder = $conn ? \DB::connection($conn)->table($table) : \DB::table($table);
                foreach ($filters as $k => $v) {
                    $builder->where($k, $v);
                }
                $probeCount = (clone $builder)->count();
                // Choose a safe order column: prefer filter key, then 'id', else first column name
                $orderCol = null;
                if (! empty($filters)) {
                    $orderCol = array_key_first($filters);
                } else {
                    $names = array_values(array_filter(array_map(fn ($c) => $c['data'] ?? null, $columns)));
                    if (in_array('id', $names, true)) {
                        $orderCol = 'id';
                    } elseif (! empty($names)) {
                        $orderCol = $names[0];
                    }
                }
                $q = (clone $builder);
                if ($orderCol) {
                    $q->orderBy($orderCol);
                }
                $first = $q->limit(1)->first();
                $probe = ['count' => $probeCount, 'first' => $first];
            } catch (\Throwable $e) {
                $probe = ['error' => $e->getMessage()];
            }
        }

        // Bind a fake request using Symfony InputBag if available
        $runs = [];
        for ($i = 0; $i < $repeat; $i++) {
            $startTs = microtime(true);
            try {
                // Best-effort: set request globals temporarily
                request()->merge($payload + $filters);
                $ctx = $adapter->fromLegacyInputs($method, $data, $filters, []);
                // Force tableName on context if adapter inference fails
                $ctx->tableName = $table;
                $ctx = $pipeline->run($ctx);
                $durMs = (microtime(true) - $startTs) * 1000.0;
                $rows = is_array($ctx->response['data'] ?? null) ? count($ctx->response['data']) : 0;
                $runs[] = ['ms' => $durMs, 'rows' => $rows, 'recordsTotal' => $ctx->response['recordsTotal'] ?? null, 'recordsFiltered' => $ctx->response['recordsFiltered'] ?? null];
            } catch (\Throwable $e) {
                $durMs = (microtime(true) - $startTs) * 1000.0;
                $runs[] = ['ms' => $durMs, 'error' => $e->getMessage()];
            }
        }

        // Compute stats excluding warmup runs
        $valid = array_slice($runs, max(0, min($warmup, count($runs))));
        $times = array_map(fn ($r) => (float) ($r['ms'] ?? 0), $valid);
        sort($times);
        $avgMs = count($times) ? array_sum($times) / count($times) : 0.0;
        $minMs = count($times) ? min($times) : 0.0;
        $maxMs = count($times) ? max($times) : 0.0;
        $median = count($times) ? ($times[(int) floor((count($times) - 1) / 2)] + $times[(int) ceil((count($times) - 1) / 2)]) / 2 : 0.0;
        $p95 = 0.0;
        if (count($times)) {
            $idx = (int) floor(0.95 * (count($times) - 1));
            $p95 = $times[$idx];
        }

        $summary = [
            'table' => $table,
            'connection' => $conn,
            'repeat' => $repeat,
            'length' => $length,
            'start' => $start,
            'search' => $search,
            'filters' => $filters,
            'order' => $this->option('order'),
            'stats' => [
                'avg_ms' => round($avgMs, 2),
                'min_ms' => round($minMs, 2),
                'max_ms' => round($maxMs, 2),
                'median_ms' => round($median, 2),
                'p95_ms' => round($p95, 2),
                'warmup_ignored' => $warmup,
            ],
            'runs' => $runs,
            'probe' => $probe,
        ];

        if ($this->option('json')) {
            $this->line(json_encode($summary, JSON_PRETTY_PRINT));
        } else {
            $this->info('Benchmark Summary');
            $this->line('Table: '.$table.($conn ? (' @ '.$conn) : ''));
            $this->line('Repeat: '.$repeat.' — Length: '.$length.' — Start: '.$start);
            $this->line('Avg: '.round($avgMs, 2).' ms — Min: '.round($minMs, 2).' ms — Max: '.round($maxMs, 2).' ms');
        }

        return self::SUCCESS;
    }
}
