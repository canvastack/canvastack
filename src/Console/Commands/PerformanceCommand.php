<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Console\Commands;

use Canvastack\Canvastack\Support\Performance\PerformanceDashboard;
use Illuminate\Console\Command;

/**
 * Performance Command.
 *
 * Artisan command for monitoring and optimizing performance.
 */
class PerformanceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'canvastack:performance
                            {action : The action to perform (overview|benchmark|warmup|clear|score)}
                            {--export : Export results to JSON file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor and optimize CanvaStack performance';

    /**
     * Performance dashboard instance.
     */
    protected PerformanceDashboard $dashboard;

    /**
     * Create a new command instance.
     */
    public function __construct(PerformanceDashboard $dashboard)
    {
        parent::__construct();
        $this->dashboard = $dashboard;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $action = $this->argument('action');

        return match ($action) {
            'overview' => $this->showOverview(),
            'benchmark' => $this->runBenchmark(),
            'warmup' => $this->warmupCaches(),
            'clear' => $this->clearCaches(),
            'score' => $this->showScore(),
            default => $this->error("Unknown action: {$action}"),
        };
    }

    /**
     * Show performance overview.
     */
    protected function showOverview(): int
    {
        $this->info('Performance Overview');
        $this->line('');

        $overview = $this->dashboard->getOverview();

        // System performance
        $this->line('<fg=cyan>System Performance:</>');
        $system = $overview['system'];
        $this->line("  Memory Usage: {$system['memory_usage_mb']} MB");
        $this->line("  Memory Peak: {$system['memory_peak_mb']} MB");
        $this->line("  Memory Limit: {$system['memory_limit']}");
        $this->line('');

        // Theme cache stats
        if ($overview['themes']) {
            $this->line('<fg=cyan>Theme Cache:</>');
            $themes = $overview['themes'];
            $this->line("  Total Themes: {$themes['total_themes']}");
            $this->line("  CSS Cache Ratio: {$themes['css_cache_ratio']}%");
            $this->line("  Config Cache Ratio: {$themes['config_cache_ratio']}%");
            $this->line("  Preloaded: {$themes['preloaded_themes']}");
            $this->line('');
        }

        // Translation cache stats
        if ($overview['translations']) {
            $this->line('<fg=cyan>Translation Cache:</>');
            $translations = $overview['translations'];
            $this->line("  Total Locales: {$translations['total_locales']}");
            $this->line("  Locale Cache Ratio: {$translations['locale_cache_ratio']}%");
            $this->line("  Namespace Cache Ratio: {$translations['namespace_cache_ratio']}%");
            $this->line("  Preloaded: {$translations['preloaded_locales']}");
            $this->line('');
        }

        // Recommendations
        $this->showRecommendations($overview['recommendations']);

        if ($this->option('export')) {
            $this->exportToFile($overview, 'performance-overview');
        }

        return 0;
    }

    /**
     * Run performance benchmark.
     */
    protected function runBenchmark(): int
    {
        $this->info('Running Performance Benchmark...');
        $this->line('');

        $results = $this->dashboard->runBenchmark();

        // Theme benchmark
        if (isset($results['benchmarks']['themes'])) {
            $this->line('<fg=cyan>Theme Benchmark:</>');
            $themes = $results['benchmarks']['themes'];
            $summary = $themes['summary'];
            $this->line("  Total Themes: {$themes['total_themes']}");
            $this->line("  Avg Uncached: {$summary['avg_uncached_ms']} ms");
            $this->line("  Avg Cached: {$summary['avg_cached_ms']} ms");
            $this->line("  Improvement: {$summary['avg_improvement_percent']}%");
            $this->line('');
        }

        // Translation benchmark
        if (isset($results['benchmarks']['translations'])) {
            $this->line('<fg=cyan>Translation Benchmark:</>');
            $translations = $results['benchmarks']['translations'];
            $summary = $translations['summary'];
            $this->line("  Total Locales: {$translations['total_locales']}");
            $this->line("  Avg Uncached: {$summary['avg_uncached_ms']} ms");
            $this->line("  Avg Cached: {$summary['avg_cached_ms']} ms");
            $this->line("  Improvement: {$summary['avg_improvement_percent']}%");
            $this->line('');
        }

        $this->info("Benchmark completed in {$results['duration_seconds']} seconds");

        if ($this->option('export')) {
            $this->exportToFile($results, 'performance-benchmark');
        }

        return 0;
    }

    /**
     * Warm up all caches.
     */
    protected function warmupCaches(): int
    {
        $this->info('Warming up caches...');
        $this->line('');

        $results = $this->dashboard->warmupAll();

        // Theme warmup
        if (isset($results['warmup']['themes'])) {
            $themes = $results['warmup']['themes'];
            $this->line("<fg=green>Themes:</> {$themes['cached']}/{$themes['total']} cached in {$themes['time_ms']} ms");
        }

        // Translation warmup
        if (isset($results['warmup']['translations'])) {
            $translations = $results['warmup']['translations'];
            $this->line("<fg=green>Translations:</> {$translations['cached']}/{$translations['total']} cached in {$translations['time_ms']} ms");
        }

        // Integration warmup
        if (isset($results['warmup']['integration'])) {
            $integration = $results['warmup']['integration'];
            $this->line("<fg=green>Integration:</> {$integration['cached']}/{$integration['total']} cached in {$integration['time_ms']} ms");
        }

        $this->line('');
        $this->info("Warmup completed in {$results['duration_seconds']} seconds");

        return 0;
    }

    /**
     * Clear all caches.
     */
    protected function clearCaches(): int
    {
        $this->info('Clearing caches...');

        $results = $this->dashboard->clearAll();

        $this->line('');
        $this->line('<fg=green>Cleared:</>');
        foreach ($results['cleared'] as $component) {
            $this->line("  - {$component}");
        }

        $this->line('');
        $this->info('All caches cleared successfully');

        return 0;
    }

    /**
     * Show performance score.
     */
    protected function showScore(): int
    {
        $this->info('Performance Score');
        $this->line('');

        $score = $this->dashboard->getPerformanceScore();

        $color = match ($score['grade']) {
            'A' => 'green',
            'B' => 'cyan',
            'C' => 'yellow',
            'D' => 'magenta',
            'F' => 'red',
        };

        $this->line("<fg={$color}>Score: {$score['score']}/100 (Grade: {$score['grade']})</>");
        $this->line("<fg={$color}>Status: {$score['status']}</>");
        $this->line('');

        if (!empty($score['deductions'])) {
            $this->line('<fg=yellow>Deductions:</>');
            foreach ($score['deductions'] as $deduction) {
                $this->line("  - {$deduction}");
            }
            $this->line('');
        }

        // Show recommendations
        $overview = $this->dashboard->getOverview();
        $this->showRecommendations($overview['recommendations']);

        if ($this->option('export')) {
            $this->exportToFile($score, 'performance-score');
        }

        return 0;
    }

    /**
     * Show recommendations.
     */
    protected function showRecommendations(array $recommendations): void
    {
        $hasRecommendations = false;

        foreach ($recommendations as $category => $items) {
            if (!empty($items)) {
                $hasRecommendations = true;
                break;
            }
        }

        if (!$hasRecommendations) {
            $this->line('<fg=green>No recommendations - performance is optimal!</>');

            return;
        }

        $this->line('<fg=yellow>Recommendations:</>');

        foreach ($recommendations as $category => $items) {
            if (empty($items)) {
                continue;
            }

            $this->line('');
            $this->line("  <fg=cyan>{$category}:</>");

            foreach ($items as $item) {
                $color = match ($item['type']) {
                    'critical' => 'red',
                    'warning' => 'yellow',
                    'info' => 'cyan',
                    default => 'white',
                };

                $this->line("    <fg={$color}>[{$item['type']}]</> {$item['message']}");
                $this->line("      → {$item['suggestion']}");
            }
        }

        $this->line('');
    }

    /**
     * Export results to JSON file.
     */
    protected function exportToFile(array $data, string $filename): void
    {
        $timestamp = date('Y-m-d_H-i-s');
        $filepath = storage_path("logs/{$filename}_{$timestamp}.json");

        file_put_contents($filepath, json_encode($data, JSON_PRETTY_PRINT));

        $this->line('');
        $this->info("Results exported to: {$filepath}");
    }
}
