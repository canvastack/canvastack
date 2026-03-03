<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Table;

use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Test chart data aggregation in TableBuilder.
 *
 * Tests Phase 8: P2 Features - Task 2.5 (Chart Data Aggregation)
 * Verifies that charts show aggregated data correctly.
 */
class ChartDataAggregationTest extends TestCase
{
    protected TableBuilder $table;

    protected function setUp(): void
    {
        parent::setUp();

        $this->table = $this->app->make(TableBuilder::class);
        $this->table->setContext('admin');

        // Create test table
        $this->createTestTable();
    }

    protected function tearDown(): void
    {
        // Drop test table
        $capsule = Capsule::connection();
        $capsule->getSchemaBuilder()->dropIfExists('test_orders');

        parent::tearDown();
    }

    /**
     * Test that chart data is aggregated correctly with SUM.
     */
    public function test_chart_data_aggregates_with_sum(): void
    {
        // Insert test data
        $this->insertTestData([
            ['total' => 100, 'month' => '2024-01'],
            ['total' => 200, 'month' => '2024-01'],
            ['total' => 150, 'month' => '2024-02'],
            ['total' => 250, 'month' => '2024-02'],
        ]);

        $model = $this->createTestModel();
        $this->table->setModel($model);

        // Use reflection to call protected buildChartData method
        $reflection = new \ReflectionClass($this->table);
        $method = $reflection->getMethod('buildChartData');
        $method->setAccessible(true);

        $data = $method->invoke($this->table, ['total'], 'sum', 'month');

        // Verify structure
        $this->assertArrayHasKey('series', $data);
        $this->assertArrayHasKey('categories', $data);
        $this->assertArrayHasKey('values', $data);
        $this->assertArrayHasKey('labels', $data);

        // Verify categories
        $this->assertEquals(['2024-01', '2024-02'], $data['categories']);
        $this->assertEquals(['2024-01', '2024-02'], $data['labels']);

        // Verify series data (SUM aggregation)
        $this->assertCount(1, $data['series']);
        $this->assertEquals('Total', $data['series'][0]['name']);
        $this->assertEquals([300, 400], $data['series'][0]['data']); // 100+200=300, 150+250=400

        // Verify values
        $this->assertEquals([300, 400], $data['values']);
    }

    /**
     * Test that chart data is aggregated correctly with AVG.
     */
    public function test_chart_data_aggregates_with_avg(): void
    {
        // Insert test data
        $this->insertTestData([
            ['total' => 100, 'month' => '2024-01'],
            ['total' => 200, 'month' => '2024-01'],
            ['total' => 150, 'month' => '2024-02'],
            ['total' => 250, 'month' => '2024-02'],
        ]);

        $model = $this->createTestModel();
        $this->table->setModel($model);

        // Use reflection to call protected buildChartData method
        $reflection = new \ReflectionClass($this->table);
        $method = $reflection->getMethod('buildChartData');
        $method->setAccessible(true);

        $data = $method->invoke($this->table, ['total'], 'avg', 'month');

        // Verify series data (AVG aggregation)
        $this->assertCount(1, $data['series']);
        $this->assertEquals('Total', $data['series'][0]['name']);
        
        // AVG: (100+200)/2=150, (150+250)/2=200
        $this->assertEquals([150, 200], $data['series'][0]['data']);
    }

    /**
     * Test that chart data is aggregated correctly with COUNT.
     */
    public function test_chart_data_aggregates_with_count(): void
    {
        // Insert test data
        $this->insertTestData([
            ['total' => 100, 'month' => '2024-01'],
            ['total' => 200, 'month' => '2024-01'],
            ['total' => 150, 'month' => '2024-02'],
        ]);

        $model = $this->createTestModel();
        $this->table->setModel($model);

        // Use reflection to call protected buildChartData method
        $reflection = new \ReflectionClass($this->table);
        $method = $reflection->getMethod('buildChartData');
        $method->setAccessible(true);

        $data = $method->invoke($this->table, ['total'], 'count', 'month');

        // Verify series data (COUNT aggregation)
        $this->assertCount(1, $data['series']);
        $this->assertEquals('Total', $data['series'][0]['name']);
        
        // COUNT: 2 records in 2024-01, 1 record in 2024-02
        $this->assertEquals([2, 1], $data['series'][0]['data']);
    }

