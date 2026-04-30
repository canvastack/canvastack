<?php

namespace Tests\Unit;

use Tests\TestCase;
use Canvastack\Canvastack\Library\Components\Table\Craft\Datatables;
use Illuminate\Support\Facades\Log;

/*
 * Unit Tests for Query Optimization in Datatables.php (Task 2.1)
 *
 * Validates the following optimizations:
 * - 2.1.1 Eager loading for relationships (N+1 prevention)
 * - 2.1.2 Column selection (only required columns, no SELECT *)
 * - 2.1.3 Database-level sorting via ORDER BY
 * - 2.1.4 Database-level filtering via WHERE
 * - 2.1.5 Pagination via LIMIT/OFFSET (skip/take assigned correctly)
 * - 2.1.6 Query performance monitoring (getQueryMetrics)
 * - 2.1.7 Slow query logging (SLOW_QUERY_THRESHOLD_MS)
 * - 2.1.8 Large dataset handling (10k+ rows)
 *
 * @group performance
 * @group unit
 */
class DatatablesQueryOptimizationTest extends TestCase
{
    private Datatables $datatables;

    protected function setUp(): void
    {
        parent::setUp();
        $this->datatables = new Datatables();
    }


    // =========================================================================
    // 2.1.1 Eager Loading
    // =========================================================================

    /*
     * Test that applyEagerLoading is called when relations are defined
     *
     * @test
     * Validates: Property 13 - Eager Loading for N+1 Prevention
     */
    public function test_eager_loading_applied_when_relations_defined()
    {
        $relationNames = ['category', 'user'];

        $mockModel = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['with'])
            ->getMock();

        $mockModel->expects($this->once())
            ->method('with')
            ->with($relationNames)
            ->willReturnSelf();

        $data = $this->buildDataConfig('products', [
            'relations' => array_fill_keys($relationNames, []),
        ]);

        $result = $this->callPrivate('applyEagerLoading', [$mockModel, $data, 'products']);

