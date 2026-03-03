<?php

namespace Canvastack\Canvastack\Console\Commands;

use Canvastack\Canvastack\Support\Cache\CacheManager;
use Illuminate\Console\Command;

/**
 * CacheClearCommand.
 *
 * Artisan command to clear CanvaStack cache
 */
class CacheClearCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'canvastack:cache-clear 
                            {component? : Specific component to clear (forms, tables, permissions, views, queries)}
                            {--all : Clear all CanvaStack cache}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear CanvaStack cache';

    /**
     * Cache manager instance.
     *
     * @var CacheManager
     */
    protected CacheManager $cache;

    /**
     * Create a new command instance.
     *
     * @param CacheManager $cache
     */
    public function __construct(CacheManager $cache)
    {
        parent::__construct();
        $this->cache = $cache;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $component = $this->argument('component');
        $all = $this->option('all');

        if ($all) {
            return $this->clearAll();
        }

        if ($component) {
            return $this->clearComponent($component);
        }

        // Interactive mode
        return $this->interactive();
    }

    /**
     * Clear all CanvaStack cache.
     *
     * @return int
     */
    protected function clearAll(): int
    {
        $this->info('Clearing all CanvaStack cache...');

        if ($this->cache->flushAll()) {
            $this->info('✓ All CanvaStack cache cleared successfully!');

            return Command::SUCCESS;
        }

        $this->error('✗ Failed to clear cache');

        return Command::FAILURE;
    }

    /**
     * Clear specific component cache.
     *
     * @param string $component
     * @return int
     */
    protected function clearComponent(string $component): int
    {
        $validComponents = ['forms', 'tables', 'permissions', 'views', 'queries', 'config'];

        if (!in_array($component, $validComponents)) {
            $this->error("Invalid component: {$component}");
            $this->info('Valid components: ' . implode(', ', $validComponents));

            return Command::FAILURE;
        }

        $this->info("Clearing {$component} cache...");

        if ($this->cache->flush($component)) {
            $this->info("✓ {$component} cache cleared successfully!");

            return Command::SUCCESS;
        }

        $this->error("✗ Failed to clear {$component} cache");

        return Command::FAILURE;
    }

    /**
     * Interactive mode.
     *
     * @return int
     */
    protected function interactive(): int
    {
        $choice = $this->choice(
            'What would you like to clear?',
            [
                'all' => 'All CanvaStack cache',
                'forms' => 'Forms cache',
                'tables' => 'Tables cache',
                'permissions' => 'Permissions cache',
                'views' => 'Views cache',
                'queries' => 'Queries cache',
                'config' => 'Config cache',
            ],
            'all'
        );

        if ($choice === 'all') {
            return $this->clearAll();
        }

        return $this->clearComponent($choice);
    }
}
