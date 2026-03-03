<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Console\Commands;

use Canvastack\Canvastack\Support\Localization\TranslationLoader;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * TranslationExportCommand.
 *
 * Export translations to JSON or CSV format.
 */
class TranslationExportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'canvastack:translate:export
                            {--locale= : Locale to export (default: all)}
                            {--group= : Translation group to export (default: all)}
                            {--format=json : Export format (json, csv, php, xlsx)}
                            {--output= : Output file path}
                            {--include-vendor : Include vendor translations}
                            {--flatten : Flatten nested arrays (for CSV)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export translations to JSON or CSV format';

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
        $this->info('Exporting translations...');
        $this->newLine();

        $locale = $this->option('locale');
        $group = $this->option('group');
        $format = $this->option('format');

        // Get locales to export
        $locales = $locale ? [$locale] : $this->getLoader()->getAvailableLocales();

        if (empty($locales)) {
            $this->error('No locales found to export.');

            return self::FAILURE;
        }

        // Export translations
        $translations = $this->collectTranslations($locales, $group);

        if (empty($translations)) {
            $this->error('No translations found to export.');

            return self::FAILURE;
        }

        // Display statistics
        $this->displayStatistics($translations);

        // Export to file
        $outputPath = $this->getOutputPath($format, $locale);
        $this->exportTranslations($translations, $outputPath, $format);

        $this->newLine();
        $this->info('Translations exported successfully!');
        $this->info("Output: {$outputPath}");

        return self::SUCCESS;
    }

    /**
     * Collect translations.
     *
     * @param  array<string>  $locales
     * @param  string|null  $group
     * @return array<string, array<string, mixed>>
     */
    protected function collectTranslations(array $locales, ?string $group): array
    {
        $translations = [];
        $includeVendor = $this->option('include-vendor');

        $progressBar = $this->output->createProgressBar(count($locales));
        $progressBar->start();

        foreach ($locales as $locale) {
            $translations[$locale] = [];

            if ($group) {
                // Export specific group
                $data = $this->getLoader()->load($locale, $group);
                if (!empty($data)) {
                    $translations[$locale][$group] = $data;
                }
            } else {
                // Export all groups
                $groups = $this->getLoader()->getGroups($locale);

                foreach ($groups as $groupName) {
                    // Skip vendor groups if not included
                    if (!$includeVendor && str_contains($groupName, '::')) {
                        continue;
                    }

                    $data = $this->getLoader()->load($locale, $groupName);
                    if (!empty($data)) {
                        $translations[$locale][$groupName] = $data;
                    }
                }
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();

        return $translations;
    }

    /**
     * Display statistics.
     *
     * @param  array<string, array<string, mixed>>  $translations
     * @return void
     */
    protected function displayStatistics(array $translations): void
    {
        $totalKeys = 0;
        $totalGroups = 0;

        foreach ($translations as $locale => $groups) {
            foreach ($groups as $group => $keys) {
                $totalGroups++;
                $totalKeys += $this->countKeys($keys);
            }
        }

        $this->info('Statistics:');
        $this->info('===========');
        $this->info('Locales: ' . count($translations));
        $this->info('Groups: ' . $totalGroups);
        $this->info('Total keys: ' . $totalKeys);
        $this->newLine();

        $this->info('By Locale:');
        foreach ($translations as $locale => $groups) {
            $keys = 0;
            foreach ($groups as $groupData) {
                $keys += $this->countKeys($groupData);
            }
            $this->line("  {$locale}: {$keys} keys");
        }
    }

    /**
     * Count keys in array (recursive).
     *
     * @param  array<string, mixed>  $array
     * @return int
     */
    protected function countKeys(array $array): int
    {
        $count = 0;

        foreach ($array as $value) {
            if (is_array($value)) {
                $count += $this->countKeys($value);
            } else {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Get output path.
     *
     * @param  string  $format
     * @param  string|null  $locale
     * @return string
     */
    protected function getOutputPath(string $format, ?string $locale): string
    {
        if ($this->option('output')) {
            return $this->option('output');
        }

        $filename = $locale ? "translations-{$locale}.{$format}" : "translations-all.{$format}";

        return storage_path("app/translations/{$filename}");
    }

    /**
     * Export translations to file.
     *
     * @param  array<string, array<string, mixed>>  $translations
     * @param  string  $path
     * @param  string  $format
     * @return void
     */
    protected function exportTranslations(array $translations, string $path, string $format): void
    {
        // Ensure directory exists
        $directory = dirname($path);
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        match ($format) {
            'json' => $this->exportJson($translations, $path),
            'csv' => $this->exportCsv($translations, $path),
            'php' => $this->exportPhp($translations, $path),
            'xlsx' => $this->exportXlsx($translations, $path),
            default => $this->exportJson($translations, $path),
        };
    }

    /**
     * Export to JSON.
     *
     * @param  array<string, array<string, mixed>>  $translations
     * @param  string  $path
     * @return void
     */
    protected function exportJson(array $translations, string $path): void
    {
        $data = [
            'exported_at' => now()->toDateTimeString(),
            'locales' => array_keys($translations),
            'translations' => $translations,
        ];

        File::put($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * Export to CSV.
     *
     * @param  array<string, array<string, mixed>>  $translations
     * @param  string  $path
     * @return void
     */
    protected function exportCsv(array $translations, string $path): void
    {
        $handle = fopen($path, 'w');

        if ($handle === false) {
            $this->error("Failed to open file: {$path}");

            return;
        }

        // Get all locales
        $locales = array_keys($translations);

        // Header
        $header = ['Key', 'Group'];
        foreach ($locales as $locale) {
            $header[] = $locale;
        }
        fputcsv($handle, $header);

        // Collect all keys
        $allKeys = $this->collectAllKeys($translations);

        // Data
        foreach ($allKeys as $group => $keys) {
            foreach ($keys as $key => $fullKey) {
                $row = [$key, $group];

                foreach ($locales as $locale) {
                    $value = $this->getNestedValue($translations[$locale][$group] ?? [], $key);
                    $row[] = $value ?? '';
                }

                fputcsv($handle, $row);
            }
        }

        fclose($handle);
    }

    /**
     * Export to PHP.
     *
     * @param  array<string, array<string, mixed>>  $translations
     * @param  string  $path
     * @return void
     */
    protected function exportPhp(array $translations, string $path): void
    {
        $data = [
            'exported_at' => now()->toDateTimeString(),
            'locales' => array_keys($translations),
            'translations' => $translations,
        ];

        $export = "<?php\n\nreturn " . var_export($data, true) . ";\n";
        File::put($path, $export);
    }

    /**
     * Export to XLSX (placeholder - requires additional library).
     *
     * @param  array<string, array<string, mixed>>  $translations
     * @param  string  $path
     * @return void
     */
    protected function exportXlsx(array $translations, string $path): void
    {
        $this->warn('XLSX export requires PhpSpreadsheet library.');
        $this->warn('Falling back to CSV export...');

        $csvPath = str_replace('.xlsx', '.csv', $path);
        $this->exportCsv($translations, $csvPath);
    }

    /**
     * Collect all keys from all locales.
     *
     * @param  array<string, array<string, mixed>>  $translations
     * @return array<string, array<string, string>>
     */
    protected function collectAllKeys(array $translations): array
    {
        $allKeys = [];

        foreach ($translations as $locale => $groups) {
            foreach ($groups as $group => $keys) {
                if (!isset($allKeys[$group])) {
                    $allKeys[$group] = [];
                }

                $flatKeys = $this->flattenKeys($keys);
                foreach ($flatKeys as $key => $value) {
                    $allKeys[$group][$key] = $value;
                }
            }
        }

        return $allKeys;
    }

    /**
     * Flatten nested array keys.
     *
     * @param  array<string, mixed>  $array
     * @param  string  $prefix
     * @return array<string, string>
     */
    protected function flattenKeys(array $array, string $prefix = ''): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            $newKey = $prefix ? "{$prefix}.{$key}" : $key;

            if (is_array($value)) {
                $result = array_merge($result, $this->flattenKeys($value, $newKey));
            } else {
                $result[$newKey] = (string) $value;
            }
        }

        return $result;
    }

    /**
     * Get nested value from array.
     *
     * @param  array<string, mixed>  $array
     * @param  string  $key
     * @return string|null
     */
    protected function getNestedValue(array $array, string $key): ?string
    {
        $keys = explode('.', $key);
        $value = $array;

        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return null;
            }
            $value = $value[$k];
        }

        return is_string($value) ? $value : null;
    }
}
