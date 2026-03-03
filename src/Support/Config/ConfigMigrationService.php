<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Support\Config;

use Illuminate\Support\Facades\File;

/**
 * Configuration Migration Service.
 *
 * Migrates configuration from old canvas.* format to new canvastack.* format.
 */
class ConfigMigrationService
{
    /**
     * Mapping of old config keys to new config keys.
     */
    protected array $keyMapping = [
        // canvas.settings.php -> canvastack.php
        'canvas.app_name' => 'canvastack.app.name',
        'canvas.app_desc' => 'canvastack.app.description',
        'canvas.base_url' => 'canvastack.app.base_url',
        'canvas.lang' => 'canvastack.app.lang',
        'canvas.maintenance' => 'canvastack.app.maintenance',

        // canvas.connections.php -> canvastack.php
        'canvas.connections' => 'canvastack.database.sources',

        // canvas.templates.php -> canvastack-ui.php
        'canvas.template' => 'canvastack-ui.assets.template',
        'canvas.base_template' => 'canvastack-ui.assets.base_template',
        'canvas.base_resources' => 'canvastack-ui.assets.base_resources',

        // canvas.registers.php -> canvastack.php
        'canvas.plugins' => 'canvastack.modules.plugins',
    ];

    /**
     * Migrate configuration from old format to new format.
     */
    public function migrate(): array
    {
        $results = [
            'success' => true,
            'migrated' => [],
            'errors' => [],
            'warnings' => [],
        ];

        // Check if old config files exist
        $oldConfigPath = config_path();
        $oldFiles = [
            'canvas.settings.php',
            'canvas.connections.php',
            'canvas.templates.php',
            'canvas.registers.php',
        ];

        $foundFiles = [];

        foreach ($oldFiles as $file) {
            if (File::exists($oldConfigPath . '/' . $file)) {
                $foundFiles[] = $file;
            }
        }

        if (empty($foundFiles)) {
            $results['warnings'][] = 'No old configuration files found';

            return $results;
        }

        // Migrate each file
        foreach ($foundFiles as $file) {
            try {
                $migrated = $this->migrateFile($file);
                $results['migrated'][$file] = $migrated;
            } catch (\Exception $e) {
                $results['success'] = false;
                $results['errors'][$file] = $e->getMessage();
            }
        }

        return $results;
    }

    /**
     * Migrate a single configuration file.
     */
    protected function migrateFile(string $filename): array
    {
        $oldConfigPath = config_path($filename);
        $oldConfig = include $oldConfigPath;

        $migrated = [];

        switch ($filename) {
            case 'canvas.settings.php':
                $migrated = $this->migrateSettings($oldConfig);
                break;

            case 'canvas.connections.php':
                $migrated = $this->migrateConnections($oldConfig);
                break;

            case 'canvas.templates.php':
                $migrated = $this->migrateTemplates($oldConfig);
                break;

            case 'canvas.registers.php':
                $migrated = $this->migrateRegisters($oldConfig);
                break;
        }

        return $migrated;
    }

    /**
     * Migrate canvas.settings.php.
     */
    protected function migrateSettings(array $oldConfig): array
    {
        $migrated = [];

        // App settings
        if (isset($oldConfig['app_name'])) {
            $migrated['canvastack.app.name'] = $oldConfig['app_name'];
        }

        if (isset($oldConfig['app_desc'])) {
            $migrated['canvastack.app.description'] = $oldConfig['app_desc'];
        }

        if (isset($oldConfig['base_url'])) {
            $migrated['canvastack.app.base_url'] = $oldConfig['base_url'];
        }

        if (isset($oldConfig['lang'])) {
            $migrated['canvastack.app.lang'] = $oldConfig['lang'];
        }

        if (isset($oldConfig['maintenance'])) {
            $migrated['canvastack.app.maintenance'] = $oldConfig['maintenance'];
        }

        // Platform settings
        if (isset($oldConfig['platform'])) {
            $migrated['canvastack.platform'] = $oldConfig['platform'];
        }

        // User settings
        if (isset($oldConfig['user'])) {
            $migrated['canvastack.user'] = $oldConfig['user'];
        }

        // Activity logging
        if (isset($oldConfig['log_activity'])) {
            $migrated['canvastack.log_activity'] = $oldConfig['log_activity'];
        }

        // Email settings
        if (isset($oldConfig['email'])) {
            $migrated['canvastack.email'] = $oldConfig['email'];
        }

        // Meta tags
        if (isset($oldConfig['meta'])) {
            $migrated['canvastack.meta'] = $oldConfig['meta'];
        }

        // Copyright
        if (isset($oldConfig['copyright'])) {
            $migrated['canvastack.copyright'] = $oldConfig['copyright'];
        }

        return $migrated;
    }

    /**
     * Migrate canvas.connections.php.
     */
    protected function migrateConnections(array $oldConfig): array
    {
        $migrated = [];

        if (isset($oldConfig['sources'])) {
            $migrated['canvastack.database.sources'] = $oldConfig['sources'];
        }

        return $migrated;
    }

