<?php

namespace Canvastack\Canvastack\Support\Localization;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

/**
 * MissingTranslationDetector.
 *
 * Detects and logs missing translations in real-time.
 * Provides reporting and notification capabilities.
 */
class MissingTranslationDetector
{
    /**
     * Missing translations cache key.
     *
     * @var string
     */
    protected string $cacheKey = 'canvastack.missing_translations';

    /**
     * Missing translations.
     *
     * @var array<string, array<string, mixed>>
     */
    protected array $missing = [];

    /**
     * Translation loader.
     *
     * @var TranslationLoader
     */
    protected TranslationLoader $loader;

    /**
     * Locale manager.
     *
     * @var LocaleManager
     */
    protected LocaleManager $localeManager;

    /**
     * Detection enabled.
     *
     * @var bool
     */
    protected bool $enabled;

    /**
     * Log missing translations.
     *
     * @var bool
     */
    protected bool $logMissing;

    /**
     * Constructor.
     */
    public function __construct(TranslationLoader $loader, LocaleManager $localeManager)
    {
        $this->loader = $loader;
        $this->localeManager = $localeManager;
        $this->enabled = Config::get('canvastack.localization.detect_missing', true);
        $this->logMissing = Config::get('canvastack.localization.log_missing', true);
        $this->loadMissing();
    }

    /**
     * Load missing translations from cache.
     *
     * @return void
     */
    protected function loadMissing(): void
    {
        if (Cache::has($this->cacheKey)) {
            $this->missing = Cache::get($this->cacheKey);
        }
    }

    /**
     * Detect missing translation.
     *
     * @param  string  $key
     * @param  string|null  $locale
     * @return void
     */
    public function detect(string $key, ?string $locale = null): void
    {
        if (!$this->enabled) {
            return;
        }

        $locale = $locale ?? $this->localeManager->getLocale();

        // Check if translation exists
        if ($this->loader->has($locale, $key)) {
            return;
        }

        // Record missing translation
        $this->recordMissing($key, $locale);
    }

    /**
     * Record missing translation.
     *
     * @param  string  $key
     * @param  string  $locale
     * @return void
     */
    protected function recordMissing(string $key, string $locale): void
    {
        $missingKey = "{$locale}.{$key}";

        if (!isset($this->missing[$missingKey])) {
            $this->missing[$missingKey] = [
                'key' => $key,
                'locale' => $locale,
                'count' => 0,
                'first_seen' => now()->toDateTimeString(),
                'last_seen' => null,
                'contexts' => [],
            ];
        }

        $this->missing[$missingKey]['count']++;
        $this->missing[$missingKey]['last_seen'] = now()->toDateTimeString();

        // Add context (URL, route, etc.)
        $context = $this->getContext();
        if (!in_array($context, $this->missing[$missingKey]['contexts'])) {
            $this->missing[$missingKey]['contexts'][] = $context;
        }

        // Log if enabled
        if ($this->logMissing) {
            Log::warning("Missing translation: {$key} for locale: {$locale}", [
                'key' => $key,
                'locale' => $locale,
                'context' => $context,
            ]);
        }

        // Save periodically
        if ($this->missing[$missingKey]['count'] % 5 === 0) {
            $this->saveMissing();
        }
    }

    /**
     * Get current context.
     *
     * @return string
     */
    protected function getContext(): string
    {
        if (app()->runningInConsole()) {
            return 'console';
        }

        $request = request();

        return $request->method() . ' ' . $request->path();
    }

    /**
     * Get all missing translations.
     *
     * @return array<string, array<string, mixed>>
     */
    public function all(): array
    {
        return $this->missing;
    }

    /**
     * Get missing translations for a locale.
     *
     * @param  string  $locale
     * @return array<string, array<string, mixed>>
     */
    public function getByLocale(string $locale): array
    {
        return array_filter($this->missing, function ($item) use ($locale) {
            return $item['locale'] === $locale;
        });
    }

    /**
     * Get missing translations for a key.
     *
     * @param  string  $key
     * @return array<string, array<string, mixed>>
     */
    public function getByKey(string $key): array
    {
        return array_filter($this->missing, function ($item) use ($key) {
            return $item['key'] === $key;
        });
    }

    /**
     * Get most frequently missing translations.
     *
     * @param  int  $limit
     * @return array<string, array<string, mixed>>
     */
    public function getMostFrequent(int $limit = 10): array
    {
        $sorted = $this->missing;

        usort($sorted, function ($a, $b) {
            return $b['count'] <=> $a['count'];
        });

        return array_slice($sorted, 0, $limit);
    }

