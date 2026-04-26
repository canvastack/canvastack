<?php

namespace Canvastack\Canvastack\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Warm Table Cache Command
 * 
 * Warms up table schema and configuration caches for improved performance.
 * This command pre-loads frequently accessed data into cache to reduce
 * database queries during runtime.
 * 
 * Usage:
 *   php artisan canvastack:warm-cache
 *   php artisan canvastack:warm-cache --tables=users,posts
 *   php artisan canvastack:warm-cache --force
 * 
 * Configuration:
 *   config/canvastack.cache.php:
 *   - warming.enabled: Enable/disable cache warming
 *   - warming.tables: List of tables to warm
 *   - warming.scheduled: Enable scheduled warming
 *   - warming.schedule: Cron expression for scheduling
 * 
 * @package App\Console\Commands
 */
class WarmTableCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'canvastack:warm-cache
                            {--tables= : Comma-separated list of tables to warm (overrides config)}
                            {--force : Force warming even if disabled in config}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Warm up table schema and configuration cache for improved performance';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Check if warming is enabled
        if (!config('canvastack.cache.warming.enabled', false) && !$this->option('force')) {
            $this->warn('Cache warming is disabled in configuration.');
            $this->info('Use --force to warm cache anyway, or enable in config/canvastack.cache.php');
            return 0;
        }

        $this->info('Starting cache warming...');
        $startTime = microtime(true);

        // Get tables to warm
        $tables = $this->getTablesToWarm();

        if (empty($tables)) {
            $this->warn('No tables configured for warming.');
            $this->info('Configure tables in config/canvastack.cache.php under warming.tables');
            return 0;
        }

        $this->info('Warming cache for ' . count($tables) . ' table(s)...');

        $warmed = 0;
        $failed = 0;

        foreach ($tables as $table) {
            try {
                $this->warmTableCache($table);
                $this->line("  ✓ {$table}");
                $warmed++;
            } catch (\Exception $e) {
                $this->error("  ✗ {$table}: " . $e->getMessage());
                $failed++;
            }
        }

        $duration = round((microtime(true) - $startTime) * 1000, 2);

        $this->newLine();
        $this->info("Cache warming completed in {$duration}ms");
        $this->info("Warmed: {$warmed} | Failed: {$failed}");

        return $failed > 0 ? 1 : 0;
    }

    /**
     * Get list of tables to warm
     *
     * @return array
     */
    protected function getTablesToWarm(): array
    {
        // Check for command option first
        if ($this->option('tables')) {
            return array_map('trim', explode(',', $this->option('tables')));
        }

        // Fall back to config
        return config('canvastack.cache.warming.tables', []);
    }

    /**
     * Warm cache for a specific table
     *
     * @param string $table Table name
     * @return void
     * @throws \Exception
     */
    protected function warmTableCache(string $table): void
    {
        $connection = config('database.default');

        // Warm schema cache
        $schema = canvastack_table_get_cached_schema($table, $connection);
        if (empty($schema)) {
            throw new \Exception('Failed to load schema');
        }

        // Warm validation cache (column listing)
        $columns = DB::connection($connection)->getSchemaBuilder()->getColumnListing($table);
        if (empty($columns)) {
            throw new \Exception('Failed to load columns');
        }

        // Cache column listing for validation
        $cacheKey = config('canvastack.cache.prefix', 'canvastack_') .
                    config('canvastack.cache.table_schema.key_prefix', 'table_schema_') .
                    $table . '_columns';
        \Cache::put($cacheKey, $columns, 3600);

        // Log warming operation
        if (config('canvastack.cache.development.log_operations', false)) {
            \Log::debug('[DEV] Cache: Table warmed', [
                'table' => $table,
                'connection' => $connection,
                'columns_count' => count($columns),
            ]);
        }
    }
}
