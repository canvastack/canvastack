<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Support\Performance;

use Canvastack\Canvastack\Support\Integration\ThemeLocalePerformance;
use Canvastack\Canvastack\Support\Localization\TranslationPerformanceOptimizer;
use Canvastack\Canvastack\Support\Theme\ThemePerformanceOptimizer;

/**
 * Performance Dashboard.
 *
 * Provides a unified dashboard for monitoring and optimizing
 * performance across all system components.
 */
class PerformanceDashboard
{
    /**
     * Performance monitor instance.
     */
    protected PerformanceMonitor $monitor;

    /**
     * Theme performance optimizer.
     */
    protected ?ThemePerformanceOptimizer $themeOptimizer = null;

    /**
     * Translation performance optimizer.
     */
    protected ?TranslationPerformanceOptimizer $translationOptimizer = null;

    /**
     * Theme locale performance optimizer.
     */
    protected ?ThemeLocalePerformance $themeLocalePerformance = null;

    /**
     * Constructor.
     */
    public function __construct(PerformanceMonitor $monitor)
    {
        $this->monitor = $monitor;
    }

    /**
     * Set theme performance optimizer.
     */
    public function setThemeOptimizer(ThemePerformanceOptimizer $optimizer): self
    {
        $this->themeOptimizer = $optimizer;

        return $this;
    }

    /**
     * Set translation performance optimizer.
     */
    public function setTranslationOptimizer(TranslationPerformanceOptimizer $optimizer): self
    {
        $this->translationOptimizer = $optimizer;

        return $this;
    }

    /**
     * Set theme locale performance optimizer.
     */
    public function setThemeLocalePerformance(ThemeLocalePerformance $performance): self
    {
        $this->themeLocalePerformance = $performance;

        return $this;
    }

    /**
     * Get comprehensive performance overview.
     */
    public function getOverview(): array
    {
        return [
            'system' => $this->monitor->getCurrentPerformance(),
            'themes' => $this->themeOptimizer ? $this->themeOptimizer->getCacheStats() : null,
            'translations' => $this->translationOptimizer ? $this->translationOptimizer->getCacheStats() : null,
            'integration' => $this->themeLocalePerformance ? $this->themeLocalePerformance->getCacheStats() : null,
            'metrics' => $this->monitor->getReport(),
            'recommendations' => $this->getAllRecommendations(),
        ];
    }

    /**
     * Get all performance recommendations.
     */
    public function getAllRecommendations(): array
    {
        $recommendations = [];

        // System recommendations
        $recommendations['system'] = $this->monitor->getRecommendations();

        // Theme recommendations
        if ($this->themeOptimizer) {
            $recommendations['themes'] = $this->themeOptimizer->getRecommendations();
        }

        // Translation recommendations
        if ($this->translationOptimizer) {
            $recommendations['translations'] = $this->translationOptimizer->getRecommendations();
        }

        // Integration recommendations
        if ($this->themeLocalePerformance) {
            $recommendations['integration'] = $this->themeLocalePerformance->getRecommendations();
        }

        return $recommendations;
    }

    /**
     * Run comprehensive benchmark.
     */
    public function runBenchmark(): array
    {
        $results = [
            'started_at' => time(),
            'benchmarks' => [],
        ];

        // Benchmark themes
        if ($this->themeOptimizer) {
            $this->monitor->startTimer('theme_benchmark');
            $results['benchmarks']['themes'] = $this->themeOptimizer->benchmarkAllThemes();
            $results['benchmarks']['themes']['metrics'] = $this->monitor->stopTimer('theme_benchmark');
        }

        // Benchmark translations
        if ($this->translationOptimizer) {
            $this->monitor->startTimer('translation_benchmark');
            $results['benchmarks']['translations'] = $this->translationOptimizer->benchmarkAllLocales();
            $results['benchmarks']['translations']['metrics'] = $this->monitor->stopTimer('translation_benchmark');
        }

        // Benchmark integration
        if ($this->themeLocalePerformance) {
            $this->monitor->startTimer('integration_benchmark');
            $results['benchmarks']['integration'] = $this->themeLocalePerformance->benchmark();
            $results['benchmarks']['integration']['metrics'] = $this->monitor->stopTimer('integration_benchmark');
        }

        $results['completed_at'] = time();
        $results['duration_seconds'] = $results['completed_at'] - $results['started_at'];

        return $results;
    }

    /**
     * Warm up all caches.
     */
    public function warmupAll(): array
    {
        $results = [
            'started_at' => time(),
            'warmup' => [],
        ];

        // Warmup themes
        if ($this->themeOptimizer) {
            $this->monitor->startTimer('theme_warmup');
            $results['warmup']['themes'] = $this->themeOptimizer->warmupCache();
            $results['warmup']['themes']['metrics'] = $this->monitor->stopTimer('theme_warmup');
        }

        // Warmup translations
        if ($this->translationOptimizer) {
            $this->monitor->startTimer('translation_warmup');
            $results['warmup']['translations'] = $this->translationOptimizer->warmupCache();
            $results['warmup']['translations']['metrics'] = $this->monitor->stopTimer('translation_warmup');
        }

        // Warmup integration
        if ($this->themeLocalePerformance) {
            $this->monitor->startTimer('integration_warmup');
            $results['warmup']['integration'] = $this->themeLocalePerformance->warmupCache();
            $results['warmup']['integration']['metrics'] = $this->monitor->stopTimer('integration_warmup');
        }

        $results['completed_at'] = time();
        $results['duration_seconds'] = $results['completed_at'] - $results['started_at'];

        return $results;
    }

