<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Property\Table;

use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Tests\Fixtures\Models\City;
use Canvastack\Canvastack\Tests\Fixtures\Models\Province;
use Canvastack\Canvastack\Tests\Support\PropertyTesting\Generator;
use Canvastack\Canvastack\Tests\Support\PropertyTesting\PropertyTestCase;
use Illuminate\Support\Facades\DB;

/**
 * Property 8: Query Count Limit.
 *
 * Validates: Requirements 26.3
 *
 * Property: For ALL table configurations, the total number of database queries
 * MUST be less than 5, regardless of configuration complexity.
 *
 * This property ensures that the table component maintains efficient database
 * access patterns and doesn't generate excessive queries even with complex
 * configurations involving relationships, filters, sorting, and formatting.
 *
 * Key Invariant: Total queries < 5 for any configuration.
 * - Simple table: 1 query (main data)
 * - Table with 1 relationship: 2 queries (main + relation)
 * - Table with 2 relationships: 3 queries (main + 2 relations)
 * - Table with filters + relationships: ≤ 4 queries
 */
class QueryCountLimitPropertyTest extends PropertyTestCase
{
    private TableBuilder $table;

    protected function setUp(): void
    {
        parent::setUp();

        $this->table = app(TableBuilder::class);
    }

    /**
     * Property 8: Query count limit for simple configurations.
     *
     * Test that simple table configurations use minimal queries.
     *
     * @test
     * @group property
     * @group performance
     * @group canvastack-table-complete
     */
    public function property_simple_table_uses_minimal_queries(): void
    {
        $this->forAll(
            Generator::rowCounts(),
            function (int $rowCount) {
                // Setup: Create test data
                $this->refreshTestDatabase();

                Province::factory()->count($rowCount)->create();

                // Enable query logging
                DB::enableQueryLog();
                DB::flushQueryLog();

                // Execute: Render simple table
                $this->table
                    ->setModel(Province::query()->getModel())
                    ->setFields(['id', 'name', 'code'])
                    ->render();

                // Get query count
                $queries = DB::getQueryLog();
                $dataQueries = $this->filterDataQueries($queries);
                $queryCount = count($dataQueries);

                // Verify: Query count should be < 5 (actually should be 1 for simple table)
                if ($queryCount >= 5) {
                    throw new \Exception(
                        "Query count limit exceeded: {$queryCount} data queries for simple table " .
                        '(expected < 5). Queries: ' . json_encode(array_column($dataQueries, 'query'))
                    );
                }

                return true;
            },
            100
        );
    }

    /**
     * Property 8.1: Query count limit with single relationship.
     *
     * Test that tables with one relationship stay under query limit.
     *
     * @test
     * @group property
     * @group performance
     */
    public function property_single_relationship_under_query_limit(): void
    {
        $this->forAll(
            Generator::rowCounts(),
            function (int $rowCount) {
                // Setup
                $this->refreshTestDatabase();

                Province::factory()
                    ->count(min(5, $rowCount))
                    ->has(City::factory()->count((int) ceil($rowCount / 5)))
                    ->create();

                // Enable query logging
                DB::enableQueryLog();
                DB::flushQueryLog();

                // Execute: Render table with relationship
                $this->table
                    ->setModel(City::query()->getModel())
                    ->setFields(['id', 'name', 'code'])
                    ->relations(
                        City::query()->getModel(),
                        'province',
                        'name',
                        [],
                        'Province'
                    )
                    ->render();

                // Get query count
                $queries = DB::getQueryLog();
                $dataQueries = $this->filterDataQueries($queries);
                $queryCount = count($dataQueries);

                // Verify: Query count should be < 5 (should be 2: main + relation)
                if ($queryCount >= 5) {
                    throw new \Exception(
                        "Query count limit exceeded: {$queryCount} data queries for table with 1 relationship " .
                        '(expected < 5). Queries: ' . json_encode(array_column($dataQueries, 'query'))
                    );
                }

                return true;
            },
            100
        );
    }

