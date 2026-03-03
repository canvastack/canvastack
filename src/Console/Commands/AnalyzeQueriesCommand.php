<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Console\Commands;

use Canvastack\Canvastack\Support\Performance\QueryMonitor;
use Illuminate\Console\Command;

/**
 * AnalyzeQueriesCommand - Analyze query performance and generate report.
 */
class AnalyzeQueriesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'canvastack:analyze-queries
                            {--threshold=1000 : Slow query threshold in milliseconds}
                            {--limit=50 : Query count threshold}
                            {--export= : Export report to file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Analyze database queries and generate performance report';

    /**
     * Execute the console command.
     *
     * @param QueryMonitor $monitor
     * @return int
     */
    public function handle(QueryMonitor $monitor): int
    {
        $threshold = (float) $this->option('threshold');
        $limit = (int) $this->option('limit');

        $monitor->setSlowQueryThreshold($threshold);
        $monitor->setQueryCountThreshold($limit);

        $this->info('Analyzing queries...');
        $this->newLine();

        $monitor->start();

        // Simulate some queries (in real usage, this would be during actual app execution)
        $this->info('Run your application to generate queries, then press Enter to generate report.');
        $this->ask('Press Enter when ready');

        $monitor->stop();

        $report = $monitor->generateReport();

        $this->displayReport($report);

        // Export if requested
        if ($exportPath = $this->option('export')) {
            $this->exportReport($report, $exportPath);
        }

        return Command::SUCCESS;
    }

    /**
     * Display the performance report.
     *
     * @param array<string, mixed> $report
     * @return void
     */
    protected function displayReport(array $report): void
    {
        $this->newLine();
        $this->info('=== Query Performance Report ===');
        $this->newLine();

        // Summary
        $this->info('Summary:');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Queries', $report['summary']['total_queries']],
                ['Slow Queries', $report['summary']['slow_queries']],
                ['Duplicate Queries', $report['summary']['duplicate_queries']],
                ['Total Time', $report['summary']['total_time']],
                ['Average Time', $report['summary']['average_time']],
            ]
        );
        $this->newLine();

        // Slow Queries
        if (!empty($report['slow_queries'])) {
            $this->warn('Slow Queries:');
            foreach ($report['slow_queries'] as $query) {
                $this->line("  - {$query['sql']} ({$query['time']}ms)");
            }
            $this->newLine();
        }

        // Duplicate Queries
        if (!empty($report['duplicate_queries'])) {
            $this->warn('Duplicate Queries:');
            foreach ($report['duplicate_queries'] as $duplicate) {
                $this->line("  - {$duplicate['sql']} (executed {$duplicate['count']} times)");
            }
            $this->newLine();
        }

        // N+1 Problems
        if (!empty($report['n1_problems'])) {
            $this->error('N+1 Query Problems:');
            foreach ($report['n1_problems'] as $problem) {
                $this->line("  - {$problem['pattern']} (executed {$problem['count']} times)");
            }
            $this->newLine();
        }

        // Recommendations
        $this->info('Recommendations:');
        foreach ($report['recommendations'] as $recommendation) {
            $this->line("  • {$recommendation}");
        }
        $this->newLine();
    }

    /**
     * Export report to file.
     *
     * @param array<string, mixed> $report
     * @param string $path
     * @return void
     */
    protected function exportReport(array $report, string $path): void
    {
        $json = json_encode($report, JSON_PRETTY_PRINT);
        file_put_contents($path, $json);

        $this->info("Report exported to: {$path}");
    }
}
