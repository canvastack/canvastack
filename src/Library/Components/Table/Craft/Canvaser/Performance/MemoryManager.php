<?php

namespace Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Performance;

use Illuminate\Support\Facades\Log;

/**
 * MemoryManager - Advanced memory management for POST method DataTables
 * 
 * Provides comprehensive memory management including:
 * - Memory usage monitoring
 * - Automatic garbage collection
 * - Memory leak detection
 * - Large dataset handling
 * - Memory optimization strategies
 * - Resource cleanup
 */
class MemoryManager
{
    /**
     * Memory configuration
     */
    private array $config;

    /**
     * Memory usage tracking
     */
    private array $memoryTracking = [];

    /**
     * Resource registry
     */
    private array $resources = [];

    /**
     * Memory checkpoints
     */
    private array $checkpoints = [];

    /**
     * Constructor
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'memory_limit_mb' => 512,
            'warning_threshold_mb' => 400,
            'gc_threshold_mb' => 300,
            'enable_monitoring' => true,
            'enable_auto_gc' => true,
            'enable_leak_detection' => true,
            'chunk_size' => 1000,
            'max_execution_time' => 300,
            'enable_resource_tracking' => true,
            'cleanup_interval' => 100, // operations
            'memory_optimization_level' => 'moderate' // conservative, moderate, aggressive
        ], $config);

        $this->initializeMemoryManagement();
    }

    /**
     * Initialize memory management
     */
    private function initializeMemoryManagement(): void
    {
        // Set memory limit
        $memoryLimit = $this->config['memory_limit_mb'] . 'M';
        ini_set('memory_limit', $memoryLimit);

        // Set execution time limit
        set_time_limit($this->config['max_execution_time']);

        // Register shutdown function for cleanup
        register_shutdown_function([$this, 'shutdown']);

        // Start monitoring if enabled
        if ($this->config['enable_monitoring']) {
            $this->startMonitoring();
        }
    }

    /**
     * Start memory monitoring
     */
    private function startMonitoring(): void
    {
        $this->memoryTracking = [
            'start_time' => microtime(true),
            'start_memory' => memory_get_usage(),
            'peak_memory' => memory_get_peak_usage(),
            'checkpoints' => [],
            'operations' => 0,
            'gc_calls' => 0,
            'warnings' => []
        ];

        $this->createCheckpoint('initialization');
    }

    /**
     * Create memory checkpoint
     */
    public function createCheckpoint(string $name): void
    {
        if (!$this->config['enable_monitoring']) {
            return;
        }

        $checkpoint = [
            'name' => $name,
            'timestamp' => microtime(true),
            'memory_usage' => memory_get_usage(),
            'memory_usage_real' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(),
            'peak_memory_real' => memory_get_peak_usage(true)
        ];

        $this->checkpoints[] = $checkpoint;
        $this->memoryTracking['checkpoints'][] = $checkpoint;

        // Check memory usage
        $this->checkMemoryUsage($checkpoint);
    }

    /**
     * Check memory usage and take action if needed
     */
    private function checkMemoryUsage(array $checkpoint): void
    {
        $memoryUsageMB = $checkpoint['memory_usage'] / 1024 / 1024;
        $warningThreshold = $this->config['warning_threshold_mb'];
        $memoryLimit = $this->config['memory_limit_mb'];
        $gcThreshold = $this->config['gc_threshold_mb'];

        // Trigger garbage collection if needed
        if ($this->config['enable_auto_gc'] && $memoryUsageMB > $gcThreshold) {
            $this->forceGarbageCollection();
        }

        // Log warning if approaching limit
        if ($memoryUsageMB > $warningThreshold) {
            $warning = [
                'checkpoint' => $checkpoint['name'],
                'memory_usage_mb' => round($memoryUsageMB, 2),
                'threshold_mb' => $warningThreshold,
                'timestamp' => $checkpoint['timestamp']
            ];

            $this->memoryTracking['warnings'][] = $warning;

            Log::warning('High memory usage detected', $warning);
        }

        // Throw exception if exceeding limit
        if ($memoryUsageMB > $memoryLimit) {
            throw new \Exception("Memory usage ({$memoryUsageMB}MB) exceeds limit ({$memoryLimit}MB)");
        }
    }

    /**
     * Force garbage collection
     */
    public function forceGarbageCollection(): void
    {
        $beforeMemory = memory_get_usage();
        
        // Force garbage collection
        gc_collect_cycles();
        
        $afterMemory = memory_get_usage();
        $freedMemory = $beforeMemory - $afterMemory;
        
        $this->memoryTracking['gc_calls']++;
        
        Log::info('Garbage collection performed', [
            'freed_memory_mb' => round($freedMemory / 1024 / 1024, 2),
            'memory_before_mb' => round($beforeMemory / 1024 / 1024, 2),
            'memory_after_mb' => round($afterMemory / 1024 / 1024, 2)
        ]);
    }

