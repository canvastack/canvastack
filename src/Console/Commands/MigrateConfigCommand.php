<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * MigrateConfigCommand.
 *
 * Artisan command to migrate old CanvaStack Origin config files
 * to new CanvaStack config structure.
 */
class MigrateConfigCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'canvastack:migrate-config 
                            {--backup : Create backup of old config files}
                            {--force : Overwrite existing config files}
                            {--dry-run : Show what would be migrated without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate old CanvaStack Origin config files to new structure';

    /**
     * Old config files mapping.
     *
     * @var array<string, string>
     */
    protected array $oldConfigFiles = [
        'canvas.settings' => 'canvastack',
        'canvas.connections' => 'canvastack',
        'canvas.templates' => 'canvastack-ui',
        'canvas.registers' => 'canvastack',
    ];

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $this->info('CanvaStack Config Migration Tool');
        $this->info('================================');
        $this->newLine();

        // Check if old config files exist
        $oldFiles = $this->findOldConfigFiles();

        if (empty($oldFiles)) {
            $this->warn('No old CanvaStack Origin config files found.');
            $this->info('Looking for: canvas.settings.php, canvas.connections.php, canvas.templates.php, canvas.registers.php');
            
            return Command::SUCCESS;
        }

        $this->info('Found old config files:');
        foreach ($oldFiles as $file) {
            $this->line("  - {$file}");
        }
        $this->newLine();

        // Dry run mode
        if ($this->option('dry-run')) {
            return $this->dryRun($oldFiles);
        }

        // Confirm migration
        if (!$this->option('force') && !$this->confirm('Do you want to proceed with migration?', true)) {
            $this->info('Migration cancelled.');
            
            return Command::SUCCESS;
        }

        // Create backup if requested
        if ($this->option('backup')) {
            $this->createBackup($oldFiles);
        }

        // Migrate config files
        $this->migrateConfigs($oldFiles);

        $this->newLine();
        $this->info('✓ Config migration completed successfully!');
        $this->newLine();
        $this->info('Next steps:');
        $this->line('  1. Review the new config files in config/ directory');
        $this->line('  2. Update your .env file if needed');
        $this->line('  3. Clear config cache: php artisan config:clear');
        $this->line('  4. Test your application');

        return Command::SUCCESS;
    }

    /**
     * Find old config files.
     *
     * @return array<string>
     */
    protected function findOldConfigFiles(): array
    {
        $found = [];
        $configPath = config_path();

        foreach (array_keys($this->oldConfigFiles) as $oldFile) {
            $filePath = $configPath . '/' . $oldFile . '.php';
            if (File::exists($filePath)) {
                $found[] = $oldFile . '.php';
            }
        }

        return $found;
    }

    /**
     * Dry run mode - show what would be migrated.
     *
     * @param array<string> $oldFiles
     * @return int
     */
    protected function dryRun(array $oldFiles): int
    {
        $this->info('DRY RUN MODE - No changes will be made');
        $this->newLine();

        foreach ($oldFiles as $oldFile) {
            $oldName = str_replace('.php', '', $oldFile);
            $newName = $this->oldConfigFiles[$oldName] ?? 'canvastack';

            $this->line("Would migrate: {$oldFile}");
            $this->line("  → Target: config/{$newName}.php");
            $this->newLine();

            // Show sample migration
            $this->showSampleMigration($oldName);
        }

        return Command::SUCCESS;
    }

    /**
     * Create backup of old config files.
     *
     * @param array<string> $oldFiles
     * @return void
     */
    protected function createBackup(array $oldFiles): void
    {
        $this->info('Creating backup...');

        $backupDir = storage_path('canvastack/config-backup-' . date('Y-m-d-His'));
        File::makeDirectory($backupDir, 0755, true);

        foreach ($oldFiles as $oldFile) {
            $source = config_path($oldFile);
            $destination = $backupDir . '/' . $oldFile;

            File::copy($source, $destination);
            $this->line("  ✓ Backed up: {$oldFile}");
        }

        $this->info("Backup created at: {$backupDir}");
        $this->newLine();
    }

    /**
     * Migrate config files.
     *
     * @param array<string> $oldFiles
     * @return void
     */
    protected function migrateConfigs(array $oldFiles): void
    {
        $this->info('Migrating config files...');
        $this->newLine();

        foreach ($oldFiles as $oldFile) {
            $oldName = str_replace('.php', '', $oldFile);
            $this->migrateConfig($oldName);
        }
    }

    /**
     * Migrate a single config file.
     *
     * @param string $oldName
     * @return void
     */
    protected function migrateConfig(string $oldName): void
    {
        $newName = $this->oldConfigFiles[$oldName] ?? 'canvastack';

        $this->line("Migrating {$oldName}.php → {$newName}.php");

        // Load old config
        $oldConfig = config($oldName, []);

        if (empty($oldConfig)) {
            $this->warn("  ⚠ Config file {$oldName}.php is empty or invalid");
            
            return;
        }

        // Migrate based on old config type
        $newConfig = match ($oldName) {
            'canvas.settings' => $this->migrateSettings($oldConfig),
            'canvas.connections' => $this->migrateConnections($oldConfig),
            'canvas.templates' => $this->migrateTemplates($oldConfig),
            'canvas.registers' => $this->migrateRegisters($oldConfig),
            default => $oldConfig,
        };

        // Merge with existing config if it exists
        $existingConfig = config($newName, []);
        $mergedConfig = array_merge($existingConfig, $newConfig);

        // Write new config file
        $this->writeConfigFile($newName, $mergedConfig);

        $this->info("  ✓ Migrated successfully");
    }

    /**
     * Migrate canvas.settings.php to canvastack.php.
     *
     * @param array<string, mixed> $oldConfig
     * @return array<string, mixed>
     */
    protected function migrateSettings(array $oldConfig): array
    {
        return [
            'app' => [
                'name' => $oldConfig['app_name'] ?? config('app.name'),
                'url' => $oldConfig['app_url'] ?? config('app.url'),
                'timezone' => $oldConfig['timezone'] ?? config('app.timezone'),
                'locale' => $oldConfig['locale'] ?? config('app.locale'),
            ],
            'cache' => [
                'enabled' => $oldConfig['cache_enabled'] ?? true,
                'driver' => $oldConfig['cache_driver'] ?? 'redis',
                'ttl' => [
                    'forms' => $oldConfig['cache_ttl_forms'] ?? 3600,
                    'tables' => $oldConfig['cache_ttl_tables'] ?? 300,
                    'permissions' => $oldConfig['cache_ttl_permissions'] ?? 3600,
                    'views' => $oldConfig['cache_ttl_views'] ?? 3600,
                    'queries' => $oldConfig['cache_ttl_queries'] ?? 300,
                ],
            ],
            'performance' => [
                'chunk_size' => $oldConfig['chunk_size'] ?? 100,
                'eager_load' => $oldConfig['eager_load'] ?? true,
                'query_cache' => $oldConfig['query_cache'] ?? true,
            ],
            'logging' => [
                'enabled' => $oldConfig['logging_enabled'] ?? true,
                'channel' => $oldConfig['logging_channel'] ?? 'stack',
                'level' => $oldConfig['logging_level'] ?? 'debug',
            ],
        ];
    }

    /**
     * Migrate canvas.connections.php to canvastack.php.
     *
     * @param array<string, mixed> $oldConfig
     * @return array<string, mixed>
     */
    protected function migrateConnections(array $oldConfig): array
    {
        return [
            'database' => [
                'connections' => $oldConfig['connections'] ?? [],
                'default' => $oldConfig['default'] ?? config('database.default'),
            ],
        ];
    }

    /**
     * Migrate canvas.templates.php to canvastack-ui.php.
     *
     * @param array<string, mixed> $oldConfig
     * @return array<string, mixed>
     */
    protected function migrateTemplates(array $oldConfig): array
    {
        return [
            'theme' => [
                'default' => $oldConfig['default_theme'] ?? 'default',
                'colors' => [
                    'primary' => $oldConfig['primary_color'] ?? '#6366f1',
                    'secondary' => $oldConfig['secondary_color'] ?? '#8b5cf6',
                    'accent' => $oldConfig['accent_color'] ?? '#a855f7',
                ],
                'fonts' => [
                    'sans' => $oldConfig['font_sans'] ?? 'Inter',
                    'mono' => $oldConfig['font_mono'] ?? 'JetBrains Mono',
                ],
                'layout' => [
                    'container' => $oldConfig['container_width'] ?? '1280px',
                    'spacing' => $oldConfig['spacing'] ?? '1rem',
                ],
            ],
            'dark_mode' => [
                'enabled' => $oldConfig['dark_mode_enabled'] ?? true,
                'default' => $oldConfig['dark_mode_default'] ?? 'light',
                'storage' => $oldConfig['dark_mode_storage'] ?? 'localStorage',
            ],
        ];
    }

    /**
     * Migrate canvas.registers.php to canvastack.php.
     *
     * @param array<string, mixed> $oldConfig
     * @return array<string, mixed>
     */
    protected function migrateRegisters(array $oldConfig): array
    {
        return [
            'modules' => [
                'enabled' => $oldConfig['modules_enabled'] ?? [],
                'disabled' => $oldConfig['modules_disabled'] ?? [],
                'paths' => $oldConfig['module_paths'] ?? [],
            ],
        ];
    }

    /**
     * Write config file.
     *
     * @param string $name
     * @param array<string, mixed> $config
     * @return void
     */
    protected function writeConfigFile(string $name, array $config): void
    {
        $path = config_path("{$name}.php");

        // Check if file exists and force option is not set
        if (File::exists($path) && !$this->option('force')) {
            if (!$this->confirm("Config file {$name}.php already exists. Overwrite?", false)) {
                $this->warn("  ⚠ Skipped {$name}.php (already exists)");
                
                return;
            }
        }

        // Generate PHP config file content
        $content = "<?php\n\nreturn " . $this->varExport($config, 1) . ";\n";

        File::put($path, $content);
    }

    /**
     * Export variable as PHP code.
     *
     * @param mixed $var
     * @param int $indent
     * @return string
     */
    protected function varExport(mixed $var, int $indent = 0): string
    {
        if (is_array($var)) {
            $indexed = array_keys($var) === range(0, count($var) - 1);
            $r = [];
            foreach ($var as $key => $value) {
                $r[] = str_repeat('    ', $indent)
                    . ($indexed ? '' : $this->varExport($key) . ' => ')
                    . $this->varExport($value, $indent + 1);
            }
            
            return "[\n" . implode(",\n", $r) . "\n" . str_repeat('    ', $indent - 1) . ']';
        }

        return var_export($var, true);
    }

    /**
     * Show sample migration for a config file.
     *
     * @param string $oldName
     * @return void
     */
    protected function showSampleMigration(string $oldName): void
    {
        $samples = [
            'canvas.settings' => [
                'app_name' => 'My App',
                'cache_enabled' => true,
            ],
            'canvas.connections' => [
                'default' => 'mysql',
                'connections' => ['mysql' => []],
            ],
            'canvas.templates' => [
                'default_theme' => 'default',
                'primary_color' => '#6366f1',
            ],
            'canvas.registers' => [
                'modules_enabled' => ['users', 'posts'],
            ],
        ];

        if (isset($samples[$oldName])) {
            $this->line('  Sample old config:');
            foreach ($samples[$oldName] as $key => $value) {
                $this->line("    '{$key}' => " . var_export($value, true));
            }
        }
    }
}