    /**
     * Test that chart data is aggregated correctly with MIN.
     */
    public function test_chart_data_aggregates_with_min(): void
    {
        // Insert test data
        $this->insertTestData([
            ['total' => 100, 'month' => '2024-01'],
            ['total' => 200, 'month' => '2024-01'],
            ['total' => 150, 'month' => '2024-02'],
            ['total' => 250, 'month' => '2024-02'],
        ]);

        $model = $this->createTestModel();
        $this->table->setModel($model);

        // Use reflection to call protected buildChartData method
        $reflection = new \ReflectionClass($this->table);
        $method = $reflection->getMethod('buildChartData');
        $method->setAccessible(true);

        $data = $method->invoke($this->table, ['total'], 'min', 'month');

        // Verify series data (MIN aggregation)
        $this->assertCount(1, $data['series']);
        $this->assertEquals('Total', $data['series'][0]['name']);
        
        // MIN: min(100,200)=100, min(150,250)=150
        $this->assertEquals([100, 150], $data['series'][0]['data']);
    }

    /**
     * Test that chart data is aggregated correctly with MAX.
     */
    public function test_chart_data_aggregates_with_max(): void
    {
        // Insert test data
        $this->insertTestData([
            ['total' => 100, 'month' => '2024-01'],
            ['total' => 200, 'month' => '2024-01'],
            ['total' => 150, 'month' => '2024-02'],
            ['total' => 250, 'month' => '2024-02'],
        ]);

        $model = $this->createTestModel();
        $this->table->setModel($model);

        // Use reflection to call protected buildChartData method
        $reflection = new \ReflectionClass($this->table);
        $method = $reflection->getMethod('buildChartData');
        $method->setAccessible(true);

        $data = $method->invoke($this->table, ['total'], 'max', 'month');

        // Verify series data (MAX aggregation)
        $this->assertCount(1, $data['series']);
        $this->assertEquals('Total', $data['series'][0]['name']);
        
        // MAX: max(100,200)=200, max(150,250)=250
        $this->assertEquals([200, 250], $data['series'][0]['data']);
    }

    /**
     * Test that chart data handles multiple series correctly.
     */
    public function test_chart_data_handles_multiple_series(): void
    {
        // Insert test data with multiple fields
        $this->insertTestData([
            ['total' => 100, 'quantity' => 10, 'month' => '2024-01'],
            ['total' => 200, 'quantity' => 20, 'month' => '2024-01'],
            ['total' => 150, 'quantity' => 15, 'month' => '2024-02'],
        ]);

        $model = $this->createTestModel();
        $this->table->setModel($model);

        // Use reflection to call protected buildChartData method
        $reflection = new \ReflectionClass($this->table);
        $method = $reflection->getMethod('buildChartData');
        $method->setAccessible(true);

        $data = $method->invoke($this->table, ['total', 'quantity'], 'sum', 'month');

        // Verify multiple series
        $this->assertCount(2, $data['series']);
        
        // First series (total)
        $this->assertEquals('Total', $data['series'][0]['name']);
        $this->assertEquals([300, 150], $data['series'][0]['data']);
        
        // Second series (quantity)
        $this->assertEquals('Quantity', $data['series'][1]['name']);
        $this->assertEquals([30, 15], $data['series'][1]['data']);
    }

    /**
     * Test that chart data is ordered by groupBy field.
     */
    public function test_chart_data_is_ordered_by_group_field(): void
    {
        // Insert test data in random order
        $this->insertTestData([
            ['total' => 150, 'month' => '2024-03'],
            ['total' => 100, 'month' => '2024-01'],
            ['total' => 200, 'month' => '2024-02'],
        ]);

        $model = $this->createTestModel();
        $this->table->setModel($model);

        // Use reflection to call protected buildChartData method
        $reflection = new \ReflectionClass($this->table);
        $method = $reflection->getMethod('buildChartData');
        $method->setAccessible(true);

        $data = $method->invoke($this->table, ['total'], 'sum', 'month');

        // Verify data is ordered by month
        $this->assertEquals(['2024-01', '2024-02', '2024-03'], $data['categories']);
        $this->assertEquals([100, 200, 150], $data['series'][0]['data']);
    }