    /**
     * Process large dataset in chunks
     */
    public function processLargeDataset(array $data, callable $processor, array $options = []): array
    {
        $chunkSize = $options['chunk_size'] ?? $this->config['chunk_size'];
        $results = [];
        $totalChunks = ceil(count($data) / $chunkSize);
        
        $this->createCheckpoint('large_dataset_start');
        
        for ($i = 0; $i < $totalChunks; $i++) {
            $chunk = array_slice($data, $i * $chunkSize, $chunkSize);
            
            // Process chunk
            $chunkResult = $processor($chunk, $i, $totalChunks);
            
            if ($chunkResult !== null) {
                if (is_array($chunkResult)) {
                    $results = array_merge($results, $chunkResult);
                } else {
                    $results[] = $chunkResult;
                }
            }
            
            // Memory management
            unset($chunk, $chunkResult);
            
            // Periodic cleanup
            if (($i + 1) % $this->config['cleanup_interval'] === 0) {
                $this->performPeriodicCleanup();
                $this->createCheckpoint("chunk_processed_{$i}");
            }
        }
        
        $this->createCheckpoint('large_dataset_end');
        
        return $results;
    }

    /**
     * Perform periodic cleanup
     */
    private function performPeriodicCleanup(): void
    {
        // Clean up resources
        $this->cleanupResources();
        
        // Force garbage collection if needed
        if ($this->config['enable_auto_gc']) {
            $memoryUsageMB = memory_get_usage() / 1024 / 1024;
            if ($memoryUsageMB > $this->config['gc_threshold_mb']) {
                $this->forceGarbageCollection();
            }
        }
        
        // Clean old checkpoints to prevent memory buildup
        $this->cleanupOldCheckpoints();
    }

    /**
     * Clean up old checkpoints
     */
    private function cleanupOldCheckpoints(): void
    {
        // Keep only last 50 checkpoints
        if (count($this->checkpoints) > 50) {
            $this->checkpoints = array_slice($this->checkpoints, -50);
        }
        
        if (count($this->memoryTracking['checkpoints']) > 50) {
            $this->memoryTracking['checkpoints'] = array_slice($this->memoryTracking['checkpoints'], -50);
        }
    }

    /**
     * Register resource for tracking
     */
    public function registerResource(string $id, $resource, string $type = 'generic'): void
    {
        if (!$this->config['enable_resource_tracking']) {
            return;
        }

        $this->resources[$id] = [
            'resource' => $resource,
            'type' => $type,
            'created_at' => microtime(true),
            'memory_at_creation' => memory_get_usage()
        ];
    }

    /**
     * Unregister resource
     */
    public function unregisterResource(string $id): void
    {
        if (isset($this->resources[$id])) {
            $resource = $this->resources[$id];
            
            // Cleanup based on resource type
            $this->cleanupResourceByType($resource['resource'], $resource['type']);
            
            unset($this->resources[$id]);
        }
    }

    /**
     * Cleanup resource by type
     */
    private function cleanupResourceByType($resource, string $type): void
    {
        switch ($type) {
            case 'file_handle':
                if (is_resource($resource)) {
                    fclose($resource);
                }
                break;
                
            case 'curl_handle':
                if (is_resource($resource)) {
                    curl_close($resource);
                }
                break;
                
            case 'database_connection':
                // Database connections are usually handled by Laravel
                break;
                
            case 'temporary_file':
                if (is_string($resource) && file_exists($resource)) {
                    unlink($resource);
                }
                break;
                
            default:
                // Generic cleanup - just unset
                unset($resource);
                break;
        }
    }

    /**
     * Clean up all registered resources
     */
    private function cleanupResources(): void
    {
        foreach ($this->resources as $id => $resource) {
            $this->cleanupResourceByType($resource['resource'], $resource['type']);
        }
        
        $this->resources = [];
    }

    /**
     * Optimize memory usage based on configuration level
     */
    public function optimizeMemoryUsage(): void
    {
        switch ($this->config['memory_optimization_level']) {
            case 'aggressive':
                $this->aggressiveMemoryOptimization();
                break;
                
            case 'moderate':
                $this->moderateMemoryOptimization();
                break;
                
            case 'conservative':
            default:
                $this->conservativeMemoryOptimization();
                break;
        }
    }

    /**
     * Conservative memory optimization
     */
    private function conservativeMemoryOptimization(): void
    {
        // Only basic cleanup
        if ($this->config['enable_auto_gc']) {
            gc_collect_cycles();
        }
    }

    /**
     * Moderate memory optimization
     */
    private function moderateMemoryOptimization(): void
    {
        // Cleanup resources
        $this->cleanupResources();
        
        // Force garbage collection
        if ($this->config['enable_auto_gc']) {
            gc_collect_cycles();
        }
        
        // Clean old checkpoints
        $this->cleanupOldCheckpoints();
    }

    /**
     * Aggressive memory optimization
     */
    private function aggressiveMemoryOptimization(): void
    {
        // All moderate optimizations
        $this->moderateMemoryOptimization();
        
        // Additional aggressive measures
        
        // Disable opcache if enabled (careful in production)
        if (function_exists('opcache_reset') && ini_get('opcache.enable')) {
            opcache_reset();
        }
        
        // Clear realpath cache
        clearstatcache();
        
        // Force multiple GC cycles
        for ($i = 0; $i < 3; $i++) {
            gc_collect_cycles();
        }
    }

