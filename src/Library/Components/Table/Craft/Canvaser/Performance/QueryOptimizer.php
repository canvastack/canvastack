<?php

namespace Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Performance;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * QueryOptimizer - Advanced query optimization for POST method DataTables
 * 
 * Provides comprehensive query optimization including:
 * - Query analysis and optimization
 * - Index recommendations
 * - Query caching strategies
 * - Performance monitoring
 * - Memory management
 * - Execution plan analysis
 */
class QueryOptimizer
{
    /**
     * Optimization configuration
     */
    private array $config;

    /**
     * Performance metrics
     */
    private array $metrics = [];

    /**
     * Query cache
     */
    private array $queryCache = [];

    /**
     * Index recommendations
     */
    private array $indexRecommendations = [];

    /**
     * Constructor
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'enable_query_cache' => true,
            'cache_duration' => 3600, // 1 hour
            'enable_index_analysis' => true,
            'enable_execution_plan' => true,
            'slow_query_threshold' => 1000, // 1 second in milliseconds
            'memory_limit_mb' => 512,
            'max_result_size' => 10000,
            'enable_query_rewrite' => true,
            'enable_performance_monitoring' => true,
            'optimization_level' => 'aggressive', // conservative, moderate, aggressive
            'enable_parallel_processing' => false
        ], $config);
    }

    /**
     * Optimize query for POST method DataTables
     */
    public function optimizeQuery(string $sql, array $bindings = [], array $options = []): array
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        try {
            // Generate query signature for caching
            $querySignature = $this->generateQuerySignature($sql, $bindings);
            
            // Check cache first
            if ($this->config['enable_query_cache']) {
                $cachedResult = $this->getCachedQuery($querySignature);
                if ($cachedResult) {
                    $this->recordMetrics('cache_hit', $startTime, $startMemory);
                    return $cachedResult;
                }
            }

            // Analyze query structure
            $analysis = $this->analyzeQuery($sql, $bindings);
            
            // Apply optimizations based on analysis
            $optimizedQuery = $this->applyOptimizations($sql, $bindings, $analysis, $options);
            
            // Execute optimized query
            $result = $this->executeOptimizedQuery($optimizedQuery, $options);
            
            // Cache result if enabled
            if ($this->config['enable_query_cache'] && $result['cacheable']) {
                $this->cacheQuery($querySignature, $result);
            }
            
            // Record performance metrics
            $this->recordMetrics('query_execution', $startTime, $startMemory, [
                'original_sql' => $sql,
                'optimized_sql' => $optimizedQuery['sql'],
                'result_count' => count($result['data']),
                'optimizations_applied' => $optimizedQuery['optimizations']
            ]);

            return $result;

        } catch (\Exception $e) {
            $this->recordMetrics('query_error', $startTime, $startMemory, [
                'error' => $e->getMessage(),
                'sql' => $sql
            ]);
            
            Log::error('Query optimization failed', [
                'error' => $e->getMessage(),
                'sql' => $sql,
                'bindings' => $bindings
            ]);
            
            throw $e;
        }
    }

    /**
     * Analyze query structure and performance characteristics
     */
    private function analyzeQuery(string $sql, array $bindings): array
    {
        $analysis = [
            'type' => $this->detectQueryType($sql),
            'tables' => $this->extractTables($sql),
            'joins' => $this->extractJoins($sql),
            'where_conditions' => $this->extractWhereConditions($sql),
            'order_by' => $this->extractOrderBy($sql),
            'group_by' => $this->extractGroupBy($sql),
            'having' => $this->extractHaving($sql),
            'subqueries' => $this->detectSubqueries($sql),
            'complexity_score' => 0,
            'estimated_rows' => 0,
            'index_usage' => [],
            'optimization_opportunities' => []
        ];

        // Calculate complexity score
        $analysis['complexity_score'] = $this->calculateComplexityScore($analysis);
        
        // Analyze index usage if enabled
        if ($this->config['enable_index_analysis']) {
            $analysis['index_usage'] = $this->analyzeIndexUsage($sql, $bindings);
        }
        
        // Get execution plan if enabled
        if ($this->config['enable_execution_plan']) {
            $analysis['execution_plan'] = $this->getExecutionPlan($sql, $bindings);
        }
        
        // Identify optimization opportunities
        $analysis['optimization_opportunities'] = $this->identifyOptimizationOpportunities($analysis);

        return $analysis;
    }

    /**
     * Apply optimizations based on query analysis
     */
    private function applyOptimizations(string $sql, array $bindings, array $analysis, array $options): array
    {
        $optimizedSql = $sql;
        $optimizedBindings = $bindings;
        $appliedOptimizations = [];

        // Apply optimizations based on configuration level
        switch ($this->config['optimization_level']) {
            case 'aggressive':
                $optimizations = $this->getAggressiveOptimizations($analysis, $options);
                break;
            case 'moderate':
                $optimizations = $this->getModerateOptimizations($analysis, $options);
                break;
            case 'conservative':
            default:
                $optimizations = $this->getConservativeOptimizations($analysis, $options);
                break;
        }

        foreach ($optimizations as $optimization) {
            $result = $this->applyOptimization($optimizedSql, $optimizedBindings, $optimization);
            if ($result['applied']) {
                $optimizedSql = $result['sql'];
                $optimizedBindings = $result['bindings'];
                $appliedOptimizations[] = $optimization['type'];
            }
        }

        return [
            'sql' => $optimizedSql,
            'bindings' => $optimizedBindings,
            'optimizations' => $appliedOptimizations
        ];
    }

    /**
     * Get conservative optimizations
     */
    private function getConservativeOptimizations(array $analysis, array $options): array
    {
        $optimizations = [];

        // Add LIMIT if not present and result size is large
        if (!stripos($analysis['type'], 'LIMIT') && ($options['length'] ?? 0) > 0) {
            $optimizations[] = [
                'type' => 'add_limit',
                'value' => $options['length'],
                'offset' => $options['start'] ?? 0
            ];
        }

        // Optimize ORDER BY for pagination
        if (!empty($analysis['order_by']) && ($options['start'] ?? 0) > 0) {
            $optimizations[] = [
                'type' => 'optimize_order_by',
                'columns' => $analysis['order_by']
            ];
        }

        // Add index hints for simple queries
        if ($analysis['complexity_score'] < 5 && !empty($analysis['optimization_opportunities'])) {
            foreach ($analysis['optimization_opportunities'] as $opportunity) {
                if ($opportunity['type'] === 'missing_index' && $opportunity['confidence'] > 0.8) {
                    $optimizations[] = [
                        'type' => 'add_index_hint',
                        'table' => $opportunity['table'],
                        'index' => $opportunity['suggested_index']
                    ];
                }
            }
        }

        return $optimizations;
    }

    /**
     * Get moderate optimizations
     */
    private function getModerateOptimizations(array $analysis, array $options): array
    {
        $optimizations = $this->getConservativeOptimizations($analysis, $options);

        // Query rewriting for better performance
        if ($this->config['enable_query_rewrite']) {
            // Convert EXISTS to JOIN where appropriate
            if (stripos($analysis['type'], 'EXISTS')) {
                $optimizations[] = [
                    'type' => 'exists_to_join',
                    'subqueries' => $analysis['subqueries']
                ];
            }

            // Optimize IN clauses with large value lists
            if (stripos($analysis['type'], 'IN') && count($analysis['where_conditions']) > 10) {
                $optimizations[] = [
                    'type' => 'optimize_in_clause',
                    'conditions' => $analysis['where_conditions']
                ];
            }
        }

        // Add covering indexes recommendations
        foreach ($analysis['optimization_opportunities'] as $opportunity) {
            if ($opportunity['type'] === 'covering_index' && $opportunity['confidence'] > 0.7) {
                $optimizations[] = [
                    'type' => 'suggest_covering_index',
                    'table' => $opportunity['table'],
                    'columns' => $opportunity['columns']
                ];
            }
        }

        return $optimizations;
    }

    /**
     * Get aggressive optimizations
     */
    private function getAggressiveOptimizations(array $analysis, array $options): array
    {
        $optimizations = $this->getModerateOptimizations($analysis, $options);

        // Parallel processing for complex queries
        if ($this->config['enable_parallel_processing'] && $analysis['complexity_score'] > 8) {
            $optimizations[] = [
                'type' => 'parallel_processing',
                'strategy' => $this->determineParallelStrategy($analysis)
            ];
        }

        // Query decomposition for very complex queries
        if ($analysis['complexity_score'] > 10) {
            $optimizations[] = [
                'type' => 'query_decomposition',
                'strategy' => 'split_complex_joins'
            ];
        }

        // Materialized view suggestions
        if (count($analysis['joins']) > 3 && $analysis['complexity_score'] > 7) {
            $optimizations[] = [
                'type' => 'suggest_materialized_view',
                'tables' => $analysis['tables'],
                'joins' => $analysis['joins']
            ];
        }

        return $optimizations;
    }

    /**
     * Apply individual optimization
     */
    private function applyOptimization(string $sql, array $bindings, array $optimization): array
    {
        switch ($optimization['type']) {
            case 'add_limit':
                return $this->addLimitOptimization($sql, $bindings, $optimization);
                
            case 'optimize_order_by':
                return $this->optimizeOrderBy($sql, $bindings, $optimization);
                
            case 'add_index_hint':
                return $this->addIndexHint($sql, $bindings, $optimization);
                
            case 'exists_to_join':
                return $this->convertExistsToJoin($sql, $bindings, $optimization);
                
            case 'optimize_in_clause':
                return $this->optimizeInClause($sql, $bindings, $optimization);
                
            default:
                return ['applied' => false, 'sql' => $sql, 'bindings' => $bindings];
        }
    }

    /**
     * Add LIMIT optimization
     */
    private function addLimitOptimization(string $sql, array $bindings, array $optimization): array
    {
        // Check if LIMIT already exists
        if (stripos($sql, 'LIMIT') !== false) {
            return ['applied' => false, 'sql' => $sql, 'bindings' => $bindings];
        }

        $limit = $optimization['value'];
        $offset = $optimization['offset'] ?? 0;

        if ($offset > 0) {
            $sql .= " LIMIT {$offset}, {$limit}";
        } else {
            $sql .= " LIMIT {$limit}";
        }

        return ['applied' => true, 'sql' => $sql, 'bindings' => $bindings];
    }

    /**
     * Optimize ORDER BY clause
     */
    private function optimizeOrderBy(string $sql, array $bindings, array $optimization): array
    {
        // For large offsets, consider using a different approach
        $offset = $optimization['offset'] ?? 0;
        
        if ($offset > 10000) {
            // Use cursor-based pagination hint
            $sql = "/* USE_CURSOR_PAGINATION */ " . $sql;
            return ['applied' => true, 'sql' => $sql, 'bindings' => $bindings];
        }

        return ['applied' => false, 'sql' => $sql, 'bindings' => $bindings];
    }

    /**
     * Add index hint
     */
    private function addIndexHint(string $sql, array $bindings, array $optimization): array
    {
        $table = $optimization['table'];
        $index = $optimization['index'];
        
        // Add USE INDEX hint
        $pattern = "/FROM\s+`?{$table}`?/i";
        $replacement = "FROM `{$table}` USE INDEX (`{$index}`)";
        
        $newSql = preg_replace($pattern, $replacement, $sql, 1);
        
        if ($newSql !== $sql) {
            return ['applied' => true, 'sql' => $newSql, 'bindings' => $bindings];
        }

        return ['applied' => false, 'sql' => $sql, 'bindings' => $bindings];
    }

    /**
     * Execute optimized query
     */
    private function executeOptimizedQuery(array $optimizedQuery, array $options): array
    {
        $startTime = microtime(true);
        
        try {
            // Check memory usage before execution
            $this->checkMemoryUsage();
            
            // Execute query
            $results = DB::select($optimizedQuery['sql'], $optimizedQuery['bindings']);
            
            // Convert to array
            $data = array_map(function($item) {
                return (array) $item;
            }, $results);
            
            $executionTime = (microtime(true) - $startTime) * 1000;
            
            // Check if query was slow
            $isSlowQuery = $executionTime > $this->config['slow_query_threshold'];
            
            if ($isSlowQuery) {
                $this->logSlowQuery($optimizedQuery['sql'], $executionTime, count($data));
            }

            return [
                'data' => $data,
                'execution_time' => $executionTime,
                'row_count' => count($data),
                'optimizations_applied' => $optimizedQuery['optimizations'],
                'cacheable' => !$isSlowQuery && count($data) < $this->config['max_result_size'],
                'memory_used' => memory_get_usage() - memory_get_usage(),
                'slow_query' => $isSlowQuery
            ];

        } catch (\Exception $e) {
            Log::error('Optimized query execution failed', [
                'sql' => $optimizedQuery['sql'],
                'bindings' => $optimizedQuery['bindings'],
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Generate query signature for caching
     */
    private function generateQuerySignature(string $sql, array $bindings): string
    {
        $normalizedSql = $this->normalizeSql($sql);
        $bindingsHash = md5(serialize($bindings));
        return 'query_' . md5($normalizedSql . $bindingsHash);
    }

    /**
     * Normalize SQL for consistent caching
     */
    private function normalizeSql(string $sql): string
    {
        // Remove extra whitespace
        $sql = preg_replace('/\s+/', ' ', trim($sql));
        
        // Convert to lowercase for consistency
        $sql = strtolower($sql);
        
        // Remove comments
        $sql = preg_replace('/\/\*.*?\*\//', '', $sql);
        $sql = preg_replace('/--.*$/', '', $sql);
        
        return $sql;
    }

    /**
     * Cache query result
     */
    private function cacheQuery(string $signature, array $result): void
    {
        $cacheData = [
            'data' => $result['data'],
            'metadata' => [
                'execution_time' => $result['execution_time'],
                'row_count' => $result['row_count'],
                'cached_at' => now()->toISOString()
            ]
        ];
        
        Cache::put($signature, $cacheData, $this->config['cache_duration']);
    }

    /**
     * Get cached query result
     */
    private function getCachedQuery(string $signature): ?array
    {
        $cached = Cache::get($signature);
        
        if ($cached) {
            return [
                'data' => $cached['data'],
                'execution_time' => 0, // Cache hit
                'row_count' => $cached['metadata']['row_count'],
                'optimizations_applied' => ['cache_hit'],
                'cacheable' => true,
                'memory_used' => 0,
                'slow_query' => false,
                'from_cache' => true
            ];
        }
        
        return null;
    }

    /**
     * Record performance metrics
     */
    private function recordMetrics(string $type, float $startTime, int $startMemory, array $additional = []): void
    {
        if (!$this->config['enable_performance_monitoring']) {
            return;
        }

        $metrics = [
            'type' => $type,
            'execution_time' => (microtime(true) - $startTime) * 1000,
            'memory_used' => memory_get_usage() - $startMemory,
            'peak_memory' => memory_get_peak_usage(),
            'timestamp' => now()->toISOString()
        ];

        $this->metrics[] = array_merge($metrics, $additional);
        
        // Keep only last 1000 metrics
        if (count($this->metrics) > 1000) {
            array_shift($this->metrics);
        }
    }

    /**
     * Check memory usage
     */
    private function checkMemoryUsage(): void
    {
        $memoryUsageMB = memory_get_usage() / 1024 / 1024;
        
        if ($memoryUsageMB > $this->config['memory_limit_mb']) {
            throw new \Exception("Memory usage ({$memoryUsageMB}MB) exceeds limit ({$this->config['memory_limit_mb']}MB)");
        }
    }

    /**
     * Log slow query
     */
    private function logSlowQuery(string $sql, float $executionTime, int $rowCount): void
    {
        Log::warning('Slow query detected', [
            'sql' => $sql,
            'execution_time' => $executionTime,
            'row_count' => $rowCount,
            'threshold' => $this->config['slow_query_threshold']
        ]);
    }

    /**
     * Utility methods for query analysis
     */
    private function detectQueryType(string $sql): string
    {
        $sql = strtoupper(trim($sql));
        
        if (strpos($sql, 'SELECT') === 0) return 'SELECT';
        if (strpos($sql, 'INSERT') === 0) return 'INSERT';
        if (strpos($sql, 'UPDATE') === 0) return 'UPDATE';
        if (strpos($sql, 'DELETE') === 0) return 'DELETE';
        
        return 'UNKNOWN';
    }

    private function extractTables(string $sql): array
    {
        // Simple table extraction - can be enhanced
        preg_match_all('/FROM\s+`?(\w+)`?/i', $sql, $matches);
        preg_match_all('/JOIN\s+`?(\w+)`?/i', $sql, $joinMatches);
        
        return array_unique(array_merge($matches[1] ?? [], $joinMatches[1] ?? []));
    }

    private function extractJoins(string $sql): array
    {
        preg_match_all('/(LEFT|RIGHT|INNER|OUTER)?\s*JOIN\s+`?(\w+)`?\s+ON\s+(.+?)(?=\s+(?:LEFT|RIGHT|INNER|OUTER)?\s*JOIN|\s+WHERE|\s+GROUP|\s+ORDER|\s+LIMIT|$)/i', $sql, $matches, PREG_SET_ORDER);
        
        $joins = [];
        foreach ($matches as $match) {
            $joins[] = [
                'type' => trim($match[1] ?: 'INNER'),
                'table' => $match[2],
                'condition' => trim($match[3])
            ];
        }
        
        return $joins;
    }

    private function extractWhereConditions(string $sql): array
    {
        // Simple WHERE extraction - can be enhanced
        if (preg_match('/WHERE\s+(.+?)(?=\s+GROUP|\s+ORDER|\s+LIMIT|$)/i', $sql, $matches)) {
            return [trim($matches[1])];
        }
        
        return [];
    }

    private function extractOrderBy(string $sql): array
    {
        if (preg_match('/ORDER\s+BY\s+(.+?)(?=\s+LIMIT|$)/i', $sql, $matches)) {
            return array_map('trim', explode(',', $matches[1]));
        }
        
        return [];
    }

    private function extractGroupBy(string $sql): array
    {
        if (preg_match('/GROUP\s+BY\s+(.+?)(?=\s+HAVING|\s+ORDER|\s+LIMIT|$)/i', $sql, $matches)) {
            return array_map('trim', explode(',', $matches[1]));
        }
        
        return [];
    }

    private function extractHaving(string $sql): array
    {
        if (preg_match('/HAVING\s+(.+?)(?=\s+ORDER|\s+LIMIT|$)/i', $sql, $matches)) {
            return [trim($matches[1])];
        }
        
        return [];
    }

    private function detectSubqueries(string $sql): array
    {
        preg_match_all('/\(\s*SELECT\s+.+?\)/i', $sql, $matches);
        return $matches[0] ?? [];
    }

    private function calculateComplexityScore(array $analysis): int
    {
        $score = 0;
        
        $score += count($analysis['tables']);
        $score += count($analysis['joins']) * 2;
        $score += count($analysis['where_conditions']);
        $score += count($analysis['subqueries']) * 3;
        $score += count($analysis['group_by']);
        $score += count($analysis['having']) * 2;
        
        return $score;
    }

    private function analyzeIndexUsage(string $sql, array $bindings): array
    {
        // This would require EXPLAIN query analysis
        // Simplified implementation
        return [];
    }

    private function getExecutionPlan(string $sql, array $bindings): array
    {
        try {
            $explainSql = "EXPLAIN " . $sql;
            $plan = DB::select($explainSql, $bindings);
            return array_map(function($row) {
                return (array) $row;
            }, $plan);
        } catch (\Exception $e) {
            return [];
        }
    }

    private function identifyOptimizationOpportunities(array $analysis): array
    {
        $opportunities = [];
        
        // Missing index opportunities
        if (count($analysis['where_conditions']) > 0 && empty($analysis['index_usage'])) {
            $opportunities[] = [
                'type' => 'missing_index',
                'confidence' => 0.8,
                'table' => $analysis['tables'][0] ?? 'unknown',
                'suggested_index' => 'composite_index'
            ];
        }
        
        // Covering index opportunities
        if (count($analysis['joins']) > 2) {
            $opportunities[] = [
                'type' => 'covering_index',
                'confidence' => 0.7,
                'table' => $analysis['tables'][0] ?? 'unknown',
                'columns' => array_merge($analysis['order_by'], $analysis['group_by'])
            ];
        }
        
        return $opportunities;
    }

    private function determineParallelStrategy(array $analysis): string
    {
        if (count($analysis['joins']) > 3) {
            return 'parallel_joins';
        }
        
        if (count($analysis['subqueries']) > 1) {
            return 'parallel_subqueries';
        }
        
        return 'parallel_scan';
    }

    /**
     * Get performance metrics
     */
    public function getPerformanceMetrics(): array
    {
        return $this->metrics;
    }

    /**
     * Get index recommendations
     */
    public function getIndexRecommendations(): array
    {
        return $this->indexRecommendations;
    }

    /**
     * Clear cache
     */
    public function clearCache(): void
    {
        $this->queryCache = [];
        Cache::flush(); // This clears all cache - be careful in production
    }
}