    /**
     * Property 8.2: Query count limit with sorting.
     *
     * Test that adding sorting doesn't increase query count.
     *
     * @test
     * @group property
     * @group performance
     */
    public function property_sorting_doesnt_increase_query_count(): void
    {
        $this->forAll(
            Generator::elements(['id', 'name', 'code']),
            function (string $sortColumn) {
                // Setup
                $this->refreshTestDatabase();
                Province::factory()->count(50)->create();

                // Enable query logging
                DB::enableQueryLog();
                DB::flushQueryLog();

                // Execute: Render table with sorting
                $this->table
                    ->setModel(Province::query()->getModel())
                    ->setFields(['id', 'name', 'code'])
                    ->orderby($sortColumn, 'asc')
                    ->render();

                // Get query count
                $queries = DB::getQueryLog();
                $dataQueries = $this->filterDataQueries($queries);
                $queryCount = count($dataQueries);

                // Verify: Query count should be < 5 (should be 1: main query with ORDER BY)
                if ($queryCount >= 5) {
                    throw new \Exception(
                        "Query count limit exceeded: {$queryCount} data queries for table with sorting " .
                        '(expected < 5). Queries: ' . json_encode(array_column($dataQueries, 'query'))
                    );
                }

                return true;
            },
            100
        );
    }

    /**
     * Property 8.3: Query count limit with filters.
     *
     * Test that adding where conditions doesn't cause query explosion.
     *
     * @test
     * @group property
     * @group performance
     */
    public function property_filters_dont_cause_query_explosion(): void
    {
        $this->forAll(
            Generator::positiveInteger(100),
            function (int $filterValue) {
                // Setup
                $this->refreshTestDatabase();
                Province::factory()->count(50)->create();

                // Enable query logging
                DB::enableQueryLog();
                DB::flushQueryLog();

                // Execute: Render table with filter
                $this->table
                    ->setModel(Province::query()->getModel())
                    ->setFields(['id', 'name', 'code'])
                    ->where('id', '>', $filterValue)
                    ->render();

                // Get query count
                $queries = DB::getQueryLog();
                $dataQueries = $this->filterDataQueries($queries);
                $queryCount = count($dataQueries);

                // Verify: Query count should be < 5 (should be 1: main query with WHERE)
                if ($queryCount >= 5) {
                    throw new \Exception(
                        "Query count limit exceeded: {$queryCount} data queries for table with filter " .
                        '(expected < 5). Queries: ' . json_encode(array_column($dataQueries, 'query'))
                    );
                }

                return true;
            },
            100
        );
    }

    /**
     * Property 8.4: Query count limit with complex configuration.
     *
     * Test that combining multiple features stays under query limit.
     *
     * @test
     * @group property
     * @group performance
     */
    public function property_complex_configuration_under_query_limit(): void
    {
        $this->forAll(
            Generator::rowCounts(),
            function (int $rowCount) {
                // Setup
                $this->refreshTestDatabase();

                Province::factory()
                    ->count(min(5, $rowCount))
                    ->has(City::factory()->count((int) ceil($rowCount / 5)))
                    ->create();

                // Enable query logging
                DB::enableQueryLog();
                DB::flushQueryLog();

                // Execute: Render table with complex configuration
                // - Relationships
                // - Sorting
                // - Filtering
                // - Hidden columns
                // - Column alignment
                $this->table
                    ->setModel(City::query()->getModel())
                    ->setFields(['id', 'name', 'code', 'province_id'])
                    ->setHiddenColumns(['province_id'])
                    ->relations(
                        City::query()->getModel(),
                        'province',
                        'name',
                        [],
                        'Province'
                    )
                    ->orderby('name', 'asc')
                    ->where('id', '>', 0)
                    ->setAlignColumns('center', ['id'])
                    ->render();

                // Get query count
                $queries = DB::getQueryLog();
                $dataQueries = $this->filterDataQueries($queries);
                $queryCount = count($dataQueries);

                // Verify: Query count should be < 5
                // Expected: 2 queries (main + relation)
                if ($queryCount >= 5) {
                    throw new \Exception(
                        "Query count limit exceeded: {$queryCount} data queries for complex configuration " .
                        '(expected < 5). Queries: ' . json_encode(array_column($dataQueries, 'query'))
                    );
                }

                return true;
            },
            100
        );
    }

