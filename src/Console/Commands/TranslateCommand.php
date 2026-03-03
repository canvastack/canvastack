<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\Finder;

/**
 * TranslateCommand.
 *
 * Extract translation keys from source files.
 */
class TranslateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'canvastack:translate
                            {--path= : Path to scan for translation keys (default: resources/views)}
                            {--output= : Output file path (default: storage/app/translations/keys.json)}
                            {--format=json : Output format (json, php, csv)}
                            {--include-vendor : Include vendor packages}
                            {--pattern=* : Additional patterns to search for}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Extract translation keys from source files';

    /**
     * Extracted translation keys.
     *
     * @var array<string, array<string, mixed>>
     */
    protected array $keys = [];

    /**
     * Default patterns to search for translation keys.
     *
     * @var array<string>
     */
    protected array $patterns = [
        // Blade directives
        '/@lang\([\'"]([^\'"]+)[\'"]\)/',
        '/@trans\([\'"]([^\'"]+)[\'"]\)/',
        '/@choice\([\'"]([^\'"]+)[\'"]\)/',

        // Helper functions
        '/__\([\'"]([^\'"]+)[\'"]\)/',
        '/trans\([\'"]([^\'"]+)[\'"]\)/',
        '/trans_choice\([\'"]([^\'"]+)[\'"](?:,\s*\d+)?\)/',  // Updated to handle count parameter

        // Facade calls
        '/Lang::get\([\'"]([^\'"]+)[\'"]\)/',
        '/Lang::choice\([\'"]([^\'"]+)[\'"](?:,\s*\d+)?\)/',  // Updated to handle count parameter
        '/Translation::get\([\'"]([^\'"]+)[\'"]\)/',

        // CanvaStack specific
        '/trans_ui\([\'"]([^\'"]+)[\'"]\)/',
        '/trans_component\([\'"]([^\'"]+)[\'"]\)/',
        '/trans_validation\([\'"]([^\'"]+)[\'"]\)/',
        '/trans_error\([\'"]([^\'"]+)[\'"]\)/',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Extracting translation keys...');
        $this->newLine();

        // Get scan path
        $path = $this->option('path') ?? resource_path('views');

        if (!File::exists($path)) {
            $this->error("Path does not exist: {$path}");

            return self::FAILURE;
        }

        // Add custom patterns
        $customPatterns = $this->option('pattern');
        if (!empty($customPatterns)) {
            // Ensure it's an array
            if (is_string($customPatterns)) {
                $customPatterns = [$customPatterns];
            }
            $this->patterns = array_merge($this->patterns, $customPatterns);
        }

        // Scan files
        $this->scanDirectory($path);

        // Include vendor if requested
        if ($this->option('include-vendor')) {
            $vendorPath = base_path('vendor');
            if (File::exists($vendorPath)) {
                $this->info('Scanning vendor packages...');
                $this->scanDirectory($vendorPath);
            }
        }

        // Display statistics
        $this->displayStatistics();

        // Export results
        $outputPath = $this->option('output') ?? storage_path('app/translations/keys.json');
        $format = $this->option('format');

        $this->exportKeys($outputPath, $format);

        $this->newLine();
        $this->info('Translation keys extracted successfully!');
        $this->info("Output: {$outputPath}");

        return self::SUCCESS;
    }

    /**
     * Scan directory for translation keys.
     *
     * @param  string  $path
     * @return void
     */
    protected function scanDirectory(string $path): void
    {
        $finder = new Finder();
        $finder->files()
            ->in($path)
            ->name(['*.php', '*.blade.php'])
            ->notPath('vendor')
            ->notPath('node_modules')
            ->notPath('storage');

        $progressBar = $this->output->createProgressBar($finder->count());
        $progressBar->start();

        foreach ($finder as $file) {
            $this->scanFile($file->getRealPath());
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
    }

    /**
     * Scan file for translation keys.
     *
     * @param  string  $filePath
     * @return void
     */
    protected function scanFile(string $filePath): void
    {
        $content = File::get($filePath);

        foreach ($this->patterns as $pattern) {
            if (preg_match_all($pattern, $content, $matches)) {
                foreach ($matches[1] as $key) {
                    $this->addKey($key, $filePath);
                }
            }
        }
    }

    /**
     * Add translation key.
     *
     * @param  string  $key
     * @param  string  $filePath
     * @return void
     */
    protected function addKey(string $key, string $filePath): void
    {
        if (!isset($this->keys[$key])) {
            $this->keys[$key] = [
                'key' => $key,
                'group' => $this->extractGroup($key),
                'files' => [],
                'count' => 0,
            ];
        }

        if (!in_array($filePath, $this->keys[$key]['files'])) {
            $this->keys[$key]['files'][] = $filePath;
        }

        $this->keys[$key]['count']++;
    }

    /**
     * Extract group from translation key.
     *
     * @param  string  $key
     * @return string
     */
    protected function extractGroup(string $key): string
    {
        // Handle namespaced keys (e.g., "canvastack::ui.button")
        if (str_contains($key, '::')) {
            [$namespace, $rest] = explode('::', $key, 2);
            $parts = explode('.', $rest);

            return $namespace . '::' . ($parts[0] ?? 'unknown');
        }

        // Handle regular keys (e.g., "ui.button")
        $parts = explode('.', $key);

        return $parts[0] ?? 'unknown';
    }

    /**
     * Display statistics.
     *
     * @return void
     */
    protected function displayStatistics(): void
    {
        $this->newLine();
        $this->info('Statistics:');
        $this->info('===========');
        $this->info('Total keys: ' . count($this->keys));
        $this->info('Total occurrences: ' . array_sum(array_column($this->keys, 'count')));
        $this->newLine();

        // Group by namespace
        $byGroup = [];
        foreach ($this->keys as $item) {
            $group = $item['group'];
            if (!isset($byGroup[$group])) {
                $byGroup[$group] = 0;
            }
            $byGroup[$group]++;
        }

        $this->info('By Group:');
        foreach ($byGroup as $group => $count) {
            $this->line("  {$group}: {$count}");
        }
    }

    /**
     * Export keys to file.
     *
     * @param  string  $path
     * @param  string  $format
     * @return void
     */
    protected function exportKeys(string $path, string $format): void
    {
        // Ensure directory exists
        $directory = dirname($path);
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        match ($format) {
            'json' => $this->exportJson($path),
            'php' => $this->exportPhp($path),
            'csv' => $this->exportCsv($path),
            default => $this->exportJson($path),
        };
    }

    /**
     * Export keys to JSON.
     *
     * @param  string  $path
     * @return void
     */
    protected function exportJson(string $path): void
    {
        $data = [
            'generated_at' => now()->toDateTimeString(),
            'total_keys' => count($this->keys),
            'keys' => array_values($this->keys),
        ];

        File::put($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * Export keys to PHP array.
     *
     * @param  string  $path
     * @return void
     */
    protected function exportPhp(string $path): void
    {
        $data = [
            'generated_at' => now()->toDateTimeString(),
            'total_keys' => count($this->keys),
            'keys' => array_values($this->keys),
        ];

        $export = "<?php\n\nreturn " . var_export($data, true) . ";\n";
        File::put($path, $export);
    }

    /**
     * Export keys to CSV.
     *
     * @param  string  $path
     * @return void
     */
    protected function exportCsv(string $path): void
    {
        $handle = fopen($path, 'w');

        if ($handle === false) {
            $this->error("Failed to open file: {$path}");

            return;
        }

        // Header
        fputcsv($handle, ['Key', 'Group', 'Count', 'Files']);

        // Data
        foreach ($this->keys as $item) {
            fputcsv($handle, [
                $item['key'],
                $item['group'],
                $item['count'],
                implode('; ', $item['files']),
            ]);
        }

        fclose($handle);
    }
}
