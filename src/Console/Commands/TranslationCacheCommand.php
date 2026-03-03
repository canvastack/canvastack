<?php

namespace Canvastack\Canvastack\Console\Commands;

use Canvastack\Canvastack\Support\Localization\TranslationCache;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

/**
 * TranslationCacheCommand.
 *
 * Manage translation cache (warm, flush, refresh).
 */
class TranslationCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'canvastack:translation:cache
                            {action : Action to perform (warm, flush, refresh, stats)}
                            {--locale= : Specific locale}
                            {--group= : Specific group}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage translation cache';

    /**
     * Translation cache.
     *
     * @var TranslationCache
     */
    protected TranslationCache $cache;

    /**
     * Constructor.
     */
    public function __construct(TranslationCache $cache)
    {
        parent::__construct();
        $this->cache = $cache;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $action = $this->argument('action');

        return match ($action) {
            'warm' => $this->warmCache(),
            'flush' => $this->flushCache(),
            'refresh' => $this->refreshCache(),
            'stats' => $this->showStatistics(),
            default => $this->error("Unknown action: {$action}"),
        };
    }

    /**
     * Warm translation cache.
     *
     * @return int
     */
    protected function warmCache(): int
    {
        $locale = $this->option('locale');
        $group = $this->option('group');

        if ($locale) {
            $this->info("Warming cache for locale: {$locale}");
            $groups = $group ? [$group] : null;
            $count = $this->cache->warm($locale, $groups);
            $this->info("Cached {$count} translations for {$locale}");
        } else {
            $locales = array_keys(Config::get('canvastack.localization.available_locales', []));
            $totalCount = 0;

            foreach ($locales as $loc) {
                $this->info("Warming cache for locale: {$loc}");
                $count = $this->cache->warm($loc);
                $totalCount += $count;
                $this->info("Cached {$count} translations for {$loc}");
            }

            $this->info("Total cached: {$totalCount} translations");
        }

        return self::SUCCESS;
    }

    /**
     * Flush translation cache.
     *
     * @return int
     */
    protected function flushCache(): int
    {
        $locale = $this->option('locale');
        $group = $this->option('group');

        if ($locale && $group) {
            $this->info("Flushing cache for locale: {$locale}, group: {$group}");
            $this->cache->flushGroup($locale, $group);
        } elseif ($locale) {
            $this->info("Flushing cache for locale: {$locale}");
            $this->cache->flush($locale);
        } else {
            $this->info('Flushing all translation caches');
            $this->cache->flushAll();
        }

        $this->info('Cache flushed successfully!');

        return self::SUCCESS;
    }

    /**
     * Refresh translation cache.
     *
     * @return int
     */
    protected function refreshCache(): int
    {
        $locale = $this->option('locale');
        $group = $this->option('group');

        if ($locale) {
            $this->info("Refreshing cache for locale: {$locale}");
            $groups = $group ? [$group] : null;
            $count = $this->cache->refresh($locale, $groups);
            $this->info("Refreshed {$count} translations for {$locale}");
        } else {
            $locales = array_keys(Config::get('canvastack.localization.available_locales', []));
            $totalCount = 0;

            foreach ($locales as $loc) {
                $this->info("Refreshing cache for locale: {$loc}");
                $count = $this->cache->refresh($loc);
                $totalCount += $count;
                $this->info("Refreshed {$count} translations for {$loc}");
            }

            $this->info("Total refreshed: {$totalCount} translations");
        }

        return self::SUCCESS;
    }

    /**
     * Show cache statistics.
     *
     * @return int
     */
    protected function showStatistics(): int
    {
        $stats = $this->cache->getStatistics();

        $this->info('Translation Cache Statistics');
        $this->info('============================');
        $this->info('Enabled: ' . ($stats['enabled'] ? 'Yes' : 'No'));
        $this->info("Driver: {$stats['driver']}");

        if ($stats['enabled']) {
            $this->info("TTL: {$stats['ttl']} seconds");
            $this->newLine();

            $this->info('By Locale:');
            foreach ($stats['locales'] as $locale => $data) {
                $this->info("  {$locale}: {$data['cached_keys']} cached keys");
            }
        }

        return self::SUCCESS;
    }
}