    /**
     * Get recently missing translations.
     *
     * @param  int  $hours
     * @return array<string, array<string, mixed>>
     */
    public function getRecent(int $hours = 24): array
    {
        $threshold = now()->subHours($hours);

        return array_filter($this->missing, function ($item) use ($threshold) {
            return $item['last_seen'] && strtotime($item['last_seen']) >= $threshold->timestamp;
        });
    }

    /**
     * Get statistics.
     *
     * @return array<string, mixed>
     */
    public function getStatistics(): array
    {
        $stats = [
            'total_missing' => count($this->missing),
            'total_occurrences' => 0,
            'by_locale' => [],
            'by_group' => [],
        ];

        foreach ($this->missing as $item) {
            $stats['total_occurrences'] += $item['count'];

            // By locale
            if (!isset($stats['by_locale'][$item['locale']])) {
                $stats['by_locale'][$item['locale']] = 0;
            }
            $stats['by_locale'][$item['locale']]++;

            // By group
            $parts = explode('.', $item['key']);
            $group = $parts[0] ?? 'unknown';
            if (!isset($stats['by_group'][$group])) {
                $stats['by_group'][$group] = 0;
            }
            $stats['by_group'][$group]++;
        }

        return $stats;
    }

    /**
     * Generate report.
     *
     * @return array<string, mixed>
     */
    public function generateReport(): array
    {
        return [
            'generated_at' => now()->toDateTimeString(),
            'statistics' => $this->getStatistics(),
            'most_frequent' => $this->getMostFrequent(20),
            'recent' => $this->getRecent(24),
            'by_locale' => $this->groupByLocale(),
            'by_group' => $this->groupByGroup(),
        ];
    }

    /**
     * Group missing translations by locale.
     *
     * @return array<string, array<string, array<string, mixed>>>
     */
    protected function groupByLocale(): array
    {
        $grouped = [];

        foreach ($this->missing as $item) {
            $locale = $item['locale'];
            if (!isset($grouped[$locale])) {
                $grouped[$locale] = [];
            }
            $grouped[$locale][] = $item;
        }

        return $grouped;
    }

    /**
     * Group missing translations by group.
     *
     * @return array<string, array<string, array<string, mixed>>>
     */
    protected function groupByGroup(): array
    {
        $grouped = [];

        foreach ($this->missing as $item) {
            $parts = explode('.', $item['key']);
            $group = $parts[0] ?? 'unknown';
            if (!isset($grouped[$group])) {
                $grouped[$group] = [];
            }
            $grouped[$group][] = $item;
        }

        return $grouped;
    }

    /**
     * Export report to file.
     *
     * @param  string  $path
     * @param  string  $format
     * @return bool
     */
    public function exportReport(string $path, string $format = 'json'): bool
    {
        $report = $this->generateReport();

        if ($format === 'json') {
            return File::put($path, json_encode($report, JSON_PRETTY_PRINT)) !== false;
        }

        if ($format === 'csv') {
            return $this->exportCsv($path, $report);
        }

        return false;
    }

    /**
     * Export report to CSV.
     *
     * @param  string  $path
     * @param  array<string, mixed>  $report
     * @return bool
     */
    protected function exportCsv(string $path, array $report): bool
    {
        $handle = fopen($path, 'w');

        if ($handle === false) {
            return false;
        }

        // Header
        fputcsv($handle, ['Key', 'Locale', 'Count', 'First Seen', 'Last Seen', 'Contexts']);

        // Data
        foreach ($this->missing as $item) {
            fputcsv($handle, [
                $item['key'],
                $item['locale'],
                $item['count'],
                $item['first_seen'],
                $item['last_seen'],
                implode('; ', $item['contexts']),
            ]);
        }

        fclose($handle);

        return true;
    }

    /**
     * Clear missing translations.
     *
     * @param  string|null  $locale
     * @return void
     */
    public function clear(?string $locale = null): void
    {
        if ($locale) {
            $this->missing = array_filter($this->missing, function ($item) use ($locale) {
                return $item['locale'] !== $locale;
            });
        } else {
            $this->missing = [];
        }

        $this->saveMissing();
    }

    /**
     * Save missing translations to cache.
     *
     * @return void
     */
    protected function saveMissing(): void
    {
        Cache::put($this->cacheKey, $this->missing, 86400); // 24 hours
    }

    /**
     * Enable detection.
     *
     * @return void
     */
    public function enable(): void
    {
        $this->enabled = true;
    }

    /**
     * Disable detection.
     *
     * @return void
     */
    public function disable(): void
    {
        $this->enabled = false;
    }

    /**
     * Check if detection is enabled.
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }
}