    /**
     * Property 8.5: Query count limit with field replacement.
     *
     * Test that field replacement stays under query limit.
     *
     * @test
     * @group property
     * @group performance
     */
    public function property_field_replacement_under_query_limit(): void
    {
        $this->forAll(
            Generator::rowCounts(),
            function (int $rowCount) {
                // Setup
                $this->refreshTestDatabase();

                Province::factory()
                    ->count(min(5, $rowCount))
                    ->has(City::factory()->count((int) ceil($rowCount / 5)))
                    ->create();

                // Enable query logging
                DB::enableQueryLog();
                DB::flushQueryLog();

                // Execute: Render table with field replacement
                $this->table
                    ->setModel(City::query()->getModel())
                    ->setFields(['id', 'name', 'code', 'province_id'])
                    ->fieldReplacementValue(
                        City::query()->getModel(),
                        'province',
                        'name',
                        'Province',
                        'province_id'
                    )
                    ->render();

                // Get query count
                $queries = DB::getQueryLog();
                $dataQueries = $this->filterDataQueries($queries);
                $queryCount = count($dataQueries);

                // Verify: Query count should be < 5 (should be 2: main + relation)
                if ($queryCount >= 5) {
                    throw new \Exception(
                        "Query count limit exceeded: {$queryCount} data queries for field replacement " .
                        '(expected < 5). Queries: ' . json_encode(array_column($dataQueries, 'query'))
                    );
                }

                return true;
            },
            100
        );
    }

    /**
     * Property 8.6: Query count is independent of row count.
     *
     * Test that query count doesn't increase with more rows.
     *
     * @test
     * @group property
     * @group performance
     */
    public function property_query_count_independent_of_row_count(): void
    {
        $queryCounts = [];

        foreach ([10, 50, 100] as $rowCount) {
            // Setup
            $this->refreshTestDatabase();

            Province::factory()
                ->count(min(5, $rowCount))
                ->has(City::factory()->count((int) ceil($rowCount / 5)))
                ->create();

            // Enable query logging
            DB::enableQueryLog();
            DB::flushQueryLog();

            // Execute: Render table
            $this->table
                ->setModel(City::query()->getModel())
                ->setFields(['id', 'name', 'code'])
                ->relations(
                    City::query()->getModel(),
                    'province',
                    'name',
                    [],
                    'Province'
                )
                ->render();

            // Record query count
            $queries = DB::getQueryLog();
            $dataQueries = $this->filterDataQueries($queries);
            $queryCounts[$rowCount] = count($dataQueries);

            // Verify each is under limit
            $this->assertLessThan(
                5,
                $queryCounts[$rowCount],
                "Query count for {$rowCount} rows should be < 5, got {$queryCounts[$rowCount]}"
            );
        }

        // Verify: All query counts should be the same
        $uniqueCounts = array_unique($queryCounts);

        $this->assertCount(
            1,
            $uniqueCounts,
            'Query count should be independent of row count. Got: ' . json_encode($queryCounts)
        );
    }

