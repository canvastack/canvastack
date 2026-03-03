#!/usr/bin/env php
<?php

/**
 * Performance Monitoring Script
 *
 * This script monitors performance metrics and generates reports.
 * It can be run manually or scheduled via cron.
 *
 * Usage:
 *   php scripts/performance-monitor.php [options]
 *
 * Options:
 *   --format=<format>    Output format: text, json, markdown (default: text)
 *   --output=<file>      Output file (default: stdout)
 *   --threshold=<num>    Regression threshold percentage (default: 20)
 *   --baseline=<file>    Baseline metrics file (default: .performance-baseline.json)
 *   --save-baseline      Save current metrics as baseline
 *   --compare=<file>     Compare with another metrics file
 *   --verbose            Show detailed output
 */

require __DIR__ . '/../vendor/autoload.php';

use Illuminate\Support\Facades\Artisan;

// Parse command line arguments
$options = getopt('', [
    'format:',
    'output:',
    'threshold:',
    'baseline:',
    'save-baseline',
    'compare:',
    'verbose',
    'help',
]);

if (isset($options['help'])) {
    showHelp();
    exit(0);
}

$format = $options['format'] ?? 'text';
$output = $options['output'] ?? 'php://stdout';
$threshold = (float) ($options['threshold'] ?? 20.0);
$baselineFile = $options['baseline'] ?? __DIR__ . '/../.performance-baseline.json';
$compareFile = $options['compare'] ?? null;
$verbose = isset($options['verbose']);
$saveBaseline = isset($options['save-baseline']);

// Run performance tests
echo "Running performance tests...\n";
$testOutput = shell_exec('cd ' . __DIR__ . '/../ && ./vendor/bin/phpunit --testsuite=Performance --log-json=.performance-results.json 2>&1');

if ($verbose) {
    echo $testOutput . "\n";
}

// Load test results
$resultsFile = __DIR__ . '/../.performance-results.json';
if (!file_exists($resultsFile)) {
    echo "Error: Performance test results not found.\n";
    exit(1);
}

$results = json_decode(file_get_contents($resultsFile), true);

// Extract metrics from results
$metrics = extractMetrics($results);

// Save baseline if requested
if ($saveBaseline) {
    file_put_contents($baselineFile, json_encode($metrics, JSON_PRETTY_PRINT));
    echo "Baseline saved to {$baselineFile}\n";
    exit(0);
}

// Load baseline
$baseline = null;
if (file_exists($baselineFile)) {
    $baseline = json_decode(file_get_contents($baselineFile), true);
}

// Compare with baseline or another file
$comparison = null;
if ($compareFile && file_exists($compareFile)) {
    $compareMetrics = json_decode(file_get_contents($compareFile), true);
    $comparison = compareMetrics($metrics, $compareMetrics, $threshold);
} elseif ($baseline) {
    $comparison = compareMetrics($metrics, $baseline, $threshold);
}

// Generate report
$report = generateReport($metrics, $baseline, $comparison, $format);

// Output report
if ($output === 'php://stdout') {
    echo $report;
} else {
    file_put_contents($output, $report);
    echo "Report saved to {$output}\n";
}

// Exit with error code if regressions detected
if ($comparison && $comparison['has_regressions']) {
    exit(1);
}

exit(0);

/**
 * Extract metrics from test results.
 */
function extractMetrics(array $results): array
{
    $metrics = [
        'timestamp' => date('Y-m-d H:i:s'),
        'tests' => [],
    ];

    foreach ($results['tests'] ?? [] as $test) {
        if (strpos($test['name'], 'Performance') === false) {
            continue;
        }

        $testName = basename($test['name']);
        $metrics['tests'][$testName] = [
            'status' => $test['status'],
            'time' => $test['time'] ?? 0,
            'memory' => $test['memory'] ?? 0,
        ];
    }

    return $metrics;
}

/**
 * Compare metrics with baseline.
 */