    /**
     * Migrate canvas.templates.php.
     */
    protected function migrateTemplates(array $oldConfig): array
    {
        $migrated = [];

        if (isset($oldConfig['template'])) {
            $migrated['canvastack-ui.assets.template'] = $oldConfig['template'];
        }

        if (isset($oldConfig['base_template'])) {
            $migrated['canvastack-ui.assets.base_template'] = $oldConfig['base_template'];
        }

        if (isset($oldConfig['base_resources'])) {
            $migrated['canvastack-ui.assets.base_resources'] = $oldConfig['base_resources'];
        }

        if (isset($oldConfig['templates'])) {
            $migrated['canvastack-ui.assets.templates'] = $oldConfig['templates'];
        }

        return $migrated;
    }

    /**
     * Migrate canvas.registers.php.
     */
    protected function migrateRegisters(array $oldConfig): array
    {
        $migrated = [];

        if (isset($oldConfig['plugins'])) {
            $migrated['canvastack.modules.plugins'] = $oldConfig['plugins'];
        }

        return $migrated;
    }

    /**
     * Generate migration report.
     */
    public function generateReport(): array
    {
        $report = [
            'old_files' => [],
            'new_files' => [],
            'mapping' => [],
        ];

        // Check old files
        $oldConfigPath = config_path();
        $oldFiles = [
            'canvas.settings.php',
            'canvas.connections.php',
            'canvas.templates.php',
            'canvas.registers.php',
        ];

        foreach ($oldFiles as $file) {
            $path = $oldConfigPath . '/' . $file;
            $report['old_files'][$file] = [
                'exists' => File::exists($path),
                'path' => $path,
                'size' => File::exists($path) ? File::size($path) : 0,
            ];
        }

        // Check new files
        $newFiles = [
            'canvastack.php',
            'canvastack-ui.php',
            'canvastack-rbac.php',
        ];

        foreach ($newFiles as $file) {
            $path = $oldConfigPath . '/' . $file;
            $report['new_files'][$file] = [
                'exists' => File::exists($path),
                'path' => $path,
                'size' => File::exists($path) ? File::size($path) : 0,
            ];
        }

        // Add key mapping
        $report['mapping'] = $this->keyMapping;

        return $report;
    }

    /**
     * Backup old configuration files.
     */
    public function backupOldConfig(): array
    {
        $backupPath = storage_path('app/config-migration-backup');

        if (!File::exists($backupPath)) {
            File::makeDirectory($backupPath, 0755, true);
        }

        $backed = [];
        $errors = [];

        $oldFiles = [
            'canvas.settings.php',
            'canvas.connections.php',
            'canvas.templates.php',
            'canvas.registers.php',
        ];

        foreach ($oldFiles as $file) {
            $sourcePath = config_path($file);

            if (!File::exists($sourcePath)) {
                continue;
            }

            try {
                $destPath = $backupPath . '/' . $file . '.backup';
                File::copy($sourcePath, $destPath);
                $backed[] = $file;
            } catch (\Exception $e) {
                $errors[$file] = $e->getMessage();
            }
        }

        return [
            'success' => empty($errors),
            'backed_up' => $backed,
            'errors' => $errors,
            'backup_path' => $backupPath,
        ];
    }

    /**
     * Validate migration.
     */
    public function validateMigration(): array
    {
        $validation = [
            'valid' => true,
            'issues' => [],
            'warnings' => [],
        ];

        // Check if new config files exist
        $newFiles = [
            'canvastack.php',
            'canvastack-ui.php',
            'canvastack-rbac.php',
        ];

        foreach ($newFiles as $file) {
            if (!File::exists(config_path($file))) {
                $validation['valid'] = false;
                $validation['issues'][] = "Missing new config file: {$file}";
            }
        }

        // Check if old config files still exist
        $oldFiles = [
            'canvas.settings.php',
            'canvas.connections.php',
            'canvas.templates.php',
            'canvas.registers.php',
        ];

        foreach ($oldFiles as $file) {
            if (File::exists(config_path($file))) {
                $validation['warnings'][] = "Old config file still exists: {$file}";
            }
        }

        return $validation;
    }

    /**
     * Get migration instructions.
     */
    public function getInstructions(): array
    {
        return [
            'steps' => [
                [
                    'step' => 1,
                    'title' => 'Backup Old Configuration',
                    'description' => 'Create a backup of your old canvas.* configuration files',
                    'command' => 'php artisan canvastack:config:backup-old',
                ],
                [
                    'step' => 2,
                    'title' => 'Run Migration',
                    'description' => 'Migrate configuration from old format to new format',
                    'command' => 'php artisan canvastack:config:migrate',
                ],
                [
                    'step' => 3,
                    'title' => 'Validate Migration',
                    'description' => 'Validate that migration was successful',
                    'command' => 'php artisan canvastack:config:validate',
                ],
                [
                    'step' => 4,
                    'title' => 'Test Application',
                    'description' => 'Test your application to ensure everything works correctly',
                ],
                [
                    'step' => 5,
                    'title' => 'Remove Old Files (Optional)',
                    'description' => 'Once confirmed working, you can remove old canvas.* files',
                ],
            ],
            'notes' => [
                'The migration process is non-destructive - old files are not deleted',
                'A backup is automatically created before migration',
                'You can rollback by restoring from backup if needed',
                'Some manual adjustments may be required after migration',
            ],
        ];
    }
}
