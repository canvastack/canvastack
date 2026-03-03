<?php

namespace Canvastack\Canvastack\Console\Commands;

use Canvastack\Canvastack\Support\Localization\TranslationVersion;
use Illuminate\Console\Command;

/**
 * TranslationVersionCommand.
 *
 * Manage translation versions (snapshot, restore, diff, list).
 */
class TranslationVersionCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'canvastack:translation:version
                            {action : Action to perform (snapshot, restore, diff, list, delete, export, import)}
                            {version? : Version identifier}
                            {--description= : Version description}
                            {--from= : From version (for diff)}
                            {--to= : To version (for diff)}
                            {--path= : File path (for export/import)}
                            {--locale= : Specific locale}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage translation versions';

    /**
     * Translation version manager.
     *
     * @var TranslationVersion
     */
    protected TranslationVersion $version;

    /**
     * Constructor.
     */
    public function __construct(TranslationVersion $version)
    {
        parent::__construct();
        $this->version = $version;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $action = $this->argument('action');

        return match ($action) {
            'snapshot' => $this->createSnapshot(),
            'restore' => $this->restoreVersion(),
            'diff' => $this->showDiff(),
            'list' => $this->listVersions(),
            'delete' => $this->deleteVersion(),
            'export' => $this->exportVersion(),
            'import' => $this->importVersion(),
            default => $this->error("Unknown action: {$action}"),
        };
    }

    /**
     * Create version snapshot.
     *
     * @return int
     */
    protected function createSnapshot(): int
    {
        $version = $this->argument('version');

        if (!$version) {
            $this->error('Version identifier is required');

            return self::FAILURE;
        }

        $description = $this->option('description');
        $locale = $this->option('locale');
        $locales = $locale ? [$locale] : null;

        $this->info("Creating snapshot: {$version}");

        if ($this->version->createSnapshot($version, $description, $locales)) {
            $this->info('Snapshot created successfully!');

            return self::SUCCESS;
        }

        $this->error('Failed to create snapshot.');

        return self::FAILURE;
    }

    /**
     * Restore version.
     *
     * @return int
     */
    protected function restoreVersion(): int
    {
        $version = $this->argument('version');

        if (!$version) {
            $this->error('Version identifier is required');

            return self::FAILURE;
        }

        if (!$this->confirm("Are you sure you want to restore version {$version}?")) {
            return self::SUCCESS;
        }

        $locale = $this->option('locale');
        $locales = $locale ? [$locale] : null;

        $this->info("Restoring version: {$version}");

        if ($this->version->restore($version, $locales)) {
            $this->info('Version restored successfully!');

            return self::SUCCESS;
        }

        $this->error('Failed to restore version.');

        return self::FAILURE;
    }

    /**
     * Show version diff.
     *
     * @return int
     */
    protected function showDiff(): int
    {
        $from = $this->option('from');
        $to = $this->option('to');

        if (!$from || !$to) {
            $this->error('Both --from and --to options are required');

            return self::FAILURE;
        }

        $this->info("Comparing versions: {$from} -> {$to}");

        $diff = $this->version->diff($from, $to);

        if (empty($diff)) {
            $this->info('No differences found.');

            return self::SUCCESS;
        }

        foreach ($diff['locales'] as $locale => $changes) {
            $this->info("Locale: {$locale}");
            $this->info('=================');

            if (!empty($changes['added'])) {
                $this->info('Added:');
                foreach ($changes['added'] as $key => $value) {
                    $this->line("  + {$key}");
                }
            }

            if (!empty($changes['removed'])) {
                $this->info('Removed:');
                foreach ($changes['removed'] as $key => $value) {
                    $this->line("  - {$key}");
                }
            }

            if (!empty($changes['modified'])) {
                $this->info('Modified:');
                foreach ($changes['modified'] as $key => $change) {
                    $this->line("  ~ {$key}");
                }
            }

            $this->newLine();
        }

        return self::SUCCESS;
    }

    /**
     * List all versions.
     *
     * @return int
     */
    protected function listVersions(): int
    {
        $versions = $this->version->getAllVersions();

        if (empty($versions)) {
            $this->info('No versions found.');

            return self::SUCCESS;
        }

        $this->info('Translation Versions:');
        $this->info('====================');

        foreach ($versions as $ver => $data) {
            $this->line("{$ver} - {$data['description']} (created: {$data['created_at']})");
        }

        $current = $this->version->getCurrentVersion();
        $this->newLine();
        $this->info("Current version: {$current}");

        return self::SUCCESS;
    }

    /**
     * Delete version.
     *
     * @return int
     */
    protected function deleteVersion(): int
    {
        $version = $this->argument('version');

        if (!$version) {
            $this->error('Version identifier is required');

            return self::FAILURE;
        }

        if (!$this->confirm("Are you sure you want to delete version {$version}?")) {
            return self::SUCCESS;
        }

        if ($this->version->deleteVersion($version)) {
            $this->info('Version deleted successfully!');

            return self::SUCCESS;
        }

        $this->error('Failed to delete version.');

        return self::FAILURE;
    }

    /**
     * Export version.
     *
     * @return int
     */
    protected function exportVersion(): int
    {
        $version = $this->argument('version');
        $path = $this->option('path');

        if (!$version) {
            $this->error('Version identifier is required');

            return self::FAILURE;
        }

        if (!$path) {
            $path = storage_path("app/translations/export-{$version}.json");
        }

        $this->info("Exporting version {$version} to: {$path}");

        if ($this->version->export($version, $path)) {
            $this->info('Version exported successfully!');

            return self::SUCCESS;
        }

        $this->error('Failed to export version.');

        return self::FAILURE;
    }

    /**
     * Import version.
     *
     * @return int
     */
    protected function importVersion(): int
    {
        $version = $this->argument('version');
        $path = $this->option('path');

        if (!$version || !$path) {
            $this->error('Both version and --path are required');

            return self::FAILURE;
        }

        $this->info("Importing version {$version} from: {$path}");

        if ($this->version->import($path, $version)) {
            $this->info('Version imported successfully!');

            return self::SUCCESS;
        }

        $this->error('Failed to import version.');

        return self::FAILURE;
    }
}