function compareMetrics(array $current, array $baseline, float $threshold): array
{
    $comparison = [
        'has_regressions' => false,
        'improvements' => [],
        'regressions' => [],
        'unchanged' => [],
    ];

    foreach ($current['tests'] as $testName => $currentMetrics) {
        if (!isset($baseline['tests'][$testName])) {
            continue;
        }

        $baselineMetrics = $baseline['tests'][$testName];
        $timeDiff = $currentMetrics['time'] - $baselineMetrics['time'];
        $timePercent = ($timeDiff / $baselineMetrics['time']) * 100;

        $memoryDiff = $currentMetrics['memory'] - $baselineMetrics['memory'];
        $memoryPercent = ($memoryDiff / $baselineMetrics['memory']) * 100;

        $item = [
            'test' => $testName,
            'current_time' => $currentMetrics['time'],
            'baseline_time' => $baselineMetrics['time'],
            'time_diff' => $timeDiff,
            'time_percent' => $timePercent,
            'current_memory' => $currentMetrics['memory'],
            'baseline_memory' => $baselineMetrics['memory'],
            'memory_diff' => $memoryDiff,
            'memory_percent' => $memoryPercent,
        ];

        if ($timePercent > $threshold || $memoryPercent > $threshold) {
            $comparison['regressions'][] = $item;
            $comparison['has_regressions'] = true;
        } elseif ($timePercent < -5 || $memoryPercent < -5) {
            $comparison['improvements'][] = $item;
        } else {
            $comparison['unchanged'][] = $item;
        }
    }

    return $comparison;
}

/**
 * Generate report in specified format.
 */
function generateReport(array $metrics, ?array $baseline, ?array $comparison, string $format): string
{
    switch ($format) {
        case 'json':
            return json_encode([
                'metrics' => $metrics,
                'baseline' => $baseline,
                'comparison' => $comparison,
            ], JSON_PRETTY_PRINT);

        case 'markdown':
            return generateMarkdownReport($metrics, $baseline, $comparison);

        case 'text':
        default:
            return generateTextReport($metrics, $baseline, $comparison);
    }
}

/**
 * Generate text report.
 */
function generateTextReport(array $metrics, ?array $baseline, ?array $comparison): string
{
    $report = "Performance Test Report\n";
    $report .= "======================\n\n";
    $report .= "Timestamp: {$metrics['timestamp']}\n";
    $report .= "Total Tests: " . count($metrics['tests']) . "\n\n";

    if ($comparison) {
        $report .= "Comparison Results:\n";
        $report .= "-------------------\n";
        $report .= "Improvements: " . count($comparison['improvements']) . "\n";
        $report .= "Regressions: " . count($comparison['regressions']) . "\n";
        $report .= "Unchanged: " . count($comparison['unchanged']) . "\n\n";

        if (!empty($comparison['regressions'])) {
            $report .= "⚠️  REGRESSIONS DETECTED:\n\n";
            foreach ($comparison['regressions'] as $item) {
                $report .= "  {$item['test']}\n";
                $report .= sprintf("    Time: %.2fms → %.2fms (%+.1f%%)\n",
                    $item['baseline_time'] * 1000,
                    $item['current_time'] * 1000,
                    $item['time_percent']
                );
                $report .= sprintf("    Memory: %.2fMB → %.2fMB (%+.1f%%)\n\n",
                    $item['baseline_memory'] / 1024 / 1024,
                    $item['current_memory'] / 1024 / 1024,
                    $item['memory_percent']
                );
            }
        }

        if (!empty($comparison['improvements'])) {
            $report .= "✓ IMPROVEMENTS:\n\n";
            foreach ($comparison['improvements'] as $item) {
                $report .= "  {$item['test']}\n";
                $report .= sprintf("    Time: %.2fms → %.2fms (%+.1f%%)\n",
                    $item['baseline_time'] * 1000,
                    $item['current_time'] * 1000,
                    $item['time_percent']
                );
                $report .= sprintf("    Memory: %.2fMB → %.2fMB (%+.1f%%)\n\n",
                    $item['baseline_memory'] / 1024 / 1024,
                    $item['current_memory'] / 1024 / 1024,
                    $item['memory_percent']
                );
            }
        }
    } else {
        $report .= "Test Results:\n";
        $report .= "-------------\n\n";
        foreach ($metrics['tests'] as $testName => $testMetrics) {
            $report .= "  {$testName}\n";
            $report .= sprintf("    Time: %.2fms\n", $testMetrics['time'] * 1000);
            $report .= sprintf("    Memory: %.2fMB\n", $testMetrics['memory'] / 1024 / 1024);
            $report .= sprintf("    Status: %s\n\n", $testMetrics['status']);
        }
    }

    return $report;
}

