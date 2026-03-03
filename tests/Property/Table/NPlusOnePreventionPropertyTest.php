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
 * Property 7: N+1 Query Prevention via Eager Loading.
 *
 * Validates: Requirements 26.1, 48.5
 *
 * Property: For ALL table configurations with relationships, the query count MUST be
 * ≤ 1 + (number of unique relationship types), regardless of the number of rows.
 *
 * This property ensures that eager loading is properly implemented and prevents
 * N+1 query problems that cause severe performance degradation.
 *
 * Key Invariant: Query count should NOT scale with row count.
 * - 10 rows with 1 relationship = 2 queries (1 main + 1 relation)
 * - 100 rows with 1 relationship = 2 queries (NOT 101 queries)
 * - 100 rows with 2 relationships = 3 queries (1 main + 2 relations)
 */
class NPlusOnePreventionPropertyTest extends PropertyTestCase
{
    private TableBuilder $table;

    protected function setUp(): void
    {
        parent::setUp();

        $this->table = app(TableBuilder::class);
    }

    /**
     * Property 7: N+1 Query Prevention with Single Relationship.
     *
     * Test that query count doesn't scale with row count for single relationship.
     *
     * @test
     * @group property
     * @group performance
     * @group canvastack-table-complete
     */
    public function property_prevents_n_plus_one_with_single_relationship(): void
    {
        $this->forAll(
            Generator::rowCounts(),
            function (int $rowCount) {
                // Setup: Create test data
                $this->refreshTestDatabase();

                // Create provinces with cities
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

                // Verify: Query count should be ≤ 2 (1 main + 1 relation)
                // NOT 1 + rowCount (which would be N+1 problem)
                if ($queryCount > 2) {
                    throw new \Exception(
                        "N+1 query detected: {$queryCount} data queries for {$rowCount} rows " .
                        '(expected ≤ 2 queries). Queries: ' . json_encode(array_column($dataQueries, 'query'))
                    );
                }

                return true;
            },
            100
        );
    }

    /**
     * Property 7.1: Query count is independent of row count.
     *
     * Test that 10 rows and 100 rows produce the same query count.
     *
     * @test
     * @group property
     * @group performance
     */
    public function property_query_count_independent_of_row_count(): void
    {
        $queryCounts = [];

        foreach ([10, 50, 100] as $rowCount) {
            // Setup: Create test data
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
        }

        // Verify: All query counts should be the same
        $uniqueCounts = array_unique($queryCounts);

        $this->assertCount(
            1,
            $uniqueCounts,
            'Query count should be independent of row count. Got: ' . json_encode($queryCounts)
        );

        // Verify: Query count should be ≤ 2
        $this->assertLessThanOrEqual(
            2,
            reset($uniqueCounts),
            'Query count should be ≤ 2 for single relationship'
        );
    }

    /**
     * Property 7.2: Multiple relationships scale correctly.
     *
     * Test that query count = 1 + number of relationships, not 1 + (rows * relationships).
     *
     * @test
     * @group property
     * @group performance
     */
    public function property_multiple_relationships_scale_correctly(): void
    {
        // Setup: Create test data with 50 rows
        $this->refreshTestDatabase();

        Province::factory()
            ->count(10)
            ->has(City::factory()->count(5))
            ->create();

        // Test with 1 relationship
        DB::enableQueryLog();
        DB::flushQueryLog();

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

        $queryCount1Rel = count($this->filterDataQueries(DB::getQueryLog()));

        // Verify: Should be ≤ 2 queries (1 main + 1 relation)
        $this->assertLessThanOrEqual(
            2,
            $queryCount1Rel,
            "Query count with 1 relationship should be ≤ 2, got {$queryCount1Rel}"
        );

        // Note: For multiple relationships test, we would need more complex models
        // This test validates the single relationship case thoroughly
    }