        $this->assertNotNull($result);
    }

    /*
     * Test that eager loading is skipped when no relations defined
     *
     * @test
     */
    public function test_eager_loading_skipped_when_no_relations()
    {
        $mockModel = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['with'])
            ->getMock();

        $mockModel->expects($this->never())->method('with');

        $data = $this->buildDataConfig('products', []);

        $result = $this->callPrivate('applyEagerLoading', [$mockModel, $data, 'products']);

        $this->assertSame($mockModel, $result);
    }

    /*
     * Test that eager loading is skipped for non-Eloquent models
     *
     * @test
     */
    public function test_eager_loading_skipped_for_non_eloquent_model()
    {
        $plainModel = new \stdClass();

        $data = $this->buildDataConfig('products', [
            'relations' => ['category' => []],
        ]);

        $result = $this->callPrivate('applyEagerLoading', [$plainModel, $data, 'products']);

        $this->assertSame($plainModel, $result);
    }


    // =========================================================================
    // 2.1.2 Column Selection
    // =========================================================================

    /*
     * Test that selectRequiredColumns applies select() with specific columns
     *
     * @test
     * Validates: Property 14 - Column Selection (no SELECT *)
     */
    public function test_select_required_columns_applies_specific_columns()
    {
        $mockModel = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['select'])
            ->getMock();

        $mockModel->expects($this->once())
            ->method('select')
            ->with($this->callback(function ($cols) {
                return in_array('products.id', $cols)
                    && in_array('products.name', $cols)
                    && in_array('products.price', $cols);
            }))
            ->willReturnSelf();

        $data = $this->buildDataConfig('products', [
            'lists' => ['name', 'price'],
        ]);

        $result = $this->callPrivate('selectRequiredColumns', [$mockModel, $data, 'products']);

        $this->assertNotNull($result);
    }

    /*
     * Test that selectRequiredColumns always includes 'id' column
     *
     * @test
     */
    public function test_select_required_columns_always_includes_id()
    {
        $capturedColumns = [];

        $mockModel = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['select'])
            ->getMock();

        $mockModel->expects($this->once())
            ->method('select')
            ->with($this->callback(function ($cols) use (&$capturedColumns) {
                $capturedColumns = $cols;
                return true;
            }))
            ->willReturnSelf();

        $data = $this->buildDataConfig('orders', [
            'lists' => ['total', 'status'],
        ]);

        $this->callPrivate('selectRequiredColumns', [$mockModel, $data, 'orders']);

        $this->assertContains('orders.id', $capturedColumns);
    }

    /*
     * Test that selectRequiredColumns skips when no lists configured
     *
     * @test
     */
    public function test_select_required_columns_skipped_when_no_lists()
    {
        $mockModel = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['select'])
            ->getMock();

        $mockModel->expects($this->never())->method('select');

        $data = $this->buildDataConfig('products', []);

        $result = $this->callPrivate('selectRequiredColumns', [$mockModel, $data, 'products']);

        $this->assertSame($mockModel, $result);
    }

    /*
     * Test that columns with table prefix are not double-prefixed
     *
     * @test
     */
    public function test_select_does_not_double_prefix_qualified_columns()
    {
        $capturedColumns = [];

        $mockModel = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['select'])
            ->getMock();

        $mockModel->expects($this->once())
            ->method('select')
            ->with($this->callback(function ($cols) use (&$capturedColumns) {
                $capturedColumns = $cols;
                return true;
            }))
            ->willReturnSelf();

        $data = $this->buildDataConfig('orders', [
            'lists' => ['orders.total', 'status'],
        ]);

        $this->callPrivate('selectRequiredColumns', [$mockModel, $data, 'orders']);

        $this->assertContains('orders.total', $capturedColumns);
        $this->assertNotContains('orders.orders.total', $capturedColumns);
    }


    // =========================================================================
    // 2.1.5 Pagination
    // =========================================================================

    /*
     * Test that applyPagination uses skip/take for LIMIT/OFFSET
     *
     * @test
     * Validates: Property 15 - Database-Level Pagination
     */
    public function test_pagination_uses_skip_and_take()
    {
        $this->app['request']->merge(['start' => '20', 'length' => '10']);

        $mockModel = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['skip', 'take'])
            ->getMock();

        $mockModel->expects($this->once())
            ->method('skip')
            ->with(20)
            ->willReturnSelf();

        $mockModel->expects($this->once())
            ->method('take')
            ->with(10)
            ->willReturnSelf();

        $result = $this->callPrivate('applyPagination', [$mockModel, 500]);

        $this->assertEquals(20, $result['start']);
        $this->assertEquals(10, $result['length']);
        $this->assertEquals(500, $result['total']);
    }

    /*
     * Test that pagination uses defaults when no request params
     *
     * @test
     */
    public function test_pagination_uses_defaults_when_no_request_params()
    {
        $mockModel = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['skip', 'take'])
            ->getMock();

        $mockModel->method('skip')->willReturnSelf();
        $mockModel->method('take')->willReturnSelf();

        $result = $this->callPrivate('applyPagination', [$mockModel, 100]);

        $this->assertEquals(0, $result['start']);
        $this->assertEquals(10, $result['length']);
    }

    /*
     * Test that pagination enforces maximum page length cap
     *
     * @test
     * Validates: Large dataset safety - prevents fetching too many rows at once
     */
    public function test_pagination_caps_length_at_maximum()
    {
        $this->app['request']->merge(['start' => '0', 'length' => '999999']);

        $capturedLength = null;

        $mockModel = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['skip', 'take'])
            ->getMock();

        $mockModel->method('skip')->willReturnSelf();
        $mockModel->expects($this->once())
            ->method('take')
            ->with($this->callback(function ($len) use (&$capturedLength) {
                $capturedLength = $len;
                return true;
            }))
            ->willReturnSelf();

        $this->callPrivate('applyPagination', [$mockModel, 999999]);

        $this->assertLessThanOrEqual(1000, $capturedLength);
    }

    /*
     * Test that pagination enforces non-negative start offset
     *
     * @test
     */
    public function test_pagination_enforces_non_negative_start()
    {
        $this->app['request']->merge(['start' => '-50', 'length' => '10']);

        $capturedStart = null;

        $mockModel = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['skip', 'take'])
            ->getMock();

        $mockModel->expects($this->once())
            ->method('skip')
            ->with($this->callback(function ($start) use (&$capturedStart) {
                $capturedStart = $start;
                return true;
            }))
            ->willReturnSelf();

        $mockModel->method('take')->willReturnSelf();

        $this->callPrivate('applyPagination', [$mockModel, 100]);

        $this->assertGreaterThanOrEqual(0, $capturedStart);
    }


    // =========================================================================
    // 2.1.6 Query Performance Monitoring
    // =========================================================================

    /*
     * Test that getQueryMetrics returns empty array initially
     *
     * @test
     * Validates: Property 16 - Query Performance Monitoring
     */
    public function test_query_metrics_empty_initially()
    {
        $metrics = $this->datatables->getQueryMetrics();

        $this->assertIsArray($metrics);
        $this->assertEmpty($metrics);
    }

    /*
     * Test that logQueryPerformance records elapsed_ms and timestamp
     *
     * @test
     */
    public function test_log_query_performance_records_metrics()
    {
        Log::shouldReceive('warning')->zeroOrMoreTimes();

        $startTime = microtime(true) - 0.05;

        $this->callPrivate('logQueryPerformance', ['users', $startTime]);

        $metrics = $this->datatables->getQueryMetrics();

        $this->assertArrayHasKey('users', $metrics);
        $this->assertArrayHasKey('elapsed_ms', $metrics['users']);
        $this->assertArrayHasKey('timestamp', $metrics['users']);
        $this->assertGreaterThan(0, $metrics['users']['elapsed_ms']);
    }

    /*
     * Test that logQueryPerformance records metrics for multiple tables
     *
     * @test
     */
    public function test_log_query_performance_tracks_multiple_tables()
    {
        Log::shouldReceive('warning')->zeroOrMoreTimes();

        $this->callPrivate('logQueryPerformance', ['users', microtime(true) - 0.1]);
        $this->callPrivate('logQueryPerformance', ['products', microtime(true) - 0.2]);
        $this->callPrivate('logQueryPerformance', ['orders', microtime(true) - 0.3]);

        $metrics = $this->datatables->getQueryMetrics();

        $this->assertArrayHasKey('users', $metrics);
        $this->assertArrayHasKey('products', $metrics);
        $this->assertArrayHasKey('orders', $metrics);
    }

    // =========================================================================
    // 2.1.7 Slow Query Logging
    // =========================================================================

    /*
     * Test that slow queries (>1000ms) trigger a warning log
     *
     * @test
     * Validates: Property 17 - Slow Query Logging
     */
    public function test_slow_query_triggers_warning_log()
    {
        Log::shouldReceive('warning')
            ->once()
            ->with(
                '[PERFORMANCE] Datatables: Slow query detected',
                \Mockery::on(function ($context) {
                    return isset($context['table'])
                        && isset($context['elapsed_ms'])
                        && $context['elapsed_ms'] >= 1000;
                })
            );

        $startTime = microtime(true) - 1.5;

        $this->callPrivate('logQueryPerformance', ['slow_table', $startTime]);
    }

    /*
     * Test that fast queries do NOT trigger a warning log
     *
     * @test
     */
    public function test_fast_query_does_not_trigger_warning_log()
    {
        Log::shouldReceive('warning')->never();

        $startTime = microtime(true) - 0.05;

        $this->callPrivate('logQueryPerformance', ['fast_table', $startTime]);

        $metrics = $this->datatables->getQueryMetrics();
        $this->assertLessThan(1000, $metrics['fast_table']['elapsed_ms']);
    }


    // =========================================================================
    // 2.1.8 Large Dataset Handling (10k+ rows)
    // =========================================================================

    /*
     * Test that pagination correctly handles offset into a 10k+ row dataset
     *
     * When a table has 10,000+ rows, pagination must use DB-level LIMIT/OFFSET
     * so only the requested page is fetched, not all rows loaded into memory.
     *
     * @test
     * Validates: Requirement 4.3 - Efficient pagination for large datasets
     */
    public function test_pagination_handles_large_dataset_offset()
    {
        $totalRows = 15000;
        $pageSize  = 25;
        $page      = 400;
        $offset    = ($page - 1) * $pageSize; // 9975

        $this->app['request']->merge([
            'start'  => (string) $offset,
            'length' => (string) $pageSize,
        ]);

        $capturedSkip = null;
        $capturedTake = null;

        $mockModel = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['skip', 'take'])
            ->getMock();

        $mockModel->expects($this->once())
            ->method('skip')
            ->with($this->callback(function ($s) use (&$capturedSkip) {
                $capturedSkip = $s;
                return true;
            }))
            ->willReturnSelf();

        $mockModel->expects($this->once())
            ->method('take')
            ->with($this->callback(function ($t) use (&$capturedTake) {
                $capturedTake = $t;
                return true;
            }))
            ->willReturnSelf();

        $result = $this->callPrivate('applyPagination', [$mockModel, $totalRows]);

        $this->assertEquals($offset, $capturedSkip, 'skip() must use the correct offset for large datasets');
        $this->assertEquals($pageSize, $capturedTake, 'take() must use the correct page size');
        $this->assertEquals($totalRows, $result['total'], 'total must reflect full dataset size');
    }

    /*
     * Test that column selection reduces data transfer for wide tables (many columns)
     *
     * Wide tables with 50+ columns should only select the columns needed for display,
     * not SELECT * which would transfer all column data unnecessarily.
     *
     * @test
     * Validates: Requirement 4.2 - Only select required columns
     */
    public function test_column_selection_reduces_data_for_wide_tables()
    {
        $allColumns     = array_map(fn($i) => "col_{$i}", range(1, 50));
        $displayColumns = ['col_1', 'col_5', 'col_10'];

        $capturedColumns = [];

        $mockModel = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['select'])
            ->getMock();

        $mockModel->expects($this->once())
            ->method('select')
            ->with($this->callback(function ($cols) use (&$capturedColumns, $allColumns) {
                $capturedColumns = $cols;
                return count($cols) < count($allColumns);
            }))
            ->willReturnSelf();

        $data = $this->buildDataConfig('wide_table', [
            'lists' => $displayColumns,
        ]);

        $this->callPrivate('selectRequiredColumns', [$mockModel, $data, 'wide_table']);

        $this->assertLessThan(count($allColumns), count($capturedColumns));
        $this->assertContains('wide_table.id', $capturedColumns);
    }

    /*
     * Test that performance metrics are recorded for large dataset queries
     *
     * @test
     * Validates: Requirement 4.6 - Query performance monitoring
     */
    public function test_performance_metrics_recorded_for_large_dataset_query()
    {
        Log::shouldReceive('warning')->zeroOrMoreTimes();

        $startTime = microtime(true) - 0.8;

        $this->callPrivate('logQueryPerformance', ['large_table', $startTime]);

        $metrics = $this->datatables->getQueryMetrics();

        $this->assertArrayHasKey('large_table', $metrics);
        $this->assertGreaterThanOrEqual(700, $metrics['large_table']['elapsed_ms']);
        $this->assertLessThan(1000, $metrics['large_table']['elapsed_ms']);
    }

    /*
     * Test that a large dataset query exceeding threshold triggers slow query warning
     *
     * @test
     * Validates: Requirement 4.7 - Slow query logging for large datasets
     */
    public function test_large_dataset_slow_query_triggers_warning()
    {
        Log::shouldReceive('warning')
            ->once()
            ->with(
                '[PERFORMANCE] Datatables: Slow query detected',
                \Mockery::on(function ($context) {
                    return $context['table'] === 'large_table'
                        && $context['elapsed_ms'] >= 1000;
                })
            );

        $startTime = microtime(true) - 2.5;

        $this->callPrivate('logQueryPerformance', ['large_table', $startTime]);

        $metrics = $this->datatables->getQueryMetrics();
        $this->assertGreaterThanOrEqual(1000, $metrics['large_table']['elapsed_ms']);
    }

    /*
     * Test that eager loading prevents N+1 queries on large datasets with relations
     *
     * With 10k rows and a relation, without eager loading you'd have 10,001 queries.
     * With eager loading, it's just 2 queries regardless of row count.
     *
     * @test
     * Validates: Requirement 4.1 - Eager loading for N+1 prevention on large datasets
     */
    public function test_eager_loading_prevents_n_plus_1_on_large_datasets()
    {
        $relations = ['category', 'brand', 'supplier'];

        $mockModel = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['with'])
            ->getMock();

        $mockModel->expects($this->once())
            ->method('with')
            ->with($relations)
            ->willReturnSelf();

        $data = $this->buildDataConfig('products', [
            'relations' => array_fill_keys($relations, []),
        ]);

        $this->callPrivate('applyEagerLoading', [$mockModel, $data, 'products']);
    }

    /*
     * Test that pagination result contains all required keys for large datasets
     *
     * @test
     */
    public function test_pagination_result_structure_for_large_dataset()
    {
        $this->app['request']->merge(['start' => '5000', 'length' => '50']);

        $mockModel = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['skip', 'take'])
            ->getMock();

        $mockModel->method('skip')->willReturnSelf();
        $mockModel->method('take')->willReturnSelf();

        $result = $this->callPrivate('applyPagination', [$mockModel, 50000]);

        $this->assertArrayHasKey('start', $result);
        $this->assertArrayHasKey('length', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('model', $result);
        $this->assertEquals(50000, $result['total']);
        $this->assertEquals(5000, $result['start']);
        $this->assertEquals(50, $result['length']);
    }

    /*
     * Test that column deduplication prevents duplicate id column in SELECT
     *
     * @test
     */
    public function test_column_deduplication_prevents_duplicate_id_column()
    {
        $capturedColumns = [];

        $mockModel = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['select'])
            ->getMock();

        $mockModel->expects($this->once())
            ->method('select')
            ->with($this->callback(function ($cols) use (&$capturedColumns) {
                $capturedColumns = $cols;
                return true;
            }))
            ->willReturnSelf();

        $data = $this->buildDataConfig('users', [
            'lists' => ['id', 'name', 'email'],
        ]);

        $this->callPrivate('selectRequiredColumns', [$mockModel, $data, 'users']);

        $idColumns = array_filter($capturedColumns, fn($c) => $c === 'users.id');
        $this->assertCount(1, $idColumns, "'users.id' must appear exactly once in SELECT");
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    private function callPrivate(string $method, array $args = []): mixed
    {
        $ref = new \ReflectionMethod(Datatables::class, $method);
        $ref->setAccessible(true);
        return $ref->invokeArgs($this->datatables, $args);
    }

    private function buildDataConfig(string $tableName, array $columnConfig): object
    {
        $data                                  = new \stdClass();
        $data->datatables                      = new \stdClass();
        $data->datatables->columns             = [];
        $data->datatables->columns[$tableName] = $columnConfig;
        return $data;
    }
}
