<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Console\Commands;

use Canvastack\Canvastack\Support\Localization\TranslationLoader;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * TranslationCoverageCommand.
 *
 * Generate translation coverage report.
 */
class TranslationCoverageCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'canvastack:translate:coverage
                            {--locale= : Compare specific locale against base locale}
                            {--base=en : Base locale for comparison}
                            {--format=table : Output format (table, json, html)}
                            {--output= : Output file path}
                            {--threshold=80 : Minimum coverage threshold (0-100)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate translation coverage report';

    /**
     * Translation loader.
     *
     * @var TranslationLoader|null
     */
    protected ?TranslationLoader $loader = null;

    /**
     * Get translation loader.
     *
     * @return TranslationLoader
     */
    protected function getLoader(): TranslationLoader
    {
        if ($this->loader === null) {
            $this->loader = app(TranslationLoader::class);
        }

        return $this->loader;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Generating translation coverage report...');
        $this->newLine();

        $baseLocale = $this->option('base');
        $targetLocale = $this->option('locale');

        // Get locales to analyze
        $locales = $targetLocale ? [$targetLocale] : $this->getLoader()->getAvailableLocales();
        $locales = array_filter($locales, fn ($l) => $l !== $baseLocale);

        if (empty($locales)) {
            $this->error('No locales found to analyze.');

            return self::FAILURE;
        }

        // Generate coverage report
        $report = $this->generateCoverageReport($baseLocale, $locales);

        // Display report
        $format = $this->option('format');
        $this->displayReport($report, $format);

        // Check threshold
        $threshold = (int) $this->option('threshold');
        $passed = $this->checkThreshold($report, $threshold);

        if (!$passed) {
            $this->newLine();
            $this->error("Coverage below threshold ({$threshold}%)");

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * Generate coverage report.
     *
     * @param  string  $baseLocale
     * @param  array<string>  $locales
     * @return array<string, mixed>
     */
    protected function generateCoverageReport(string $baseLocale, array $locales): array
    {
        $report = [
            'generated_at' => now()->toDateTimeString(),
            'base_locale' => $baseLocale,
            'locales' => [],
            'summary' => [
                'total_locales' => count($locales),
                'average_coverage' => 0,
                'total_keys' => 0,
                'total_translated' => 0,
                'total_missing' => 0,
            ],
        ];

        // Get base translations
        $baseTranslations = $this->collectTranslations($baseLocale);
        $report['summary']['total_keys'] = $this->countKeys($baseTranslations);

        $progressBar = $this->output->createProgressBar(count($locales));
        $progressBar->start();

        $totalCoverage = 0;

        foreach ($locales as $locale) {
            $localeReport = $this->analyzeLocale($locale, $baseTranslations);
            $report['locales'][$locale] = $localeReport;

            $totalCoverage += $localeReport['coverage'];
            $report['summary']['total_translated'] += $localeReport['translated'];
            $report['summary']['total_missing'] += $localeReport['missing'];

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();

        $report['summary']['average_coverage'] = count($locales) > 0
            ? round($totalCoverage / count($locales), 2)
            : 0;

        return $report;
    }

    /**
     * Analyze locale coverage.
     *
     * @param  string  $locale
     * @param  array<string, mixed>  $baseTranslations
     * @return array<string, mixed>
     */
    protected function analyzeLocale(string $locale, array $baseTranslations): array
    {
        $localeTranslations = $this->collectTranslations($locale);

        $totalKeys = $this->countKeys($baseTranslations);
        $translatedKeys = 0;
        $missingKeys = [];
        $emptyKeys = [];

        foreach ($baseTranslations as $group => $keys) {
            $this->compareKeys(
                $keys,
                $localeTranslations[$group] ?? [],
                $group,
                $translatedKeys,
                $missingKeys,
                $emptyKeys
            );
        }

        $coverage = $totalKeys > 0 ? round(($translatedKeys / $totalKeys) * 100, 2) : 0;

        return [
            'total' => $totalKeys,
            'translated' => $translatedKeys,
            'missing' => count($missingKeys),
            'empty' => count($emptyKeys),
            'coverage' => $coverage,
            'missing_keys' => $missingKeys,
            'empty_keys' => $emptyKeys,
        ];
    }

    /**
     * Compare keys recursively.
     *
     * @param  array<string, mixed>  $baseKeys
     * @param  array<string, mixed>  $localeKeys
     * @param  string  $prefix
     * @param  int  &$translatedCount
     * @param  array<string>  &$missingKeys
     * @param  array<string>  &$emptyKeys
     * @return void
     */
    protected function compareKeys(
        array $baseKeys,
        array $localeKeys,
        string $prefix,
        int &$translatedCount,
        array &$missingKeys,
        array &$emptyKeys
    ): void {
        foreach ($baseKeys as $key => $value) {
            $fullKey = $prefix ? "{$prefix}.{$key}" : $key;

            if (is_array($value)) {
                $this->compareKeys(
                    $value,
                    $localeKeys[$key] ?? [],
                    $fullKey,
                    $translatedCount,
                    $missingKeys,
                    $emptyKeys
                );
            } else {
                if (!isset($localeKeys[$key])) {
                    $missingKeys[] = $fullKey;
                } elseif (empty($localeKeys[$key])) {
                    $emptyKeys[] = $fullKey;
                } else {
                    $translatedCount++;
                }
            }
        }
    }

    /**
     * Collect translations for locale.
     *
     * @param  string  $locale
     * @return array<string, array<string, mixed>>
     */
    protected function collectTranslations(string $locale): array
    {
        $translations = [];
        $groups = $this->getLoader()->getGroups($locale);

        foreach ($groups as $group) {
            $data = $this->getLoader()->load($locale, $group);
            if (!empty($data)) {
                $translations[$group] = $data;
            }
        }

        return $translations;
    }

    /**
     * Count keys in translations.
     *
     * @param  array<string, mixed>  $translations
     * @return int
     */
    protected function countKeys(array $translations): int
    {
        $count = 0;

        foreach ($translations as $value) {
            if (is_array($value)) {
                $count += $this->countKeys($value);
            } else {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Display report.
     *
     * @param  array<string, mixed>  $report
     * @param  string  $format
     * @return void
     */
    protected function displayReport(array $report, string $format): void
    {
        match ($format) {
            'table' => $this->displayTable($report),
            'json' => $this->displayJson($report),
            'html' => $this->displayHtml($report),
            default => $this->displayTable($report),
        };
    }

    /**
     * Display report as table.
     *
     * @param  array<string, mixed>  $report
     * @return void
     */
    protected function displayTable(array $report): void
    {
        $this->info('Translation Coverage Report');
        $this->info('==========================');
        $this->info("Generated: {$report['generated_at']}");
        $this->info("Base Locale: {$report['base_locale']}");
        $this->newLine();

        // Summary
        $summary = $report['summary'];
        $this->info('Summary:');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Locales', $summary['total_locales']],
                ['Total Keys', $summary['total_keys']],
                ['Total Translated', $summary['total_translated']],
                ['Total Missing', $summary['total_missing']],
                ['Average Coverage', $summary['average_coverage'] . '%'],
            ]
        );
        $this->newLine();

        // Per-locale coverage
        $this->info('Coverage by Locale:');
        $rows = [];
        foreach ($report['locales'] as $locale => $data) {
            $rows[] = [
                $locale,
                $data['total'],
                $data['translated'],
                $data['missing'],
                $data['empty'],
                $data['coverage'] . '%',
            ];
        }

        $this->table(
            ['Locale', 'Total', 'Translated', 'Missing', 'Empty', 'Coverage'],
            $rows
        );

        // Show missing keys for locales with low coverage
        foreach ($report['locales'] as $locale => $data) {
            if ($data['coverage'] < 80 && !empty($data['missing_keys'])) {
                $this->newLine();
                $this->warn("Missing keys for {$locale} (showing first 10):");
                $sample = array_slice($data['missing_keys'], 0, 10);
                foreach ($sample as $key) {
                    $this->line("  - {$key}");
                }
                if (count($data['missing_keys']) > 10) {
                    $remaining = count($data['missing_keys']) - 10;
                    $this->line("  ... and {$remaining} more");
                }
            }
        }
    }

    /**
     * Display report as JSON.
     *
     * @param  array<string, mixed>  $report
     * @return void
     */
    protected function displayJson(array $report): void
    {
        $json = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $outputPath = $this->option('output');
        if ($outputPath) {
            File::put($outputPath, $json);
            $this->info("Report saved to: {$outputPath}");
        } else {
            $this->line($json);
        }
    }

    /**
     * Display report as HTML.
     *
     * @param  array<string, mixed>  $report
     * @return void
     */
    protected function displayHtml(array $report): void
    {
        $html = $this->generateHtmlReport($report);

        $outputPath = $this->option('output') ?? storage_path('app/translations/coverage-report.html');

        // Ensure directory exists
        $directory = dirname($outputPath);
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        File::put($outputPath, $html);
        $this->info("HTML report saved to: {$outputPath}");
    }

    /**
     * Generate HTML report.
     *
     * @param  array<string, mixed>  $report
     * @return string
     */
    protected function generateHtmlReport(array $report): string
    {
        $summary = $report['summary'];

        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Translation Coverage Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        h1 { color: #333; }
        .summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0; }
        .card { background: #f9f9f9; padding: 15px; border-radius: 5px; border-left: 4px solid #6366f1; }
        .card h3 { margin: 0 0 10px 0; color: #666; font-size: 14px; }
        .card .value { font-size: 24px; font-weight: bold; color: #333; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #6366f1; color: white; }
        tr:hover { background: #f5f5f5; }
        .coverage { font-weight: bold; }
        .coverage.high { color: #10b981; }
        .coverage.medium { color: #f59e0b; }
        .coverage.low { color: #ef4444; }
        .timestamp { color: #666; font-size: 14px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Translation Coverage Report</h1>
        <p class="timestamp">Generated: {$report['generated_at']}</p>
        <p class="timestamp">Base Locale: {$report['base_locale']}</p>
        
        <div class="summary">
            <div class="card">
                <h3>Total Locales</h3>
                <div class="value">{$summary['total_locales']}</div>
            </div>
            <div class="card">
                <h3>Total Keys</h3>
                <div class="value">{$summary['total_keys']}</div>
            </div>
            <div class="card">
                <h3>Average Coverage</h3>
                <div class="value">{$summary['average_coverage']}%</div>
            </div>
            <div class="card">
                <h3>Total Missing</h3>
                <div class="value">{$summary['total_missing']}</div>
            </div>
        </div>
        
        <h2>Coverage by Locale</h2>
        <table>
            <thead>
                <tr>
                    <th>Locale</th>
                    <th>Total Keys</th>
                    <th>Translated</th>
                    <th>Missing</th>
                    <th>Empty</th>
                    <th>Coverage</th>
                </tr>
            </thead>
            <tbody>
HTML;

        foreach ($report['locales'] as $locale => $data) {
            $coverageClass = $data['coverage'] >= 80 ? 'high' : ($data['coverage'] >= 50 ? 'medium' : 'low');

            $html .= <<<HTML
                <tr>
                    <td><strong>{$locale}</strong></td>
                    <td>{$data['total']}</td>
                    <td>{$data['translated']}</td>
                    <td>{$data['missing']}</td>
                    <td>{$data['empty']}</td>
                    <td class="coverage {$coverageClass}">{$data['coverage']}%</td>
                </tr>
HTML;
        }

        $html .= <<<HTML
            </tbody>
        </table>
    </div>
</body>
</html>
HTML;

        return $html;
    }

    /**
     * Check if coverage meets threshold.
     *
     * @param  array<string, mixed>  $report
     * @param  int  $threshold
     * @return bool
     */
    protected function checkThreshold(array $report, int $threshold): bool
    {
        $averageCoverage = $report['summary']['average_coverage'];

        return $averageCoverage >= $threshold;
    }
}
