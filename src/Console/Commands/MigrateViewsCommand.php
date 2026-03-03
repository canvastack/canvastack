<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * MigrateViewsCommand.
 *
 * Artisan command to migrate old CanvaStack Origin views
 * to new CanvaStack view structure and syntax.
 */
class MigrateViewsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'canvastack:migrate-views 
                            {path? : Specific path to migrate (relative to resources/views)}
                            {--backup : Create backup of old view files}
                            {--force : Overwrite existing view files}
                            {--dry-run : Show what would be migrated without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate old CanvaStack Origin views to new structure';

    /**
     * View replacements mapping.
     *
     * @var array<string, string>
     */
    protected array $replacements = [
        // Layout changes
        '@extends(\'layouts.admin\')' => '@extends(\'canvastack::layouts.admin\')',
        '@extends(\'layouts.public\')' => '@extends(\'canvastack::layouts.public\')',
        '@extends(\'layouts.auth\')' => '@extends(\'canvastack::layouts.auth\')',
        '@extends("layouts.admin")' => '@extends("canvastack::layouts.admin")',
        '@extends("layouts.public")' => '@extends("canvastack::layouts.public")',
        '@extends("layouts.auth")' => '@extends("canvastack::layouts.auth")',
        
        // Component changes
        '$this->form->' => '$form->',
        '$this->table->' => '$table->',
        '$this->chart->' => '$chart->',
        
        // Old Bootstrap classes to Tailwind
        'class="btn btn-primary"' => 'class="px-4 py-2 rounded-xl text-white" style="background: @themeColor(\'primary\')"',
        'class="btn btn-secondary"' => 'class="px-4 py-2 bg-gray-100 dark:bg-gray-800 rounded-xl"',
        'class="card"' => 'class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800"',
        'class="card-body"' => 'class="p-6"',
        'class="table"' => 'class="w-full"',
        
        // Asset paths
        'asset(\'vendor/canvastack/css/app.css\')' => '@vite([\'resources/css/canvastack.css\'])',
        'asset(\'vendor/canvastack/js/app.js\')' => '@vite([\'resources/js/canvastack.js\'])',
    ];

    /**
     * Statistics for migration.
     *
     * @var array<string, int>
     */
    protected array $stats = [
        'files_found' => 0,
        'files_migrated' => 0,
        'files_skipped' => 0,
        'replacements_made' => 0,
    ];

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $this->info('CanvaStack Views Migration Tool');
        $this->info('==============================');
        $this->newLine();

        // Get path to migrate
        $path = $this->argument('path') ?? '';
        $viewsPath = resource_path('views/' . $path);

        if (!File::exists($viewsPath)) {
            $this->error("Path not found: {$viewsPath}");
            
            return Command::FAILURE;
        }

        // Find view files
        $viewFiles = $this->findViewFiles($viewsPath);

        if (empty($viewFiles)) {
            $this->warn('No view files found to migrate.');
            
            return Command::SUCCESS;
        }

        $this->stats['files_found'] = count($viewFiles);
        $this->info("Found {$this->stats['files_found']} view files to migrate.");
        $this->newLine();

        // Dry run mode
        if ($this->option('dry-run')) {
            return $this->dryRun($viewFiles);
        }

        // Confirm migration
        if (!$this->option('force') && !$this->confirm('Do you want to proceed with migration?', true)) {
            $this->info('Migration cancelled.');
            
            return Command::SUCCESS;
        }

        // Create backup if requested
        if ($this->option('backup')) {
            $this->createBackup($viewFiles);
        }

        // Migrate view files
        $this->migrateViews($viewFiles);

        // Show statistics
        $this->showStatistics();

        return Command::SUCCESS;
    }

    /**
     * Find all view files in path.
     *
     * @param string $path
     * @return array<string>
     */
    protected function findViewFiles(string $path): array
    {
        if (File::isFile($path)) {
            return [$path];
        }

        return File::allFiles($path);
    }

    /**
     * Dry run mode - show what would be migrated.
     *
     * @param array<string> $viewFiles
     * @return int
     */
    protected function dryRun(array $viewFiles): int
    {
        $this->info('DRY RUN MODE - No changes will be made');
        $this->newLine();

        $progressBar = $this->output->createProgressBar(count($viewFiles));
        $progressBar->start();

        foreach ($viewFiles as $file) {
            $content = File::get($file);
            $changes = $this->detectChanges($content);

            if (!empty($changes)) {
                $this->newLine();
                $this->line("File: {$file}");
                $this->line('Changes:');
                foreach ($changes as $change) {
                    $this->line("  - {$change}");
                }
                $this->newLine();
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        return Command::SUCCESS;
    }

    /**
     * Detect changes that would be made to content.
     *
     * @param string $content
     * @return array<string>
     */
    protected function detectChanges(string $content): array
    {
        $changes = [];

        foreach ($this->replacements as $old => $new) {
            if (str_contains($content, $old)) {
                $changes[] = "Replace: {$old} → {$new}";
            }
        }

        return $changes;
    }

    /**
     * Create backup of view files.
     *
     * @param array<string> $viewFiles
     * @return void
     */
    protected function createBackup(array $viewFiles): void
    {
        $this->info('Creating backup...');

        $backupDir = storage_path('canvastack/views-backup-' . date('Y-m-d-His'));
        File::makeDirectory($backupDir, 0755, true);

        $progressBar = $this->output->createProgressBar(count($viewFiles));
        $progressBar->start();

        foreach ($viewFiles as $file) {
            $relativePath = str_replace(resource_path('views/'), '', $file);
            $destination = $backupDir . '/' . $relativePath;

            File::makeDirectory(dirname($destination), 0755, true, true);
            File::copy($file, $destination);

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
        $this->info("Backup created at: {$backupDir}");
        $this->newLine();
    }

    /**
     * Migrate view files.
     *
     * @param array<string> $viewFiles
     * @return void
     */
    protected function migrateViews(array $viewFiles): void
    {
        $this->info('Migrating view files...');
        $this->newLine();

        $progressBar = $this->output->createProgressBar(count($viewFiles));
        $progressBar->start();

        foreach ($viewFiles as $file) {
            $this->migrateView($file);
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);
    }

    /**
     * Migrate a single view file.
     *
     * @param string $file
     * @return void
     */
    protected function migrateView(string $file): void
    {
        $content = File::get($file);
        $originalContent = $content;

        // Apply replacements
        $replacementCount = 0;
        foreach ($this->replacements as $old => $new) {
            $newContent = str_replace($old, $new, $content);
            if ($newContent !== $content) {
                $replacementCount++;
                $content = $newContent;
            }
        }

        // Additional migrations
        $content = $this->migrateMetaTags($content);
        $content = $this->migrateTranslations($content);
        $content = $this->migrateThemeColors($content);

        // Only write if content changed
        if ($content !== $originalContent) {
            File::put($file, $content);
            $this->stats['files_migrated']++;
            $this->stats['replacements_made'] += $replacementCount;
        } else {
            $this->stats['files_skipped']++;
        }
    }

    /**
     * Migrate meta tags.
     *
     * @param string $content
     * @return string
     */
    protected function migrateMetaTags(string $content): string
    {
        // Add meta tags section if @extends is found but no @push('head')
        if (str_contains($content, '@extends') && !str_contains($content, '@push(\'head\')')) {
            $content = preg_replace(
                '/(@extends\([^\)]+\))/',
                "$1\n\n@push('head')\n    {!! \$meta->tags() !!}\n@endpush",
                $content,
                1
            );
        }

        return $content;
    }

    /**
     * Migrate hardcoded text to translations.
     *
     * @param string $content
     * @return string
     */
    protected function migrateTranslations(string $content): string
    {
        // Common hardcoded texts
        $translations = [
            '"Save"' => '{{ __("ui.buttons.save") }}',
            '"Cancel"' => '{{ __("ui.buttons.cancel") }}',
            '"Delete"' => '{{ __("ui.buttons.delete") }}',
            '"Edit"' => '{{ __("ui.buttons.edit") }}',
            '"Create"' => '{{ __("ui.buttons.create") }}',
            '"Dashboard"' => '{{ __("ui.dashboard") }}',
            '"Users"' => '{{ __("ui.users") }}',
            '"Settings"' => '{{ __("ui.settings") }}',
        ];

        foreach ($translations as $old => $new) {
            $content = str_replace($old, $new, $content);
        }

        return $content;
    }

    /**
     * Migrate hardcoded colors to theme colors.
     *
     * @param string $content
     * @return string
     */
    protected function migrateThemeColors(string $content): string
    {
        // Replace common color codes with theme colors
        $colorMappings = [
            '#6366f1' => '@themeColor(\'primary\')',
            '#8b5cf6' => '@themeColor(\'secondary\')',
            '#a855f7' => '@themeColor(\'accent\')',
        ];

        foreach ($colorMappings as $color => $themeColor) {
            $content = str_replace(
                "color: {$color}",
                "color: {$themeColor}",
                $content
            );
            $content = str_replace(
                "background: {$color}",
                "background: {$themeColor}",
                $content
            );
            $content = str_replace(
                "background-color: {$color}",
                "background-color: {$themeColor}",
                $content
            );
        }

        return $content;
    }

    /**
     * Show migration statistics.
     *
     * @return void
     */
    protected function showStatistics(): void
    {
        $this->info('Migration Statistics:');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Files Found', $this->stats['files_found']],
                ['Files Migrated', $this->stats['files_migrated']],
                ['Files Skipped', $this->stats['files_skipped']],
                ['Replacements Made', $this->stats['replacements_made']],
            ]
        );

        $this->newLine();
        $this->info('✓ Views migration completed successfully!');
        $this->newLine();
        $this->info('Next steps:');
        $this->line('  1. Review the migrated view files');
        $this->line('  2. Test your application');
        $this->line('  3. Clear view cache: php artisan view:clear');
        $this->line('  4. Update any custom views manually if needed');
    }
}
