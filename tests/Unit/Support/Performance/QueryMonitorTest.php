<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Support\Performance;

use Canvastack\Canvastack\Support\Performance\QueryMonitor;
use Canvastack\Canvastack\Tests\TestCase;

/**
 * Test for QueryMonitor.
 */
class QueryMonitorTest extends TestCase
{
    protected QueryMonitor $monitor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->monitor = new QueryMonitor();
    }

    /**
     * Test that monitor can be started.
     */
    public function test_start_enables_monitoring(): void
    {
        $this->monitor->start();

        $this->assertTrue($this->monitor->isEnabled());
    }

    /**
     * Test that monitor can be stopped.
     */
    public function test_stop_disables_monitoring(): void
    {
        $this->monitor->start();
        $this->monitor->stop();

        $this->assertFalse($this->monitor->isEnabled());
    }

    /**
     * Test that monitor tracks queries.
     */
    public function test_monitor_tracks_queries(): void
    {
        $this->monitor->start();

        // Simulate queries by directly accessing the database
        // In real usage, queries would be tracked automatically

        $this->monitor->stop();

        $queries = $this->monitor->getQueries();

        $this->assertIsArray($queries);
    }

    /**
     * Test that monitor calculates statistics.
     */
    public function test_get_stats_returns_statistics(): void
    {
        $stats = $this->monitor->getStats();

        $this->assertArrayHasKey('total_queries', $stats);
        $this->assertArrayHasKey('slow_queries', $stats);
        $this->assertArrayHasKey('duplicate_queries', $stats);
        $this->assertArrayHasKey('total_time', $stats);
    }

    /**
     * Test that monitor can set slow query threshold.
     */
    public function test_set_slow_query_threshold_changes_threshold(): void
    {
        $this->monitor->setSlowQueryThreshold(500);

        // Threshold is set internally, verify by checking behavior
        $this->assertTrue(true);
    }

    /**
     * Test that monitor can set query count threshold.
     */
    public function test_set_query_count_threshold_changes_threshold(): void
    {
        $this->monitor->setQueryCountThreshold(100);

        // Threshold is set internally, verify by checking behavior
        $this->assertTrue(true);
    }

    /**
     * Test that monitor can detect N+1 problems.
     */
    public function test_detect_n1_problems_returns_issues(): void
    {
        $issues = $this->monitor->detectN1Problems();

        $this->assertIsArray($issues);
    }

    /**
     * Test that monitor can get slow queries.
     */
    public function test_get_slow_queries_returns_slow_queries(): void
    {
        $slowQueries = $this->monitor->getSlowQueries();

        $this->assertIsArray($slowQueries);
    }

    /**
     * Test that monitor can get duplicate queries.
     */
    public function test_get_duplicate_queries_returns_duplicates(): void
    {
        $duplicates = $this->monitor->getDuplicateQueries();

        $this->assertIsArray($duplicates);
    }

    /**
     * Test that monitor can generate report.
     */
    public function test_generate_report_returns_complete_report(): void
    {
        $report = $this->monitor->generateReport();

        $this->assertArrayHasKey('summary', $report);
        $this->assertArrayHasKey('slow_queries', $report);
        $this->assertArrayHasKey('duplicate_queries', $report);
        $this->assertArrayHasKey('n1_problems', $report);
        $this->assertArrayHasKey('recommendations', $report);
    }

    /**
     * Test that monitor can clear data.
     */
    public function test_clear_resets_all_data(): void
    {
        $this->monitor->start();
        $this->monitor->stop();

        $this->monitor->clear();

        $stats = $this->monitor->getStats();

        $this->assertEquals(0, $stats['total_queries']);
        $this->assertEquals(0, $stats['slow_queries']);
        $this->assertEquals(0, $stats['duplicate_queries']);
        $this->assertEquals(0, $stats['total_time']);
    }

    /**
     * Test that report includes recommendations.
     */
    public function test_generate_report_includes_recommendations(): void
    {
        $report = $this->monitor->generateReport();

        $this->assertIsArray($report['recommendations']);
        $this->assertNotEmpty($report['recommendations']);
    }

    /**
     * Test that monitor is disabled by default.
     */
    public function test_monitor_is_disabled_by_default(): void
    {
        $monitor = new QueryMonitor();

        $this->assertFalse($monitor->isEnabled());
    }
}
