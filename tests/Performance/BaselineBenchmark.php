<?php

namespace Canvastack\Canvastack\Tests\Performance;

use Canvastack\Canvastack\Tests\TestCase;

abstract class BaselineBenchmark extends TestCase
{
    protected array $metrics = [];

    protected function startBenchmark(): void
    {
        $this->metrics['start_time'] = microtime(true);
        $this->metrics['start_memory'] = memory_get_usage();
        $this->metrics['start_peak_memory'] = memory_get_peak_usage();
    }

    protected function endBenchmark(): array
    {
        $this->metrics['end_time'] = microtime(true);
        $this->metrics['end_memory'] = memory_get_usage();
        $this->metrics['end_peak_memory'] = memory_get_peak_usage();

        return [
            'time_ms' => ($this->metrics['end_time'] - $this->metrics['start_time']) * 1000,
            'memory_mb' => ($this->metrics['end_memory'] - $this->metrics['start_memory']) / 1024 / 1024,
            'peak_memory_mb' => ($this->metrics['end_peak_memory'] - $this->metrics['start_peak_memory']) / 1024 / 1024,
        ];
    }

    protected function assertPerformance(array $results, float $maxTime, float $maxMemory): void
    {
        $this->assertLessThan(
            $maxTime,
            $results['time_ms'],
            "Performance test exceeded time limit: {$results['time_ms']}ms > {$maxTime}ms"
        );

        $this->assertLessThan(
            $maxMemory,
            $results['memory_mb'],
            "Performance test exceeded memory limit: {$results['memory_mb']}MB > {$maxMemory}MB"
        );
    }

    protected function logResults(string $testName, array $results): void
    {
        echo "\n";
        echo "=== {$testName} ===\n";
        echo sprintf("Time: %.2fms\n", $results['time_ms']);
        echo sprintf("Memory: %.2fMB\n", $results['memory_mb']);
        echo sprintf("Peak Memory: %.2fMB\n", $results['peak_memory_mb']);
        echo "\n";
    }
}