    /**
     * Test that chart data handles empty result set.
     */
    public function test_chart_data_handles_empty_result(): void
    {
        // No data inserted

        $model = $this->createTestModel();
        $this->table->setModel($model);

        // Use reflection to call protected buildChartData method
        $reflection = new \ReflectionClass($this->table);
        $method = $reflection->getMethod('buildChartData');
        $method->setAccessible(true);

        $data = $method->invoke($this->table, ['total'], 'sum', 'month');

        // Verify empty arrays
        $this->assertEmpty($data['categories']);
        $this->assertEmpty($data['labels']);
        $this->assertCount(1, $data['series']);
        $this->assertEmpty($data['series'][0]['data']);
        $this->assertEmpty($data['values']);
    }

    /**
     * Test that chart data formats field names correctly.
     */
    public function test_chart_data_formats_field_names(): void
    {
        // Insert test data
        $this->insertTestData([
            ['total' => 100, 'month' => '2024-01'],
        ]);

        $model = $this->createTestModel();
        $this->table->setModel($model);

        // Use reflection to call protected buildChartData method
        $reflection = new \ReflectionClass($this->table);
        $method = $reflection->getMethod('buildChartData');
        $method->setAccessible(true);

        // Test with underscore field name
        $data = $method->invoke($this->table, ['total'], 'sum', 'month');

        // Verify field name is formatted (underscores replaced with spaces, capitalized)
        $this->assertEquals('Total', $data['series'][0]['name']);
    }

    /**
     * Test that chart data handles single data point.
     */
    public function test_chart_data_handles_single_data_point(): void
    {
        // Insert single data point
        $this->insertTestData([
            ['total' => 100, 'month' => '2024-01'],
        ]);

        $model = $this->createTestModel();
        $this->table->setModel($model);

        // Use reflection to call protected buildChartData method
        $reflection = new \ReflectionClass($this->table);
        $method = $reflection->getMethod('buildChartData');
        $method->setAccessible(true);

        $data = $method->invoke($this->table, ['total'], 'sum', 'month');

        // Verify single data point
        $this->assertCount(1, $data['categories']);
        $this->assertEquals(['2024-01'], $data['categories']);
        $this->assertEquals([100], $data['series'][0]['data']);
    }

    /**
     * Test that chart data handles large numbers correctly.
     */
    public function test_chart_data_handles_large_numbers(): void
    {
        // Insert test data with large numbers
        $this->insertTestData([
            ['total' => 1000000, 'month' => '2024-01'],
            ['total' => 2000000, 'month' => '2024-01'],
        ]);

        $model = $this->createTestModel();
        $this->table->setModel($model);

        // Use reflection to call protected buildChartData method
        $reflection = new \ReflectionClass($this->table);
        $method = $reflection->getMethod('buildChartData');
        $method->setAccessible(true);

        $data = $method->invoke($this->table, ['total'], 'sum', 'month');

        // Verify large number aggregation
        $this->assertEquals([3000000], $data['series'][0]['data']);
    }

    /**
     * Test that chart data handles decimal values correctly.
     */
    public function test_chart_data_handles_decimal_values(): void
    {
        // Insert test data with decimal values
        $this->insertTestData([
            ['total' => 100.50, 'month' => '2024-01'],
            ['total' => 200.75, 'month' => '2024-01'],
        ]);

        $model = $this->createTestModel();
        $this->table->setModel($model);

        // Use reflection to call protected buildChartData method
        $reflection = new \ReflectionClass($this->table);
        $method = $reflection->getMethod('buildChartData');
        $method->setAccessible(true);

        $data = $method->invoke($this->table, ['total'], 'sum', 'month');

        // Verify decimal aggregation (with floating point tolerance)
        $this->assertEqualsWithDelta(301.25, $data['series'][0]['data'][0], 0.01);
    }

    /**
     * Create test table.
     */
    protected function createTestTable(): void
    {
        $capsule = Capsule::connection();
        $schema = $capsule->getSchemaBuilder();

        $schema->dropIfExists('test_orders');

        $schema->create('test_orders', function ($table) {
            $table->id();
            $table->decimal('total', 10, 2)->default(0);
            $table->integer('quantity')->default(0);
            $table->string('month', 10);
            $table->timestamps();
        });
    }

    /**
     * Insert test data.
     */
    protected function insertTestData(array $records): void
    {
        $capsule = Capsule::connection();

        foreach ($records as $record) {
            $capsule->table('test_orders')->insert(array_merge($record, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    /**
     * Create test model.
     */
    protected function createTestModel(): Model
    {
        return new class extends Model {
            protected $table = 'test_orders';
            protected $fillable = ['total', 'quantity', 'month', 'created_at', 'updated_at'];
            public $timestamps = true;
        };
    }
}
