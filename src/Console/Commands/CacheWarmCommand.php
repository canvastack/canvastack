<?php

namespace Canvastack\Canvastack\Console\Commands;

use Canvastack\Canvastack\Support\Cache\ConfigCache;
use Illuminate\Console\Command;

/**
 * CacheWarmCommand.
 *
 * Artisan command to warm up CanvaStack cache
 */
class CacheWarmCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'canvastack:cache-warm';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Warm up CanvaStack cache with commonly used configurations';

    /**
     * Config cache instance.
     *
     * @var ConfigCache
     */
    protected ConfigCache $configCache;

    /**
     * Create a new command instance.
     *
     * @param ConfigCache $configCache
     */
    public function __construct(ConfigCache $configCache)
    {
        parent::__construct();
        $this->configCache = $configCache;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $this->info('Warming up CanvaStack cache...');

        try {
            // Warm up configuration cache
            $this->configCache->warmUp();

            $stats = $this->configCache->stats();
            $this->info('✓ Cache warmed successfully!');
            $this->info("  Cached {$stats['memory_cache_size']} configuration values");

            if ($this->output->isVerbose()) {
                $this->line('');
                $this->line('Cached keys:');
                foreach ($stats['memory_cache_keys'] as $key) {
                    $this->line("  - {$key}");
                }
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('✗ Failed to warm cache: ' . $e->getMessage());

            return Command::FAILURE;
        }
    }
}
