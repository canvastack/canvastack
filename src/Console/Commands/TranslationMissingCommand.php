<?php

namespace Canvastack\Canvastack\Console\Commands;

use Canvastack\Canvastack\Support\Localization\MissingTranslationDetector;
use Illuminate\Console\Command;

/**
 * TranslationMissingCommand.
 *
 * Detect and report missing translations.
 */
class TranslationMissingCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'canvastack:translation:missing
                            {action : Action to perform (list, report, export, clear)}
                            {--locale= : Filter by locale}
                            {--format=json : Export format (json, csv)}
                            {--path= : Export path}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Detect and report missing translations';

    /**
     * Missing translation detector.
     *
     * @var MissingTranslationDetector
     */
    protected MissingTranslationDetector $detector;

    /**
     * Constructor.
     */
    public function __construct(MissingTranslationDetector $detector)
    {
        parent::__construct();
        $this->detector = $detector;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $action = $this->argument('action');

        return match ($action) {
            'list' => $this->listMissing(),
            'report' => $this->showReport(),
            'export' => $this->exportReport(),
            'clear' => $this->clearMissing(),
            default => $this->error("Unknown action: {$action}"),
        };
    }

    /**
     * List missing translations.
     *
     * @return int
     */
    protected function listMissing(): int
    {
        $locale = $this->option('locale');

        $missing = $locale
            ? $this->detector->getByLocale($locale)
            : $this->detector->all();

        if (empty($missing)) {
            $this->info('No missing translations found!');

            return self::SUCCESS;
        }

        $this->info('Missing Translations:');
        $this->info('====================');

        foreach ($missing as $item) {
            $this->line("[{$item['locale']}] {$item['key']} (count: {$item['count']})");
        }

        return self::SUCCESS;
    }

    /**
     * Show missing translations report.
     *
     * @return int
     */
    protected function showReport(): int
    {
        $report = $this->detector->generateReport();

        $this->info('Missing Translations Report');
        $this->info('===========================');
        $this->info("Generated at: {$report['generated_at']}");
        $this->newLine();

        $stats = $report['statistics'];
        $this->info('Statistics:');
        $this->info("  Total missing: {$stats['total_missing']}");
        $this->info("  Total occurrences: {$stats['total_occurrences']}");
        $this->newLine();

        $this->info('By Locale:');
        foreach ($stats['by_locale'] as $locale => $count) {
            $this->info("  {$locale}: {$count}");
        }
        $this->newLine();

        $this->info('By Group:');
        foreach ($stats['by_group'] as $group => $count) {
            $this->info("  {$group}: {$count}");
        }
        $this->newLine();

        $this->info('Most Frequent (Top 10):');
        foreach ($report['most_frequent'] as $item) {
            $this->line("  [{$item['locale']}] {$item['key']} (count: {$item['count']})");
        }

        return self::SUCCESS;
    }

    /**
     * Export missing translations report.
     *
     * @return int
     */
    protected function exportReport(): int
    {
        $format = $this->option('format');
        $path = $this->option('path') ?? storage_path("app/translations/missing.{$format}");

        $this->info("Exporting report to: {$path}");

        if ($this->detector->exportReport($path, $format)) {
            $this->info('Report exported successfully!');

            return self::SUCCESS;
        }

        $this->error('Failed to export report.');

        return self::FAILURE;
    }

    /**
     * Clear missing translations.
     *
     * @return int
     */
    protected function clearMissing(): int
    {
        $locale = $this->option('locale');

        if ($this->confirm('Are you sure you want to clear missing translations?')) {
            $this->detector->clear($locale);
            $this->info('Missing translations cleared!');

            return self::SUCCESS;
        }

        return self::SUCCESS;
    }
}