    /**
     * Clear all caches.
     */
    public function clearAll(): array
    {
        $results = [
            'cleared' => [],
        ];

        // Clear theme cache
        if ($this->themeOptimizer) {
            $this->themeOptimizer->clearCache();
            $results['cleared'][] = 'themes';
        }

        // Clear translation cache
        if ($this->translationOptimizer) {
            $this->translationOptimizer->clearCache();
            $results['cleared'][] = 'translations';
        }

        // Clear integration cache
        if ($this->themeLocalePerformance) {
            $this->themeLocalePerformance->clearOldCache();
            $results['cleared'][] = 'integration';
        }

        // Clear monitor metrics
        $this->monitor->clearMetrics();
        $results['cleared'][] = 'metrics';

        return $results;
    }

    /**
     * Get cache size estimates.
     */
    public function getCacheSizes(): array
    {
        $sizes = [];

        // Theme cache size
        if ($this->themeOptimizer) {
            $sizes['themes'] = $this->estimateThemeCacheSize();
        }

        // Translation cache size
        if ($this->translationOptimizer) {
            $sizes['translations'] = $this->translationOptimizer->getMemoryUsageEstimate();
        }

        // Integration cache size
        if ($this->themeLocalePerformance) {
            $sizes['integration'] = $this->themeLocalePerformance->getCacheSizeEstimate();
        }

        // Calculate totals
        $totalBytes = 0;
        foreach ($sizes as $component => $size) {
            if (isset($size['total_size_bytes'])) {
                $totalBytes += $size['total_size_bytes'];
            }
        }

        $sizes['total'] = [
            'total_size_bytes' => $totalBytes,
            'total_size_kb' => round($totalBytes / 1024, 2),
            'total_size_mb' => round($totalBytes / 1024 / 1024, 2),
        ];

        return $sizes;
    }

    /**
     * Estimate theme cache size.
     */
    protected function estimateThemeCacheSize(): array
    {
        // This is a simplified estimation
        // In a real implementation, you would query the cache store
        return [
            'estimated' => true,
            'total_size_kb' => 0,
            'note' => 'Theme cache size estimation not yet implemented',
        ];
    }

    /**
     * Get performance score.
     */
    public function getPerformanceScore(): array
    {
        $score = 100;
        $deductions = [];

        // Check cache ratios
        if ($this->themeOptimizer) {
            $themeStats = $this->themeOptimizer->getCacheStats();
            if ($themeStats['css_cache_ratio'] < 50) {
                $score -= 10;
                $deductions[] = 'Low theme CSS cache ratio';
            }
            if ($themeStats['config_cache_ratio'] < 50) {
                $score -= 10;
                $deductions[] = 'Low theme config cache ratio';
            }
        }

        if ($this->translationOptimizer) {
            $translationStats = $this->translationOptimizer->getCacheStats();
            if ($translationStats['locale_cache_ratio'] < 50) {
                $score -= 10;
                $deductions[] = 'Low translation cache ratio';
            }
        }

        // Check memory usage
        $currentPerf = $this->monitor->getCurrentPerformance();
        $memoryLimitBytes = $this->parseMemoryLimit(ini_get('memory_limit'));
        $memoryUsagePercent = ($currentPerf['memory_usage_bytes'] / $memoryLimitBytes) * 100;

        if ($memoryUsagePercent > 80) {
            $score -= 20;
            $deductions[] = 'Very high memory usage';
        } elseif ($memoryUsagePercent > 60) {
            $score -= 10;
            $deductions[] = 'High memory usage';
        }

        // Check slow operations
        $metrics = $this->monitor->getAllMetrics();
        foreach ($metrics as $name => $metricList) {
            $stats = $this->monitor->getMetricStats($name);
            if ($stats['avg_duration_ms'] > 1000) {
                $score -= 5;
                $deductions[] = "Slow operation: {$name}";
            }
        }

        $score = max(0, $score);

        return [
            'score' => $score,
            'grade' => $this->getGrade($score),
            'deductions' => $deductions,
            'status' => $this->getStatus($score),
        ];
    }

    /**
     * Get grade from score.
     */
    protected function getGrade(int $score): string
    {
        if ($score >= 90) {
            return 'A';
        }
        if ($score >= 80) {
            return 'B';
        }
        if ($score >= 70) {
            return 'C';
        }
        if ($score >= 60) {
            return 'D';
        }

        return 'F';
    }

    /**
     * Get status from score.
     */
    protected function getStatus(int $score): string
    {
        if ($score >= 90) {
            return 'Excellent';
        }
        if ($score >= 80) {
            return 'Good';
        }
        if ($score >= 70) {
            return 'Fair';
        }
        if ($score >= 60) {
            return 'Poor';
        }

        return 'Critical';
    }

    /**
     * Parse memory limit string to bytes.
     */
    protected function parseMemoryLimit(string $limit): int
    {
        $limit = trim($limit);
        $last = strtolower($limit[strlen($limit) - 1]);
        $value = (int) $limit;

        switch ($last) {
            case 'g':
                $value *= 1024;
                // no break
            case 'm':
                $value *= 1024;
                // no break
            case 'k':
                $value *= 1024;
        }

        return $value;
    }

    /**
     * Export dashboard data.
     */
    public function export(): array
    {
        return [
            'overview' => $this->getOverview(),
            'performance_score' => $this->getPerformanceScore(),
            'cache_sizes' => $this->getCacheSizes(),
            'exported_at' => time(),
        ];
    }
}
