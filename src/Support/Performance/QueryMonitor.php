<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Support\Performance;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * QueryMonitor - Monitors and logs database queries.
 *
 * Features:
 * - Query execution time tracking
 * - Slow query detection
 * - N+1 query detection
 * - Query count monitoring
 * - Duplicate query detection
 */
class QueryMonitor
{
    protected bool $enabled = false;

    protected array $queries = [];

    protected float $slowQueryThreshold = 1000; // milliseconds

    protected int $queryCountThreshold = 50;

    protected array $stats = [
        'total_queries' => 0,
        'slow_queries' => 0,
        'duplicate_queries' => 0,
        'total_time' => 0,
    ];

    /**
     * Start monitoring queries.
     *
     * @return void
     */
    public function start(): void
    {
        if ($this->enabled) {
            return;
        }

        $this->enabled = true;
        $this->queries = [];
        $this->resetStats();

        DB::enableQueryLog();

        // Listen to query events
        DB::listen(function ($query) {
            $this->logQuery($query);
        });
    }

    /**
     * Stop monitoring queries.
     *
     * @return void
     */
    public function stop(): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->enabled = false;
        DB::disableQueryLog();
    }

    /**
     * Log a query.
     *
     * @param mixed $query
     * @return void
     */
    protected function logQuery($query): void
    {
        $sql = $query->sql;
        $bindings = $query->bindings;
        $time = $query->time;

        $this->queries[] = [
            'sql' => $sql,
            'bindings' => $bindings,
            'time' => $time,
            'timestamp' => microtime(true),
        ];

        $this->stats['total_queries']++;
        $this->stats['total_time'] += $time;

        // Check for slow queries
        if ($time > $this->slowQueryThreshold) {
            $this->stats['slow_queries']++;
            $this->logSlowQuery($sql, $bindings, $time);
        }

        // Check for duplicate queries
        if ($this->isDuplicate($sql, $bindings)) {
            $this->stats['duplicate_queries']++;
        }

        // Check for excessive queries
        if ($this->stats['total_queries'] > $this->queryCountThreshold) {
            $this->logExcessiveQueries();
        }
    }

    /**
     * Check if query is duplicate.
     *
     * @param string $sql
     * @param array<mixed> $bindings
     * @return bool
     */
    protected function isDuplicate(string $sql, array $bindings): bool
    {
        $fingerprint = md5($sql . serialize($bindings));
        $count = 0;

        foreach ($this->queries as $query) {
            $queryFingerprint = md5($query['sql'] . serialize($query['bindings']));
            if ($queryFingerprint === $fingerprint) {
                $count++;
            }
        }

        return $count > 1;
    }

    /**
     * Log slow query.
     *
     * @param string $sql
     * @param array<mixed> $bindings
     * @param float $time
     * @return void
     */
    protected function logSlowQuery(string $sql, array $bindings, float $time): void
    {
        Log::warning('Slow query detected', [
            'sql' => $sql,
            'bindings' => $bindings,
            'time' => $time . 'ms',
            'threshold' => $this->slowQueryThreshold . 'ms',
        ]);
    }

    /**
     * Log excessive queries.
     *
     * @return void
     */
    protected function logExcessiveQueries(): void
    {
        if ($this->stats['total_queries'] === $this->queryCountThreshold + 1) {
            Log::warning('Excessive queries detected', [
                'count' => $this->stats['total_queries'],
                'threshold' => $this->queryCountThreshold,
            ]);
        }
    }

    /**
     * Detect N+1 query problems.
     *
     * @return array<array<string, mixed>>
     */
    public function detectN1Problems(): array
    {
        $issues = [];
        $patterns = [];

        foreach ($this->queries as $query) {
            $pattern = preg_replace('/\d+/', '?', $query['sql']);
            if (!isset($patterns[$pattern])) {
                $patterns[$pattern] = 0;
            }
            $patterns[$pattern]++;
        }

        foreach ($patterns as $pattern => $count) {
            if ($count > 10) {
                $issues[] = [
                    'type' => 'n+1',
                    'pattern' => $pattern,
                    'count' => $count,
                    'severity' => 'high',
                    'message' => "Query executed {$count} times. Possible N+1 problem.",
                ];
            }
        }

        return $issues;
    }

    /**
     * Get all queries.
     *
     * @return array<array<string, mixed>>
     */
    public function getQueries(): array
    {
        return $this->queries;
    }

    /**
     * Get query statistics.
     *
     * @return array<string, mixed>
     */
    public function getStats(): array
    {
        return $this->stats;
    }

    /**
     * Get slow queries.
     *
     * @return array<array<string, mixed>>
     */
    public function getSlowQueries(): array
    {
        return array_filter($this->queries, function ($query) {
            return $query['time'] > $this->slowQueryThreshold;
        });
    }

    /**
     * Get duplicate queries.
     *
     * @return array<array<string, mixed>>
     */
    public function getDuplicateQueries(): array
    {
        $duplicates = [];
        $seen = [];

        foreach ($this->queries as $query) {
            $fingerprint = md5($query['sql'] . serialize($query['bindings']));

            if (isset($seen[$fingerprint])) {
                if (!isset($duplicates[$fingerprint])) {
                    $duplicates[$fingerprint] = [
                        'sql' => $query['sql'],
                        'bindings' => $query['bindings'],
                        'count' => 1,
                        'queries' => [$seen[$fingerprint]],
                    ];
                }
                $duplicates[$fingerprint]['count']++;
                $duplicates[$fingerprint]['queries'][] = $query;
            } else {
                $seen[$fingerprint] = $query;
            }
        }

        return array_values($duplicates);
    }

    /**
     * Generate performance report.
     *
     * @return array<string, mixed>
     */
    public function generateReport(): array
    {
        $avgTime = $this->stats['total_queries'] > 0
            ? $this->stats['total_time'] / $this->stats['total_queries']
            : 0;

        return [
            'summary' => [
                'total_queries' => $this->stats['total_queries'],
                'slow_queries' => $this->stats['slow_queries'],
                'duplicate_queries' => $this->stats['duplicate_queries'],
                'total_time' => round($this->stats['total_time'], 2) . 'ms',
                'average_time' => round($avgTime, 2) . 'ms',
            ],
            'slow_queries' => $this->getSlowQueries(),
            'duplicate_queries' => $this->getDuplicateQueries(),
            'n1_problems' => $this->detectN1Problems(),
            'recommendations' => $this->generateRecommendations(),
        ];
    }

    /**
     * Generate optimization recommendations.
     *
     * @return array<string>
     */
    protected function generateRecommendations(): array
    {
        $recommendations = [];

        if ($this->stats['slow_queries'] > 0) {
            $recommendations[] = "Found {$this->stats['slow_queries']} slow queries. Consider adding indexes or optimizing queries.";
        }

        if ($this->stats['duplicate_queries'] > 5) {
            $recommendations[] = "Found {$this->stats['duplicate_queries']} duplicate queries. Consider caching query results.";
        }

        if ($this->stats['total_queries'] > $this->queryCountThreshold) {
            $recommendations[] = "Executed {$this->stats['total_queries']} queries. Consider using eager loading to reduce query count.";
        }

        $n1Problems = $this->detectN1Problems();
        if (!empty($n1Problems)) {
            $recommendations[] = "Detected " . count($n1Problems) . " potential N+1 query problems. Use eager loading with ->with().";
        }

        if (empty($recommendations)) {
            $recommendations[] = "Query performance looks good!";
        }

        return $recommendations;
    }

    /**
     * Set slow query threshold.
     *
     * @param float $milliseconds
     * @return self
     */
    public function setSlowQueryThreshold(float $milliseconds): self
    {
        $this->slowQueryThreshold = $milliseconds;

        return $this;
    }

    /**
     * Set query count threshold.
     *
     * @param int $count
     * @return self
     */
    public function setQueryCountThreshold(int $count): self
    {
        $this->queryCountThreshold = $count;

        return $this;
    }

    /**
     * Reset statistics.
     *
     * @return void
     */
    protected function resetStats(): void
    {
        $this->stats = [
            'total_queries' => 0,
            'slow_queries' => 0,
            'duplicate_queries' => 0,
            'total_time' => 0,
        ];
    }

    /**
     * Clear all data.
     *
     * @return void
     */
    public function clear(): void
    {
        $this->queries = [];
        $this->resetStats();
    }

    /**
     * Check if monitoring is enabled.
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }
}
