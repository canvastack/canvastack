<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Console\Commands;

use Canvastack\Canvastack\Support\Localization\TranslationLoader;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * TranslationImportCommand.
 *
 * Import translations from JSON or CSV files.
 */
class TranslationImportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'canvastack:translate:import
                            {file : Path to import file}
                            {--format= : Import format (json, csv, php) - auto-detected if not specified}
                            {--locale= : Target locale (required for CSV)}
                            {--group= : Target group (required for CSV)}
                            {--merge : Merge with existing translations instead of replacing}
                            {--backup : Create backup before importing}
                            {--dry-run : Preview changes without applying}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import translations from JSON or CSV files';

    /**
     * Translation loader.
     *
     * @var TranslationLoader
     */
    protected TranslationLoader $loader;

    /**
     * Constructor.
     */
    public function __construct(TranslationLoader $loader)
    {
        parent::__construct();
        $this->loader = $loader;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $file = $this->argument('file');

        if (!File::exists($file)) {
            $this->error("File not found: {$file}");

            return self::FAILURE;
        }

        $this->info('Importing translations...');
        $this->newLine();

        // Detect or get format
        $format = $this->option('format') ?? $this->detectFormat($file);

        if (!$format) {
            $this->error('Could not detect file format. Please specify --format option.');

            return self::FAILURE;
        }

        // Create backup if requested
        if ($this->option('backup')) {
            $this->createBackup();
        }

        // Import translations
        $translations = $this->importFile($file, $format);

        if (empty($translations)) {
            $this->error('No translations found in file.');

            return self::FAILURE;
        }

        // Display preview
        $this->displayPreview($translations);

        // Dry run check
        if ($this->option('dry-run')) {
            $this->warn('Dry run mode - no changes applied.');

            return self::SUCCESS;
        }

        // Confirm import
        if (!$this->confirm('Do you want to import these translations?')) {
            $this->info('Import cancelled.');

            return self::SUCCESS;
        }

        // Apply translations
        $this->applyTranslations($translations);

        $this->newLine();
        $this->info('Translations imported successfully!');

        return self::SUCCESS;
    }

    /**
     * Detect file format from extension.
     *
     * @param  string  $file
     * @return string|null
     */
    protected function detectFormat(string $file): ?string
    {
        $extension = pathinfo($file, PATHINFO_EXTENSION);

        return match ($extension) {
            'json' => 'json',
            'csv' => 'csv',
            'php' => 'php',
            default => null,
        };
    }

    /**
     * Import file.
     *
     * @param  string  $file
     * @param  string  $format
     * @return array<string, array<string, mixed>>
     */
    protected function importFile(string $file, string $format): array
    {
        return match ($format) {
            'json' => $this->importJson($file),
            'csv' => $this->importCsv($file),
            'php' => $this->importPhp($file),
            default => [],
        };
    }

    /**
     * Import from JSON.
     *
     * @param  string  $file
     * @return array<string, array<string, mixed>>
     */
    protected function importJson(string $file): array
    {
        $content = File::get($file);
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error('Invalid JSON file: ' . json_last_error_msg());

            return [];
        }

        // Handle different JSON structures
        if (isset($data['translations'])) {
            return $data['translations'];
        }

        return $data;
    }

    /**
     * Import from CSV.
     *
     * @param  string  $file
     * @return array<string, array<string, mixed>>
     */
    protected function importCsv(string $file): array
    {
        $locale = $this->option('locale');
        $group = $this->option('group');

        if (!$locale || !$group) {
            $this->error('CSV import requires --locale and --group options.');

            return [];
        }

        $handle = fopen($file, 'r');

        if ($handle === false) {
            $this->error("Failed to open file: {$file}");

            return [];
        }

        // Read header
        $header = fgetcsv($handle);
        if ($header === false) {
            $this->error('Invalid CSV file: no header row.');
            fclose($handle);

            return [];
        }

        // Find locale column
        $localeIndex = array_search($locale, $header);
        if ($localeIndex === false) {
            $this->error("Locale '{$locale}' not found in CSV header.");
            fclose($handle);

            return [];
        }

        // Read data
        $translations = [];
        while (($row = fgetcsv($handle)) !== false) {
            $key = $row[0] ?? null;
            $value = $row[$localeIndex] ?? null;

            if ($key && $value) {
                $this->setNestedValue($translations, $key, $value);
            }
        }

        fclose($handle);

        return [
            $locale => [
                $group => $translations,
            ],
        ];
    }

    /**
     * Import from PHP.
     *
     * @param  string  $file
     * @return array<string, array<string, mixed>>
     */
    protected function importPhp(string $file): array
    {
        $data = include $file;

        if (!is_array($data)) {
            $this->error('Invalid PHP file: must return an array.');

            return [];
        }

        // Handle different PHP structures
        if (isset($data['translations'])) {
            return $data['translations'];
        }

        return $data;
    }

    /**
     * Set nested value in array.
     *
     * @param  array<string, mixed>  &$array
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    protected function setNestedValue(array &$array, string $key, $value): void
    {
        $keys = explode('.', $key);
        $current = &$array;

        foreach ($keys as $k) {
            if (!isset($current[$k]) || !is_array($current[$k])) {
                $current[$k] = [];
            }
            $current = &$current[$k];
        }

        $current = $value;
    }

    /**
     * Display preview of translations.
     *
     * @param  array<string, array<string, mixed>>  $translations
     * @return void
     */
    protected function displayPreview(array $translations): void
    {
        $this->info('Preview:');
        $this->info('========');

        foreach ($translations as $locale => $groups) {
            $this->info("Locale: {$locale}");

            foreach ($groups as $group => $keys) {
                $count = $this->countKeys($keys);
                $this->line("  Group: {$group} ({$count} keys)");

                // Show first 5 keys
                $flatKeys = $this->flattenKeys($keys);
                $sample = array_slice($flatKeys, 0, 5);

                foreach ($sample as $key => $value) {
                    $this->line("    {$key}: {$value}");
                }

                if (count($flatKeys) > 5) {
                    $remaining = count($flatKeys) - 5;
                    $this->line("    ... and {$remaining} more");
                }
            }

            $this->newLine();
        }
    }

    /**
     * Apply translations.
     *
     * @param  array<string, array<string, mixed>>  $translations
     * @return void
     */
    protected function applyTranslations(array $translations): void
    {
        $merge = $this->option('merge');

        $progressBar = $this->output->createProgressBar(count($translations));
        $progressBar->start();

        foreach ($translations as $locale => $groups) {
            foreach ($groups as $group => $keys) {
                $this->saveTranslations($locale, $group, $keys, $merge);
            }
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
    }

    /**
     * Save translations to file.
     *
     * @param  string  $locale
     * @param  string  $group
     * @param  array<string, mixed>  $keys
     * @param  bool  $merge
     * @return void
     */
    protected function saveTranslations(string $locale, string $group, array $keys, bool $merge): void
    {
        // Determine path
        $path = $this->getTranslationPath($locale, $group);

        // Ensure directory exists
        $directory = dirname($path);
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        // Merge with existing if requested
        if ($merge && File::exists($path)) {
            $existing = include $path;
            if (is_array($existing)) {
                $keys = array_merge($existing, $keys);
            }
        }

        // Write file
        $export = "<?php\n\nreturn " . var_export($keys, true) . ";\n";
        File::put($path, $export);
    }

    /**
     * Get translation file path.
     *
     * @param  string  $locale
     * @param  string  $group
     * @return string
     */
    protected function getTranslationPath(string $locale, string $group): string
    {
        // Handle namespaced groups
        if (str_contains($group, '::')) {
            [$namespace, $groupName] = explode('::', $group, 2);

            return lang_path("vendor/{$namespace}/{$locale}/{$groupName}.php");
        }

        return lang_path("{$locale}/{$group}.php");
    }

    /**
     * Create backup of translations.
     *
     * @return void
     */
    protected function createBackup(): void
    {
        $this->info('Creating backup...');

        $backupPath = storage_path('app/translations/backups/' . now()->format('Y-m-d_His'));

        if (!File::exists($backupPath)) {
            File::makeDirectory($backupPath, 0755, true);
        }

        // Copy lang directory
        $langPath = lang_path();
        if (File::exists($langPath)) {
            File::copyDirectory($langPath, $backupPath);
            $this->info("Backup created: {$backupPath}");
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
}