/**
 * Generate markdown report.
 */
function generateMarkdownReport(array $metrics, ?array $baseline, ?array $comparison): string
{
    $report = "# Performance Test Report\n\n";
    $report .= "**Timestamp**: {$metrics['timestamp']}  \n";
    $report .= "**Total Tests**: " . count($metrics['tests']) . "\n\n";

    if ($comparison) {
        $report .= "## Summary\n\n";
        $report .= "| Metric | Count |\n";
        $report .= "|--------|-------|\n";
        $report .= "| Improvements | " . count($comparison['improvements']) . " |\n";
        $report .= "| Regressions | " . count($comparison['regressions']) . " |\n";
        $report .= "| Unchanged | " . count($comparison['unchanged']) . " |\n\n";

        if (!empty($comparison['regressions'])) {
            $report .= "## ⚠️ Regressions Detected\n\n";
            $report .= "| Test | Time (Baseline) | Time (Current) | Change | Memory (Baseline) | Memory (Current) | Change |\n";
            $report .= "|------|-----------------|----------------|--------|-------------------|------------------|--------|\n";
            foreach ($comparison['regressions'] as $item) {
                $report .= sprintf(
                    "| %s | %.2fms | %.2fms | %+.1f%% | %.2fMB | %.2fMB | %+.1f%% |\n",
                    $item['test'],
                    $item['baseline_time'] * 1000,
                    $item['current_time'] * 1000,
                    $item['time_percent'],
                    $item['baseline_memory'] / 1024 / 1024,
                    $item['current_memory'] / 1024 / 1024,
                    $item['memory_percent']
                );
            }
            $report .= "\n";
        }

        if (!empty($comparison['improvements'])) {
            $report .= "## ✓ Improvements\n\n";
            $report .= "| Test | Time (Baseline) | Time (Current) | Change | Memory (Baseline) | Memory (Current) | Change |\n";
            $report .= "|------|-----------------|----------------|--------|-------------------|------------------|--------|\n";
            foreach ($comparison['improvements'] as $item) {
                $report .= sprintf(
                    "| %s | %.2fms | %.2fms | %+.1f%% | %.2fMB | %.2fMB | %+.1f%% |\n",
                    $item['test'],
                    $item['baseline_time'] * 1000,
                    $item['current_time'] * 1000,
                    $item['time_percent'],
                    $item['baseline_memory'] / 1024 / 1024,
                    $item['current_memory'] / 1024 / 1024,
                    $item['memory_percent']
                );
            }
            $report .= "\n";
        }
    } else {
        $report .= "## Test Results\n\n";
        $report .= "| Test | Time | Memory | Status |\n";
        $report .= "|------|------|--------|--------|\n";
        foreach ($metrics['tests'] as $testName => $testMetrics) {
            $report .= sprintf(
                "| %s | %.2fms | %.2fMB | %s |\n",
                $testName,
                $testMetrics['time'] * 1000,
                $testMetrics['memory'] / 1024 / 1024,
                $testMetrics['status']
            );
        }
        $report .= "\n";
    }

    return $report;
}

/**
 * Show help message.
 */
function showHelp(): void
{
    echo <<<HELP
Performance Monitoring Script

Usage:
  php scripts/performance-monitor.php [options]

Options:
  --format=<format>    Output format: text, json, markdown (default: text)
  --output=<file>      Output file (default: stdout)
  --threshold=<num>    Regression threshold percentage (default: 20)
  --baseline=<file>    Baseline metrics file (default: .performance-baseline.json)
  --save-baseline      Save current metrics as baseline
  --compare=<file>     Compare with another metrics file
  --verbose            Show detailed output
  --help               Show this help message

Examples:
  # Run tests and show results
  php scripts/performance-monitor.php

  # Save current metrics as baseline
  php scripts/performance-monitor.php --save-baseline

  # Compare with baseline and output markdown
  php scripts/performance-monitor.php --format=markdown --output=report.md

  # Compare with custom threshold
  php scripts/performance-monitor.php --threshold=15

  # Compare two metrics files
  php scripts/performance-monitor.php --compare=metrics-old.json

HELP;
}

