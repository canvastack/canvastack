<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Console\Commands;

use Canvastack\Canvastack\Support\Performance\QueryMonitor;
use Illuminate\Console\Command;

/**
 * MonitorQueriesCommand - Monitor database queries in real-time.
 */
class MonitorQueriesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'canvastack:monitor-queries
                            {--threshold=1000 : Slow query threshold in milliseconds}
                            {--limit=50 : Query count threshold}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor database queries and detect performance issues';

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

        $this->info('Starting query monitoring...');
        $this->info("Slow query threshold: {$threshold}ms");
        $this->info("Query count threshold: {$limit}");
        $this->newLine();

        $monitor->start();

        $this->info('Monitoring enabled. Press Ctrl+C to stop and generate report.');
        $this->newLine();

        // Keep the command running
        while (true) {
            sleep(1);
        }

        return Command::SUCCESS;
    }
}
