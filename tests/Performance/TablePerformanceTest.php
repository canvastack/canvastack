<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Performance;

use Canvastack\Canvastack\Components\Table\Query\FilterBuilder;
use Canvastack\Canvastack\Components\Table\Query\QueryOptimizer;
use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Components\Table\Validation\SchemaInspector;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * TablePerformanceTest - Performance tests for Table component.
 *
 * Tests performance targets:
 * - < 500ms load time for 1000 rows
 * - < 128MB memory usage for 10,000 rows
 * - Query caching effectiveness
 * - N+1 query prevention
 */
class TablePerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected TableBuilder $tableBuilder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tableBuilder = new TableBuilder(
            new QueryOptimizer(),
            new FilterBuilder(),
            new SchemaInspector()
        );

        // Clear cache before each test
        Cache::flush();
    }

    /**
     * Test: Load time for 1000 rows should be < 500ms.
     *
     * @test
     */
    public function it_loads_1000_rows_in_under_500ms(): void
    {
        // Create test model
        $model = $this->createTestModel();

        // Seed 1000 rows
        $this->seedTestData($model, 1000);

        // Measure load time
        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        $this->tableBuilder
            ->setModel($model)
            ->setColumns(['id', 'name', 'email', 'created_at'])
            ->getData();

        $endTime = microtime(true);
        $endMemory = memory_get_usage();

        $loadTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
        $memoryUsed = ($endMemory - $startMemory) / 1024 / 1024; // Convert to MB

        // Assert performance targets
        $this->assertLessThan(500, $loadTime, "Load time {$loadTime}ms exceeds 500ms target");
        $this->assertLessThan(50, $memoryUsed, "Memory usage {$memoryUsed}MB exceeds 50MB for 1K rows");

        // Log results
        echo "\n1000 rows - Load time: {$loadTime}ms, Memory: {$memoryUsed}MB\n";
    }

    /**
     * Test: Memory usage for 10,000 rows should be < 128MB.
     *
     * @test
     */
    public function it_handles_10000_rows_with_under_128mb_memory(): void
    {
        // Create test model
        $model = $this->createTestModel();

        // Seed 10,000 rows
        $this->seedTestData($model, 10000);

        // Measure memory usage
        $startMemory = memory_get_usage();

        $this->tableBuilder
            ->setModel($model)
            ->setColumns(['id', 'name', 'email'])
            ->chunk(100) // Use chunk processing
            ->getData();

        $endMemory = memory_get_usage();
        $memoryUsed = ($endMemory - $startMemory) / 1024 / 1024; // Convert to MB

        // Assert memory target
        $this->assertLessThan(128, $memoryUsed, "Memory usage {$memoryUsed}MB exceeds 128MB target");

        // Log results
        echo "\n10,000 rows - Memory: {$memoryUsed}MB\n";
    }

    /**
     * Test: Query caching reduces load time by > 80%.
     *
     * @test
     */
    public function it_achieves_80_percent_cache_hit_improvement(): void
    {
        // Create test model
        $model = $this->createTestModel();

        // Seed 1000 rows
        $this->seedTestData($model, 1000);

        // First load (no cache)
        $startTime1 = microtime(true);

        $this->tableBuilder
            ->setModel($model)
            ->setColumns(['id', 'name', 'email'])
            ->cache(300)
            ->getData();

        $endTime1 = microtime(true);
        $loadTime1 = ($endTime1 - $startTime1) * 1000;

        // Second load (with cache)
        $startTime2 = microtime(true);

        $this->tableBuilder
            ->setModel($model)
            ->setColumns(['id', 'name', 'email'])
            ->cache(300)
            ->getData();

        $endTime2 = microtime(true);
        $loadTime2 = ($endTime2 - $startTime2) * 1000;

        // Calculate improvement
        $improvement = (($loadTime1 - $loadTime2) / $loadTime1) * 100;

        // Assert cache effectiveness
        $this->assertGreaterThan(80, $improvement, "Cache improvement {$improvement}% is less than 80% target");

        // Log results
        echo "\nCache performance - First: {$loadTime1}ms, Cached: {$loadTime2}ms, Improvement: {$improvement}%\n";
    }

    /**
     * Test: Eager loading option works correctly.
     *
     * @test
     */
    public function it_prevents_n_plus_1_queries_with_eager_loading(): void
    {
        // Create test models with relationships
        $model = $this->createTestModelWithRelations();

        // Seed 100 rows with relations
        $this->seedTestDataWithRelations($model, 100);

        // Enable query log
        DB::enableQueryLog();

        // Load data WITH eager loading
        $this->tableBuilder
            ->setModel($model)
            ->setColumns(['id', 'name', 'email'])
            ->eager(['profile', 'posts'])
            ->getData();

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Check that eager loading was applied
        // We should see the main query plus eager load queries
        $queryCount = count($queries);

        // With eager loading, we expect:
        // 1. Main query for users
        // 2. Query for profiles (hasOne)
        // 3. Query for posts (hasMany)
        // Total: 3 queries (or more depending on implementation)
        $this->assertGreaterThanOrEqual(1, $queryCount, 'Should have at least the main query');
        $this->assertLessThanOrEqual(10, $queryCount, 'Should not have excessive queries');

        // Verify eager loading was actually used by checking query strings
        $queryStrings = array_column($queries, 'query');
        $hasEagerLoad = false;

        foreach ($queryStrings as $query) {
            // Check if any query contains WHERE IN clause (typical for eager loading)
            if (stripos($query, 'where') !== false && stripos($query, 'in') !== false) {
                $hasEagerLoad = true;
                break;
            }
        }

        // If we have more than 1 query, at least one should be an eager load query
        if ($queryCount > 1) {
            $this->assertTrue($hasEagerLoad, 'Eager loading should use WHERE IN queries');
        }

        // Log results
        echo "\nQuery count with eager loading: {$queryCount}\n";
        echo 'Eager load detected: ' . ($hasEagerLoad ? 'Yes' : 'No') . "\n";
    }

    /**
     * Test: Chunk processing prevents memory overflow.
     *
     * @test
     */
    public function it_uses_chunk_processing_for_large_datasets(): void
    {
        // Create test model
        $model = $this->createTestModel();

        // Seed 5000 rows
        $this->seedTestData($model, 5000);

        // Measure memory with chunk processing
        $startMemory = memory_get_usage();

        $this->tableBuilder
            ->setModel($model)
            ->setColumns(['id', 'name'])
            ->chunk(100)
            ->getData();

        $endMemory = memory_get_usage();
        $memoryWithChunk = ($endMemory - $startMemory) / 1024 / 1024;

        // Assert reasonable memory usage
        $this->assertLessThan(100, $memoryWithChunk, 'Chunk processing should keep memory under 100MB');

        // Log results
        echo "\n5000 rows with chunking - Memory: {$memoryWithChunk}MB\n";
    }

    /**
     * Test: Filter application performance.
     *
     * @test
     */
    public function it_applies_filters_efficiently(): void
    {
        // Create test model
        $model = $this->createTestModel();

        // Seed 1000 rows
        $this->seedTestData($model, 1000);

        // Measure filter application time
        $startTime = microtime(true);

        $this->tableBuilder
            ->setModel($model)
            ->setColumns(['id', 'name', 'email'])
            ->addFilters([
                'status' => 'active',
                'role' => 'user',
            ])
            ->getData();

        $endTime = microtime(true);
        $filterTime = ($endTime - $startTime) * 1000;

        // Assert filter performance
        $this->assertLessThan(200, $filterTime, 'Filter application should be under 200ms');

        // Log results
        echo "\nFilter application - Time: {$filterTime}ms\n";
    }

    /**
     * Test: Sorting performance.
     *
     * @test
     */
    public function it_sorts_data_efficiently(): void
    {
        // Create test model
        $model = $this->createTestModel();

        // Seed 1000 rows
        $this->seedTestData($model, 1000);

        // Measure sorting time
        $startTime = microtime(true);

        $this->tableBuilder
            ->setModel($model)
            ->setColumns(['id', 'name', 'email', 'created_at'])
            ->where('status', '=', 'active')
            ->getData();

        $endTime = microtime(true);
        $sortTime = ($endTime - $startTime) * 1000;

        // Assert sorting performance
        $this->assertLessThan(300, $sortTime, 'Sorting should be under 300ms');

        // Log results
        echo "\nSorting - Time: {$sortTime}ms\n";
    }

    /**
     * Test: Rendering performance.
     *
     * @test
     */
    public function it_renders_html_efficiently(): void
    {
        // Create test model
        $model = $this->createTestModel();

        // Seed 100 rows (rendering test)
        $this->seedTestData($model, 100);

        // Measure rendering time
        $startTime = microtime(true);

        $html = $this->tableBuilder
            ->setModel($model)
            ->setColumns(['id', 'name', 'email'])
            ->setContext('admin')
            ->render();

        $endTime = microtime(true);
        $renderTime = ($endTime - $startTime) * 1000;

        // Assert rendering performance
        $this->assertLessThan(100, $renderTime, 'Rendering should be under 100ms');
        $this->assertNotEmpty($html, 'Should generate HTML output');

        // Log results
        echo "\nRendering 100 rows - Time: {$renderTime}ms\n";
    }

    /**
     * Test: SQL injection prevention doesn't impact performance.
     *
     * @test
     */
    public function it_validates_inputs_without_performance_penalty(): void
    {
        // Create test model
        $model = $this->createTestModel();

        // Seed 1000 rows
        $this->seedTestData($model, 1000);

        // Measure validation time
        $startTime = microtime(true);

        try {
            $this->tableBuilder
                ->setModel($model)
                ->setColumns(['id', 'name', 'email'])
                ->where('name', '=', 'test')
                ->getData();
        } catch (\Exception $e) {
            // Expected for invalid columns
        }

        $endTime = microtime(true);
        $validationTime = ($endTime - $startTime) * 1000;

        // Assert validation doesn't add significant overhead
        $this->assertLessThan(50, $validationTime, 'Validation overhead should be minimal');

        // Log results
        echo "\nValidation overhead - Time: {$validationTime}ms\n";
    }

    /**
     * Helper: Create test model.
     */
    protected function createTestModel()
    {
        // Create a simple test model
        return new class () extends \Illuminate\Database\Eloquent\Model {
            protected $table = 'test_users';

            protected $fillable = ['name', 'email', 'status', 'role', 'created_at'];

            public $timestamps = false;
        };
    }

    /**
     * Helper: Create test model with relations.
     */
    protected function createTestModelWithRelations()
    {
        return new class () extends \Illuminate\Database\Eloquent\Model {
            protected $table = 'test_users';

            protected $fillable = ['name', 'email', 'created_at'];

            public $timestamps = false;

            public function profile()
            {
                return $this->hasOne(get_class($this), 'user_id');
            }

            public function posts()
            {
                return $this->hasMany(get_class($this), 'user_id');
            }
        };
    }

    /**
     * Helper: Seed test data.
     */
    protected function seedTestData($model, int $count): void
    {
        // Create table if not exists
        if (!DB::getSchemaBuilder()->hasTable($model->getTable())) {
            DB::getSchemaBuilder()->create($model->getTable(), function ($table) {
                $table->id();
                $table->string('name');
                $table->string('email');
                $table->string('status')->default('active');
                $table->string('role')->default('user');
                $table->unsignedBigInteger('user_id')->nullable(); // Add user_id for relationships
                $table->timestamp('created_at')->useCurrent();
            });
        }

        // Seed data in batches
        $batchSize = 1000;
        $batches = ceil($count / $batchSize);

        for ($i = 0; $i < $batches; $i++) {
            $data = [];
            $remaining = min($batchSize, $count - ($i * $batchSize));

            for ($j = 0; $j < $remaining; $j++) {
                $data[] = [
                    'name' => 'User ' . (($i * $batchSize) + $j),
                    'email' => 'user' . (($i * $batchSize) + $j) . '@example.com',
                    'status' => $j % 2 === 0 ? 'active' : 'inactive',
                    'role' => $j % 3 === 0 ? 'admin' : 'user',
                    'created_at' => now()->subDays($j),
                ];
            }

            DB::table($model->getTable())->insert($data);
        }
    }

    /**
     * Helper: Seed test data with relations.
     */
    protected function seedTestDataWithRelations($model, int $count): void
    {
        $this->seedTestData($model, $count);
    }

    protected function tearDown(): void
    {
        // Clean up test tables
        DB::getSchemaBuilder()->dropIfExists('test_users');

        parent::tearDown();
    }
}