    /**
     * Detect memory leaks
     */
    public function detectMemoryLeaks(): array
    {
        if (!$this->config['enable_leak_detection'] || count($this->checkpoints) < 3) {
            return [];
        }

        $leaks = [];
        $checkpointCount = count($this->checkpoints);
        
        // Analyze memory growth pattern
        for ($i = 2; $i < $checkpointCount; $i++) {
            $current = $this->checkpoints[$i];
            $previous = $this->checkpoints[$i - 1];
            $beforePrevious = $this->checkpoints[$i - 2];
            
            $growth1 = $previous['memory_usage'] - $beforePrevious['memory_usage'];
            $growth2 = $current['memory_usage'] - $previous['memory_usage'];
            
            // Detect consistent memory growth (potential leak)
            if ($growth1 > 0 && $growth2 > 0 && $growth2 > $growth1 * 1.5) {
                $leaks[] = [
                    'checkpoint' => $current['name'],
                    'memory_growth_mb' => round($growth2 / 1024 / 1024, 2),
                    'growth_rate' => round(($growth2 / $growth1) * 100, 2) . '%',
                    'timestamp' => $current['timestamp']
                ];
            }
        }
        
        return $leaks;
    }

    /**
     * Get memory statistics
     */
    public function getMemoryStatistics(): array
    {
        if (!$this->config['enable_monitoring']) {
            return [];
        }

        $currentMemory = memory_get_usage();
        $peakMemory = memory_get_peak_usage();
        $startMemory = $this->memoryTracking['start_memory'];
        
        return [
            'current_usage_mb' => round($currentMemory / 1024 / 1024, 2),
            'peak_usage_mb' => round($peakMemory / 1024 / 1024, 2),
            'start_usage_mb' => round($startMemory / 1024 / 1024, 2),
            'memory_growth_mb' => round(($currentMemory - $startMemory) / 1024 / 1024, 2),
            'limit_mb' => $this->config['memory_limit_mb'],
            'usage_percentage' => round(($currentMemory / 1024 / 1024) / $this->config['memory_limit_mb'] * 100, 2),
            'gc_calls' => $this->memoryTracking['gc_calls'],
            'warnings_count' => count($this->memoryTracking['warnings']),
            'checkpoints_count' => count($this->checkpoints),
            'resources_count' => count($this->resources),
            'execution_time' => round(microtime(true) - $this->memoryTracking['start_time'], 2),
            'memory_leaks' => $this->detectMemoryLeaks()
        ];
    }

    /**
     * Get detailed memory report
     */
    public function getDetailedMemoryReport(): array
    {
        return [
            'statistics' => $this->getMemoryStatistics(),
            'checkpoints' => $this->checkpoints,
            'warnings' => $this->memoryTracking['warnings'] ?? [],
            'resources' => array_map(function($resource) {
                return [
                    'type' => $resource['type'],
                    'created_at' => $resource['created_at'],
                    'memory_at_creation' => round($resource['memory_at_creation'] / 1024 / 1024, 2) . 'MB'
                ];
            }, $this->resources),
            'configuration' => $this->config
        ];
    }

    /**
     * Export memory report to file
     */
    public function exportMemoryReport(string $filename = null): string
    {
        if (!$filename) {
            $filename = 'memory_report_' . date('Y-m-d_H-i-s') . '.json';
        }
        
        $report = $this->getDetailedMemoryReport();
        $json = json_encode($report, JSON_PRETTY_PRINT);
        
        $filepath = storage_path('logs/' . $filename);
        file_put_contents($filepath, $json);
        
        return $filepath;
    }

    /**
     * Shutdown cleanup
     */
    public function shutdown(): void
    {
        if ($this->config['enable_monitoring']) {
            $this->createCheckpoint('shutdown');
            
            // Log final statistics
            $stats = $this->getMemoryStatistics();
            Log::info('Memory management shutdown', $stats);
            
            // Detect and log memory leaks
            $leaks = $this->detectMemoryLeaks();
            if (!empty($leaks)) {
                Log::warning('Memory leaks detected', $leaks);
            }
        }
        
        // Final cleanup
        $this->cleanupResources();
    }

    /**
     * Reset memory tracking
     */
    public function reset(): void
    {
        $this->memoryTracking = [];
        $this->checkpoints = [];
        $this->resources = [];
        
        if ($this->config['enable_monitoring']) {
            $this->startMonitoring();
        }
    }

    /**
     * Set configuration
     */
    public function setConfig(array $config): void
    {
        $this->config = array_merge($this->config, $config);
        
        // Update memory limit if changed
        if (isset($config['memory_limit_mb'])) {
            ini_set('memory_limit', $config['memory_limit_mb'] . 'M');
        }
        
        // Update execution time limit if changed
        if (isset($config['max_execution_time'])) {
            set_time_limit($config['max_execution_time']);
        }
    }

    /**
     * Get configuration
     */
    public function getConfig(): array
    {
        return $this->config;
    }
}