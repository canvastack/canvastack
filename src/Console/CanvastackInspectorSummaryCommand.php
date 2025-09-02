<?php

namespace Canvastack\Canvastack\Console;

use Illuminate\Console\Command;

class CanvastackInspectorSummaryCommand extends Command
{
    protected $signature = 'canvastack:inspector:summary
        {route? : Optional named route to filter (e.g., modules.incentive.incentive.index)}
        {--output= : Output Markdown file path}';

    protected $description = 'Produce a Markdown summary from datatable-inspector JSON files, optionally filtered by route name.';

    public function handle(): int
    {
        $base = base_path();
        $scriptFiltered = $base.DIRECTORY_SEPARATOR.'scripts'.DIRECTORY_SEPARATOR.'hybrid-diff-summary-filtered.php';
        $scriptGlobal = $base.DIRECTORY_SEPARATOR.'scripts'.DIRECTORY_SEPARATOR.'hybrid-diff-summary.php';

        $route = $this->argument('route');
        $output = $this->option('output') ?: ($base.DIRECTORY_SEPARATOR.'_inspector_summary.md');

        $php = PHP_BINARY ?: 'php';
        try {
            if ($route) {
                if (! is_file($scriptFiltered)) {
                    $this->error('Script not found: '.$scriptFiltered);

                    return self::FAILURE;
                }
                $cmd = sprintf('%s %s %s %s',
                    escapeshellarg($php),
                    escapeshellarg($scriptFiltered),
                    escapeshellarg($route),
                    escapeshellarg($output)
                );
            } else {
                if (! is_file($scriptGlobal)) {
                    $this->error('Script not found: '.$scriptGlobal);

                    return self::FAILURE;
                }
                $cmd = sprintf('%s %s %s',
                    escapeshellarg($php),
                    escapeshellarg($scriptGlobal),
                    escapeshellarg($output)
                );
            }

            $this->info('Running: '.$cmd);
            $lines = [];
            $code = 0;
            exec($cmd.' 2>&1', $lines, $code);
            $this->line(implode(PHP_EOL, $lines));

            return $code === 0 ? self::SUCCESS : self::FAILURE;
        } catch (\Throwable $e) {
            $this->error('[InspectorSummary] Error: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
