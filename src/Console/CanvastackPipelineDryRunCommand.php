<?php

namespace Canvastack\Canvastack\Console;

use Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Pipeline\DatatablesPipeline;
use Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Support\LegacyContextAdapter;
use Illuminate\Console\Command;

class CanvastackPipelineDryRunCommand extends Command
{
    protected $signature = 'canvastack:pipeline:dry-run
        {--method= : JSON string or file path for legacy method (expects difta.name for table)}
        {--datatables= : JSON string or file path for datatables config}
        {--filters= : JSON string or file path for filters map}
        {--filter-page= : JSON string or file path for filter_page map}
        {--table= : Shortcut to set method.difta.name when method missing}
        {--pretty : Pretty-print JSON output}';

    protected $description = 'Run Datatables pipeline with provided legacy-like inputs (debug/dry-run).';

    public function handle(): int
    {
        try {
            $method = $this->readJsonOption('method');
            $datatables = $this->readJsonOption('datatables');
            $filters = $this->readJsonOption('filters');
            $filter_page = $this->readJsonOption('filter-page');
            $tableOpt = (string) ($this->option('table') ?? '');

            if (! is_array($method)) {
                $method = [];
            }
            if ($tableOpt !== '' && empty($method['difta']['name'])) {
                $method['difta']['name'] = $tableOpt;
            }
            if (! is_array($filters)) {
                $filters = [];
            }
            if (! is_array($filter_page)) {
                $filter_page = [];
            }

            if (! is_array($datatables)) {
                $this->warn('No datatables config provided. Provide --datatables JSON/file with columns.{table}.lists at minimum.');
                $datatables = [];
            }

            $datatablesObj = json_decode(json_encode($datatables), false); // to object recursively

            $adapter = new LegacyContextAdapter();
            $context = $adapter->fromLegacyInputs($method, $datatablesObj, $filters, $filter_page);

            $pipeline = new DatatablesPipeline();
            $context = $pipeline->run($context);

            $result = [
                'table' => $context->tableName,
                'start' => $context->start,
                'length' => $context->length,
                'response' => $context->response,
            ];

            $jsonFlags = $this->option('pretty') ? JSON_PRETTY_PRINT : 0;
            $this->line(json_encode($result, $jsonFlags));
            if (empty($context->response)) {
                $this->warn('Pipeline response is empty. Ensure DB connection and table exist or verify datatables config.');
            }

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('[DryRun] Error: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    private function readJsonOption(string $name): mixed
    {
        $raw = $this->option($name);
        if ($raw === null || $raw === '') {
            return null;
        }
        // If looks like existing file path, load
        if (is_string($raw) && file_exists($raw)) {
            $raw = file_get_contents($raw) ?: '';
        }
        if (! is_string($raw) || trim($raw) === '') {
            return null;
        }
        $decoded = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->warn("Option --{$name} is not valid JSON. Error: ".json_last_error_msg());

            return null;
        }

        return $decoded;
    }
}
