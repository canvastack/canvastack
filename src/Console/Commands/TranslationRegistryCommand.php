<?php

namespace Canvastack\Canvastack\Console\Commands;

use Canvastack\Canvastack\Support\Localization\TranslationRegistry;
use Illuminate\Console\Command;

/**
 * TranslationRegistryCommand.
 *
 * Manage translation registry (build, export, statistics).
 */
class TranslationRegistryCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'canvastack:translation:registry
                            {action : Action to perform (build, export, stats)}
                            {--path= : Export path for export action}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage translation registry';

    /**
     * Translation registry.
     *
     * @var TranslationRegistry
     */
    protected TranslationRegistry $registry;

    /**
     * Constructor.
     */
    public function __construct(TranslationRegistry $registry)
    {
        parent::__construct();
        $this->registry = $registry;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $action = $this->argument('action');

        return match ($action) {
            'build' => $this->buildRegistry(),
            'export' => $this->exportRegistry(),
            'stats' => $this->showStatistics(),
            default => $this->error("Unknown action: {$action}"),
        };
    }

    /**
     * Build translation registry.
     *
     * @return int
     */
    protected function buildRegistry(): int
    {
        $this->info('Building translation registry...');

        $this->registry->buildRegistry();

        $stats = $this->registry->getStatistics();

        $this->info('Registry built successfully!');
        $this->info("Total keys: {$stats['total_keys']}");

        return self::SUCCESS;
    }

    /**
     * Export translation registry.
     *
     * @return int
     */
    protected function exportRegistry(): int
    {
        $path = $this->option('path') ?? storage_path('app/translations/registry.json');

        $this->info("Exporting registry to: {$path}");

        if ($this->registry->export($path)) {
            $this->info('Registry exported successfully!');

            return self::SUCCESS;
        }

        $this->error('Failed to export registry.');

        return self::FAILURE;
    }

    /**
     * Show registry statistics.
     *
     * @return int
     */
    protected function showStatistics(): int
    {
        $stats = $this->registry->getStatistics();

        $this->info('Translation Registry Statistics');
        $this->info('================================');
        $this->info("Total keys: {$stats['total_keys']}");
        $this->info("Total usage: {$stats['total_usage']}");
        $this->info("Unused keys: {$stats['unused_keys']}");
        $this->newLine();

        $this->info('Coverage by Locale:');
        foreach ($stats['locales'] as $locale => $data) {
            $this->info("  {$locale}: {$data['translated']} translated, {$data['missing']} missing ({$data['coverage']}%)");
        }

        return self::SUCCESS;
    }
}