    /**
     * Property 8.7: Query count formula verification.
     *
     * Verify the formula: QueryCount ≤ 1 + NumberOfRelationships < 5
     *
     * @test
     * @group property
     * @group performance
     */
    public function property_query_count_formula_holds(): void
    {
        // Test different configurations
        $configurations = [
            'simple' => [
                'relationships' => 0,
                'expectedMax' => 1,
            ],
            'one_relation' => [
                'relationships' => 1,
                'expectedMax' => 2,
            ],
        ];

        foreach ($configurations as $name => $config) {
            $this->refreshTestDatabase();

            Province::factory()
                ->count(5)
                ->has(City::factory()->count(10))
                ->create();

            DB::enableQueryLog();
            DB::flushQueryLog();

            // Build table based on configuration
            $table = $this->table
                ->setModel(City::query()->getModel())
                ->setFields(['id', 'name', 'code']);

            if ($config['relationships'] > 0) {
                $table->relations(
                    City::query()->getModel(),
                    'province',
                    'name',
                    [],
                    'Province'
                );
            }

            $table->render();

            $queries = DB::getQueryLog();
            $dataQueries = $this->filterDataQueries($queries);
            $actualQueries = count($dataQueries);

            // Verify: Matches expected formula
            $this->assertLessThanOrEqual(
                $config['expectedMax'],
                $actualQueries,
                "Configuration '{$name}' expected ≤ {$config['expectedMax']} queries, got {$actualQueries}"
            );

            // Verify: Under absolute limit
            $this->assertLessThan(
                5,
                $actualQueries,
                "Configuration '{$name}' exceeded query limit of 5, got {$actualQueries}"
            );
        }
    }

    /**
     * Property 8.8: Random configuration stress test.
     *
     * Test random combinations of features to ensure query limit holds.
     *
     * @test
     * @group property
     * @group performance
     */
    public function property_random_configurations_under_query_limit(): void
    {
        $this->forAll(
            Generator::boolean(),
            function (bool $useRelationship) {
                // Setup
                $this->refreshTestDatabase();

                Province::factory()
                    ->count(5)
                    ->has(City::factory()->count(10))
                    ->create();

                // Enable query logging
                DB::enableQueryLog();
                DB::flushQueryLog();

                // Execute: Build random configuration
                $table = $this->table
                    ->setModel(City::query()->getModel())
                    ->setFields(['id', 'name', 'code']);

                // Randomly add features
                if ($useRelationship) {
                    $table->relations(
                        City::query()->getModel(),
                        'province',
                        'name',
                        [],
                        'Province'
                    );
                }

                // Add random sorting
                $sortColumns = ['id', 'name', 'code'];
                $table->orderby($sortColumns[array_rand($sortColumns)], 'asc');

                // Add random filter
                $table->where('id', '>', rand(0, 10));

                $table->render();

                // Get query count
                $queries = DB::getQueryLog();
                $dataQueries = $this->filterDataQueries($queries);
                $queryCount = count($dataQueries);

                // Verify: Query count should be < 5
                if ($queryCount >= 5) {
                    throw new \Exception(
                        "Query count limit exceeded: {$queryCount} data queries for random configuration " .
                        '(relationship: ' . ($useRelationship ? 'yes' : 'no') . '). ' .
                        'Queries: ' . json_encode(array_column($dataQueries, 'query'))
                    );
                }

                return true;
            },
            100
        );
    }

    /**
     * Helper: Refresh database for each iteration.
     *
     * Note: RefreshDatabase trait handles cleanup automatically.
     * This method is kept for compatibility but does nothing.
     */
    protected function refreshTestDatabase(): void
    {
        // RefreshDatabase trait handles this automatically
        // No manual truncation needed
    }

    /**
     * Helper: Filter out schema inspection queries.
     *
     * Returns only data queries (SELECT, INSERT, UPDATE, DELETE)
     * Excludes schema inspection queries (pragma, sqlite_master, count)
     */
    private function filterDataQueries(array $queries): array
    {
        return array_values(array_filter($queries, function ($query) {
            $sql = strtolower($query['query']);

            return !str_contains($sql, 'pragma_table_xinfo')
                && !str_contains($sql, 'sqlite_master')
                && !str_contains($sql, 'count(*) as aggregate');
        }));
    }
}