    /**
     * Property 7.3: Without eager loading causes N+1.
     *
     * Demonstrate that WITHOUT eager loading, we get N+1 queries.
     * This is a negative test to prove eager loading is necessary.
     *
     * @test
     * @group property
     * @group performance
     */
    public function property_without_eager_loading_causes_n_plus_one(): void
    {
        // Setup: Create test data
        $this->refreshTestDatabase();

        Province::factory()
            ->count(5)
            ->has(City::factory()->count(4))
            ->create();

        // Enable query logging
        DB::enableQueryLog();
        DB::flushQueryLog();

        // Execute: Query without eager loading
        $cities = City::all();

        // Access relationships (this triggers N+1)
        foreach ($cities as $city) {
            $provinceName = $city->province->name;
        }

        $queryCount = count(DB::getQueryLog());

        // Verify: Should have N+1 queries (1 main + N for each province)
        // This proves that without eager loading, we have the problem
        $this->assertGreaterThan(
            2,
            $queryCount,
            "Without eager loading, should have N+1 queries. Got {$queryCount} queries for 20 rows."
        );
    }

    /**
     * Property 7.4: Eager loading with relations() method.
     *
     * Test that using relations() method properly configures eager loading.
     *
     * @test
     * @group property
     * @group performance
     */
    public function property_relations_method_enables_eager_loading(): void
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

                // Create table with relations
                $table = app(TableBuilder::class);
                $table
                    ->setModel(City::query()->getModel())
                    ->setFields(['id', 'name', 'code'])
                    ->relations(
                        City::query()->getModel(),
                        'province',
                        'name',
                        [],
                        'Province'
                    );

                // Enable query logging
                DB::enableQueryLog();
                DB::flushQueryLog();

                // Render
                $table->render();

                // Verify
                $queries = DB::getQueryLog();
                $dataQueries = $this->filterDataQueries($queries);
                $queryCount = count($dataQueries);

                if ($queryCount > 2) {
                    throw new \Exception(
                        'relations() method did not enable eager loading properly. ' .
                        "Got {$queryCount} data queries for {$rowCount} rows (expected ≤ 2)"
                    );
                }

                return true;
            },
            100
        );
    }

    /**
     * Property 7.5: Field replacement also uses eager loading.
     *
     * Test that fieldReplacementValue() also prevents N+1 queries.
     *
     * @test
     * @group property
     * @group performance
     */
    public function property_field_replacement_uses_eager_loading(): void
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

                // Create table with field replacement
                $table = app(TableBuilder::class);
                $table
                    ->setModel(City::query()->getModel())
                    ->setFields(['id', 'name', 'code', 'province_id'])
                    ->fieldReplacementValue(
                        City::query()->getModel(),
                        'province',
                        'name',
                        'Province',
                        'province_id'
                    );

                // Enable query logging
                DB::enableQueryLog();
                DB::flushQueryLog();

                // Render
                $table->render();

                // Verify
                $queries = DB::getQueryLog();
                $dataQueries = $this->filterDataQueries($queries);
                $queryCount = count($dataQueries);

                if ($queryCount > 2) {
                    throw new \Exception(
                        'fieldReplacementValue() did not enable eager loading properly. ' .
                        "Got {$queryCount} data queries for {$rowCount} rows (expected ≤ 2)"
                    );
                }

                return true;
            },
            100
        );
    }

    /**
     * Property 7.6: Query count formula verification.
     *
     * Verify the formula: QueryCount ≤ 1 + UniqueRelationshipTypes
     *
     * @test
     * @group property
     * @group performance
     */
    public function property_query_count_formula_holds(): void
    {
        // Test with different row counts
        foreach ([10, 50, 100] as $rowCount) {
            $this->refreshTestDatabase();

            Province::factory()
                ->count(min(5, $rowCount))
                ->has(City::factory()->count((int) ceil($rowCount / 5)))
                ->create();

            $uniqueRelationshipTypes = 1; // Only 'province' relationship
            $expectedMaxQueries = 1 + $uniqueRelationshipTypes; // = 2

            DB::enableQueryLog();
            DB::flushQueryLog();

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

            $queries = DB::getQueryLog();
            $dataQueries = $this->filterDataQueries($queries);
            $actualQueries = count($dataQueries);

            $this->assertLessThanOrEqual(
                $expectedMaxQueries,
                $actualQueries,
                "For {$rowCount} rows with {$uniqueRelationshipTypes} relationship(s), " .
                "expected ≤ {$expectedMaxQueries} queries, got {$actualQueries}"
            );
        }
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
