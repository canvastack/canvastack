<?php

namespace Canvastack\Canvastack\Console;

use Illuminate\Console\Command;

class CanvastackSnapshotMakeCommand extends Command
{
    protected $signature = 'canvastack:snapshot:make {table} {--lists=*} {--blacklist=*} {--orderby=} {--tolerance=} {--path=}';

    protected $description = 'Generate a snapshot JSON template for datatable-inspector.';

    public function handle(): int
    {
        $base = base_path();
        $defaultPath = $base.DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'datatable-inspector';
        $path = $this->option('path') ?: $defaultPath;
        if (! is_dir($path)) {
            @mkdir($path, 0777, true);
        }
        if (! is_dir($path)) {
            $this->error('Cannot create target directory: '.$path);

            return self::FAILURE;
        }

        $table = (string) $this->argument('table');
        $lists = $this->option('lists') ?: ['id', 'name'];
        $blacklist = $this->option('blacklist') ?: ['password', 'action', 'no'];
        $orderby = $this->option('orderby') ?: (count($lists) ? $lists[0] : 'id');
        $tolerance = $this->option('tolerance');

        $payload = [
            'table_name' => $table,
            'columns' => [
                'lists' => array_values($lists),
                'blacklist' => array_values($blacklist),
                'orderby' => ['column' => $orderby, 'order' => 'desc'],
            ],
            'joins' => [
                'foreign_keys' => new \stdClass(),
                'selected' => ["{$table}.*"],
            ],
            'filters' => ['where' => [], 'applied' => [], 'raw_params' => []],
            'paging' => ['start' => 0, 'length' => 10, 'total' => 0],
        ];
        if ($tolerance !== null && $tolerance !== '') {
            $payload['tolerance'] = is_numeric($tolerance) ? (0 + $tolerance) : $tolerance;
        }

        $filename = $path.DIRECTORY_SEPARATOR.$table.'_'.date('Ymd_His').'.json';
        $ok = (bool) file_put_contents($filename, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        if (! $ok) {
            $this->error('Failed to write file: '.$filename);

            return self::FAILURE;
        }

        $this->info('Snapshot generated: '.$filename);

        return self::SUCCESS;
    }
}
