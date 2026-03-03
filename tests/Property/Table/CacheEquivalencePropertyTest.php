<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Property\Table;

use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Tests\Fixtures\Models\User;
use Canvastack\Canvastack\Tests\Support\PropertyTesting\PropertyTestCase;
use Illuminate\Support\Facades\Cache;

/**
 * Property 16: Cache Equivalence.
 *
 * Validates: Requirements 46.5
 *
 * Property: For ALL queries, the data returned with caching enabled MUST be
 * identical to the data returned without caching (same records, same order,
 * same values).
 *
 * This property ensures that the caching mechanism is transparent and does not
 * alter the query results in any way. Cached data must be a perfect replica of
 * the non-cached data.
 *
 * Equivalence criteria:
 * - Same number of records
 * - Same record order
 * - Same field values for each record
 * - Same data types
 */
class CacheEquivalencePropertyTest extends PropertyTestCase
{
    private TableBuilder $table;

    protected function setUp(): void
    {
        parent::setUp();

        $this->table = app(TableBuilder::class);

        // Clear all cache before each test
        Cache::flush();

        // Create test data
        $this->createTestData();
    }

    protected function tearDown(): void
    {
        // Clear cache after each test
        Cache::flush();

        parent::tearDown();
    }

    /**
     * Create test data for cache equivalence testing.
     */
    protected function createTestData(): void
    {
        // Create 50 test users with varied data
        for ($i = 1; $i <= 50; $i++) {
            User::create([
                'name' => "User {$i}",
                'email' => "user{$i}@example.com",
                'password' => bcrypt('password'),
                'created_at' => now()->subDays(rand(0, 30)),
                'updated_at' => now()->subDays(rand(0, 10)),
            ]);
        }
    }

    /**
     * Property 16: Cache Equivalence.
     *
     * Test that cached data is identical to non-cached data.
     *
     * @test
     * @group property
     * @group cache
     * @group canvastack-table-complete
     */
    public function property_cached_data_equals_non_cached_data(): void
    {
        $this->forAll(
            $this->generateRandomQueries(),
            function (array $query) {
                // Clear cache before test
                Cache::flush();

                // Execute query WITHOUT caching
                $tableWithoutCache = app(TableBuilder::class);
                $tableWithoutCache->setModel(new User())
                    ->setFields($query['fields']);

                // Add filters
                if (!empty($query['filters'])) {
                    foreach ($query['filters'] as $filter) {
                        $tableWithoutCache->where($filter['column'], $filter['operator'], $filter['value']);
                    }
                }

                // Add sorting
                if (!empty($query['orderBy'])) {
                    $tableWithoutCache->orderby($query['orderBy']['column'], $query['orderBy']['direction']);
                }

                // Add limit
                if (!empty($query['limit'])) {
                    $tableWithoutCache->displayRowsLimitOnLoad($query['limit']);
                }

                $dataWithoutCache = $tableWithoutCache->getData();

                // Execute query WITH caching
                $tableWithCache = app(TableBuilder::class);
                $tableWithCache->setModel(new User())
                    ->setFields($query['fields'])
                    ->cache(300); // 5 minutes cache

                // Add same filters
                if (!empty($query['filters'])) {
                    foreach ($query['filters'] as $filter) {
                        $tableWithCache->where($filter['column'], $filter['operator'], $filter['value']);
                    }
                }

                // Add same sorting
                if (!empty($query['orderBy'])) {
                    $tableWithCache->orderby($query['orderBy']['column'], $query['orderBy']['direction']);
                }

                // Add same limit
                if (!empty($query['limit'])) {
                    $tableWithCache->displayRowsLimitOnLoad($query['limit']);
                }

                $dataWithCache = $tableWithCache->getData();

                // Extract actual data arrays
                $recordsWithoutCache = $dataWithoutCache['data'] ?? [];
                $recordsWithCache = $dataWithCache['data'] ?? [];

                // Verify: Same number of records
                $this->assertCount(
                    count($recordsWithoutCache),
                    $recordsWithCache,
                    'Cached data should have same number of records as non-cached data'
                );

                // Verify: Data is identical (same records, same order, same values)
                $this->assertEquals(
                    $dataWithoutCache,
                    $dataWithCache,
                    'Cached data should be identical to non-cached data'
                );

                // Verify: Each record is identical
                foreach ($recordsWithoutCache as $index => $record) {
                    $this->assertEquals(
                        $record,
                        $recordsWithCache[$index],
                        "Record at index {$index} should be identical in cached and non-cached data"
                    );
                }

                return true;
            },
            100
        );
    }

    /**
     * Property 16.1: Cache equivalence with multiple executions.
     *
     * Test that cached data remains equivalent across multiple executions.
     *
     * @test
     * @group property
     * @group cache
     */
    public function property_cached_data_remains_equivalent_across_executions(): void
    {
        $this->forAll(
            $this->generateRandomQueries(),
            function (array $query) {
                // Clear cache before test
                Cache::flush();

                // Execute query WITHOUT caching
                $tableWithoutCache = app(TableBuilder::class);
                $tableWithoutCache->setModel(new User())
                    ->setFields($query['fields']);

                $this->applyQueryConfiguration($tableWithoutCache, $query);
                $dataWithoutCache = $tableWithoutCache->getData();

                // Execute query WITH caching multiple times
                $cachedResults = [];
                for ($i = 0; $i < 5; $i++) {
                    $tableWithCache = app(TableBuilder::class);
                    $tableWithCache->setModel(new User())
                        ->setFields($query['fields'])
                        ->cache(300);

                    $this->applyQueryConfiguration($tableWithCache, $query);
                    $cachedResults[] = $tableWithCache->getData();
                }

                // Verify: All cached results are identical to non-cached result
                foreach ($cachedResults as $index => $cachedData) {
                    $this->assertEquals(
                        $dataWithoutCache,
                        $cachedData,
                        "Cached data at execution {$index} should be identical to non-cached data"
                    );
                }

                return true;
            },
            100
        );
    }

    /**
     * Property 16.2: Cache equivalence with field selection.
     *
     * Test that cached data preserves field selection correctly.
     *
     * @test
     * @group property
     * @group cache
     */
    public function property_cached_data_preserves_field_selection(): void
    {
        $this->forAll(
            $this->generateQueriesWithVariedFields(),
            function (array $query) {
                // Clear cache before test
                Cache::flush();

                // Execute WITHOUT caching
                $tableWithoutCache = app(TableBuilder::class);
                $tableWithoutCache->setModel(new User())
                    ->setFields($query['fields']);

                $dataWithoutCache = $tableWithoutCache->getData();

                // Execute WITH caching
                $tableWithCache = app(TableBuilder::class);
                $tableWithCache->setModel(new User())
                    ->setFields($query['fields'])
                    ->cache(300);

                $dataWithCache = $tableWithCache->getData();

                // Extract actual data arrays
                $recordsWithoutCache = $dataWithoutCache['data'] ?? [];
                $recordsWithCache = $dataWithCache['data'] ?? [];

                // Verify: Same fields are present in both results
                if (!empty($recordsWithoutCache) && !empty($recordsWithCache)) {
                    $firstRecordWithoutCache = is_array($recordsWithoutCache[0]) ? $recordsWithoutCache[0] : (array) $recordsWithoutCache[0];
                    $firstRecordWithCache = is_array($recordsWithCache[0]) ? $recordsWithCache[0] : (array) $recordsWithCache[0];

                    $fieldsWithoutCache = array_keys($firstRecordWithoutCache);
                    $fieldsWithCache = array_keys($firstRecordWithCache);

                    $this->assertEquals(
                        $fieldsWithoutCache,
                        $fieldsWithCache,
                        'Cached data should have same fields as non-cached data'
                    );
                }

                // Verify: Data is identical
                $this->assertEquals(
                    $dataWithoutCache,
                    $dataWithCache,
                    'Cached data with field selection should be identical to non-cached data'
                );

                return true;
            },
            100
        );
    }

    /**
     * Property 16.3: Cache equivalence with filters.
     *
     * Test that cached data preserves filter results correctly.
     *
     * @test
     * @group property
     * @group cache
     */
    public function property_cached_data_preserves_filter_results(): void
    {
        $this->forAll(
            $this->generateQueriesWithFilters(),
            function (array $query) {
                // Clear cache before test
                Cache::flush();

                // Execute WITHOUT caching
                $tableWithoutCache = app(TableBuilder::class);
                $tableWithoutCache->setModel(new User())
                    ->setFields($query['fields']);

                foreach ($query['filters'] as $filter) {
                    $tableWithoutCache->where($filter['column'], $filter['operator'], $filter['value']);
                }

                $dataWithoutCache = $tableWithoutCache->getData();

                // Execute WITH caching
                $tableWithCache = app(TableBuilder::class);
                $tableWithCache->setModel(new User())
                    ->setFields($query['fields'])
                    ->cache(300);

                foreach ($query['filters'] as $filter) {
                    $tableWithCache->where($filter['column'], $filter['operator'], $filter['value']);
                }

                $dataWithCache = $tableWithCache->getData();

                // Verify: Same filtered results
                $this->assertEquals(
                    $dataWithoutCache,
                    $dataWithCache,
                    'Cached filtered data should be identical to non-cached filtered data'
                );

                // Verify: Filter was actually applied (result count should be less than or equal to total)
                $totalCount = User::count();
                $resultCount = count($dataWithCache['data'] ?? []);
                if (!empty($query['filters'])) {
                    $this->assertLessThanOrEqual(
                        $totalCount,
                        $resultCount,
                        'Filtered results should not exceed total record count'
                    );
                }

                return true;
            },
            100
        );
    }

    /**
     * Property 16.4: Cache equivalence with sorting.
     *
     * Test that cached data preserves sort order correctly.
     *
     * @test
     * @group property
     * @group cache
     */
    public function property_cached_data_preserves_sort_order(): void
    {
        $this->forAll(
            $this->generateQueriesWithSorting(),
            function (array $query) {
                // Clear cache before test
                Cache::flush();

                // Execute WITHOUT caching
                $tableWithoutCache = app(TableBuilder::class);
                $tableWithoutCache->setModel(new User())
                    ->setFields($query['fields'])
                    ->orderby($query['orderBy']['column'], $query['orderBy']['direction']);

                $dataWithoutCache = $tableWithoutCache->getData();

                // Execute WITH caching
                $tableWithCache = app(TableBuilder::class);
                $tableWithCache->setModel(new User())
                    ->setFields($query['fields'])
                    ->orderby($query['orderBy']['column'], $query['orderBy']['direction'])
                    ->cache(300);

                $dataWithCache = $tableWithCache->getData();

                // Verify: Same sort order
                $this->assertEquals(
                    $dataWithoutCache,
                    $dataWithCache,
                    'Cached sorted data should be identical to non-cached sorted data'
                );

                // Extract actual data arrays
                $recordsWithoutCache = $dataWithoutCache['data'] ?? [];
                $recordsWithCache = $dataWithCache['data'] ?? [];

                // Verify: Order is preserved (check IDs match in sequence)
                if (!empty($recordsWithoutCache) && !empty($recordsWithCache)) {
                    $idsWithoutCache = array_map(function ($record) {
                        return is_array($record) ? $record['id'] : $record->id;
                    }, $recordsWithoutCache);

                    $idsWithCache = array_map(function ($record) {
                        return is_array($record) ? $record['id'] : $record->id;
                    }, $recordsWithCache);

                    $this->assertEquals(
                        $idsWithoutCache,
                        $idsWithCache,
                        'Record order (by ID) should be identical in cached and non-cached data'
                    );
                }

                return true;
            },
            100
        );
    }

    /**
     * Property 16.5: Cache equivalence with pagination.
     *
     * Test that cached data preserves pagination correctly.
     *
     * NOTE: This test verifies cache equivalence only. The actual pagination
     * limit enforcement is tested separately in functional tests.
     *
     * @test
     * @group property
     * @group cache
     */
    public function property_cached_data_preserves_pagination(): void
    {
        $this->forAll(
            $this->generateQueriesWithPagination(),
            function (array $query) {
                // Clear cache before test
                Cache::flush();

                // Execute WITHOUT caching
                $tableWithoutCache = app(TableBuilder::class);
                $tableWithoutCache->setModel(new User())
                    ->setFields($query['fields'])
                    ->displayRowsLimitOnLoad($query['limit']);

                $dataWithoutCache = $tableWithoutCache->getData();

                // Execute WITH caching
                $tableWithCache = app(TableBuilder::class);
                $tableWithCache->setModel(new User())
                    ->setFields($query['fields'])
                    ->displayRowsLimitOnLoad($query['limit'])
                    ->cache(300);

                $dataWithCache = $tableWithCache->getData();

                // Verify: Same pagination results (cache equivalence)
                // This verifies that caching doesn't alter the data, regardless of
                // whether pagination is correctly implemented in the query builder
                $this->assertEquals(
                    $dataWithoutCache,
                    $dataWithCache,
                    'Cached paginated data should be identical to non-cached paginated data'
                );

                // Verify: Both return same number of records
                $this->assertCount(
                    count($dataWithoutCache['data'] ?? []),
                    $dataWithCache['data'] ?? [],
                    'Cached and non-cached data should have same record count'
                );

                return true;
            },
            100
        );
    }

    /**
     * Property 16.6: Cache equivalence with complex queries.
     *
     * Test that cached data preserves complex query results (filters + sorting + pagination).
     *
     * @test
     * @group property
     * @group cache
     */
    public function property_cached_data_preserves_complex_query_results(): void
    {
        $this->forAll(
            $this->generateComplexQueries(),
            function (array $query) {
                // Clear cache before test
                Cache::flush();

                // Execute WITHOUT caching
                $tableWithoutCache = app(TableBuilder::class);
                $tableWithoutCache->setModel(new User())
                    ->setFields($query['fields']);

                $this->applyQueryConfiguration($tableWithoutCache, $query);
                $dataWithoutCache = $tableWithoutCache->getData();

                // Execute WITH caching
                $tableWithCache = app(TableBuilder::class);
                $tableWithCache->setModel(new User())
                    ->setFields($query['fields'])
                    ->cache(300);

                $this->applyQueryConfiguration($tableWithCache, $query);
                $dataWithCache = $tableWithCache->getData();

                // Verify: Complex query results are identical
                $this->assertEquals(
                    $dataWithoutCache,
                    $dataWithCache,
                    'Cached complex query data should be identical to non-cached data'
                );

                // Verify: Record count matches
                $this->assertCount(
                    count($dataWithoutCache),
                    $dataWithCache,
                    'Cached and non-cached data should have same record count'
                );

                return true;
            },
            100
        );
    }

    /**
     * Property 16.7: Cache equivalence with data types.
     *
     * Test that cached data preserves data types correctly.
     *
     * @test
     * @group property
     * @group cache
     */
    public function property_cached_data_preserves_data_types(): void
    {
        $this->forAll(
            $this->generateRandomQueries(),
            function (array $query) {
                // Clear cache before test
                Cache::flush();

                // Execute WITHOUT caching
                $tableWithoutCache = app(TableBuilder::class);
                $tableWithoutCache->setModel(new User())
                    ->setFields($query['fields']);

                $dataWithoutCache = $tableWithoutCache->getData();

                // Execute WITH caching
                $tableWithCache = app(TableBuilder::class);
                $tableWithCache->setModel(new User())
                    ->setFields($query['fields'])
                    ->cache(300);

                $dataWithCache = $tableWithCache->getData();

                // Extract actual data arrays
                $recordsWithoutCache = $dataWithoutCache['data'] ?? [];
                $recordsWithCache = $dataWithCache['data'] ?? [];

                // Verify: Data types are preserved
                if (!empty($recordsWithoutCache) && !empty($recordsWithCache)) {
                    $firstRecordWithoutCache = is_array($recordsWithoutCache[0]) ? $recordsWithoutCache[0] : (array) $recordsWithoutCache[0];
                    $firstRecordWithCache = is_array($recordsWithCache[0]) ? $recordsWithCache[0] : (array) $recordsWithCache[0];

                    foreach ($query['fields'] as $field) {
                        $valueWithoutCache = $firstRecordWithoutCache[$field] ?? null;
                        $valueWithCache = $firstRecordWithCache[$field] ?? null;

                        $this->assertSame(
                            gettype($valueWithoutCache),
                            gettype($valueWithCache),
                            "Data type for field '{$field}' should be preserved in cached data"
                        );
                    }
                }

                return true;
            },
            100
        );
    }

    /**
     * Apply query configuration to table builder.
     */
    protected function applyQueryConfiguration(TableBuilder $table, array $query): void
    {
        // Add filters
        if (!empty($query['filters'])) {
            foreach ($query['filters'] as $filter) {
                $table->where($filter['column'], $filter['operator'], $filter['value']);
            }
        }

        // Add sorting
        if (!empty($query['orderBy'])) {
            $table->orderby($query['orderBy']['column'], $query['orderBy']['direction']);
        }

        // Add limit
        if (!empty($query['limit'])) {
            $table->displayRowsLimitOnLoad($query['limit']);
        }
    }

    /**
     * Generate random queries for testing.
     */
    protected function generateRandomQueries(): \Generator
    {
        $fieldOptions = [
            ['id', 'name', 'email'],
            ['id', 'name'],
            ['name', 'email'],
            ['id', 'email'],
            ['id'],
        ];

        $filterOptions = [
            [],
            [['column' => 'id', 'operator' => '>', 'value' => 10]],
            [['column' => 'id', 'operator' => '<=', 'value' => 30]],
            [['column' => 'name', 'operator' => 'like', 'value' => '%User%']],
        ];

        $orderByOptions = [
            ['column' => 'id', 'direction' => 'asc'],
            ['column' => 'id', 'direction' => 'desc'],
            ['column' => 'name', 'direction' => 'asc'],
        ];

        $limitOptions = [10, 20, 50, null];

        for ($i = 0; $i < 100; $i++) {
            yield [
                'fields' => $fieldOptions[array_rand($fieldOptions)],
                'filters' => $filterOptions[array_rand($filterOptions)],
                'orderBy' => $orderByOptions[array_rand($orderByOptions)],
                'limit' => $limitOptions[array_rand($limitOptions)],
            ];
        }
    }

    /**
     * Generate queries with varied field selections.
     */
    protected function generateQueriesWithVariedFields(): \Generator
    {
        $fieldOptions = [
            ['id'],
            ['name'],
            ['email'],
            ['id', 'name'],
            ['id', 'email'],
            ['name', 'email'],
            ['id', 'name', 'email'],
        ];

        for ($i = 0; $i < 100; $i++) {
            yield [
                'fields' => $fieldOptions[array_rand($fieldOptions)],
            ];
        }
    }

    /**
     * Generate queries with filters.
     */
    protected function generateQueriesWithFilters(): \Generator
    {
        $fieldOptions = [
            ['id', 'name', 'email'],
            ['id', 'name'],
        ];

        $filterOptions = [
            [['column' => 'id', 'operator' => '>', 'value' => 5]],
            [['column' => 'id', 'operator' => '<', 'value' => 40]],
            [['column' => 'id', 'operator' => '>=', 'value' => 10]],
            [['column' => 'id', 'operator' => '<=', 'value' => 35]],
            [['column' => 'name', 'operator' => 'like', 'value' => '%User 1%']],
            [
                ['column' => 'id', 'operator' => '>', 'value' => 10],
                ['column' => 'id', 'operator' => '<', 'value' => 30],
            ],
        ];

        for ($i = 0; $i < 100; $i++) {
            yield [
                'fields' => $fieldOptions[array_rand($fieldOptions)],
                'filters' => $filterOptions[array_rand($filterOptions)],
            ];
        }
    }

    /**
     * Generate queries with sorting.
     */
    protected function generateQueriesWithSorting(): \Generator
    {
        $fieldOptions = [
            ['id', 'name', 'email'],
            ['id', 'name'],
        ];

        $orderByOptions = [
            ['column' => 'id', 'direction' => 'asc'],
            ['column' => 'id', 'direction' => 'desc'],
            ['column' => 'name', 'direction' => 'asc'],
            ['column' => 'name', 'direction' => 'desc'],
            ['column' => 'email', 'direction' => 'asc'],
        ];

        for ($i = 0; $i < 100; $i++) {
            yield [
                'fields' => $fieldOptions[array_rand($fieldOptions)],
                'orderBy' => $orderByOptions[array_rand($orderByOptions)],
            ];
        }
    }

    /**
     * Generate queries with pagination.
     */
    protected function generateQueriesWithPagination(): \Generator
    {
        $fieldOptions = [
            ['id', 'name', 'email'],
            ['id', 'name'],
        ];

        $limitOptions = [5, 10, 15, 20, 25];

        for ($i = 0; $i < 100; $i++) {
            yield [
                'fields' => $fieldOptions[array_rand($fieldOptions)],
                'limit' => $limitOptions[array_rand($limitOptions)],
            ];
        }
    }

    /**
     * Generate complex queries (filters + sorting + pagination).
     */
    protected function generateComplexQueries(): \Generator
    {
        $fieldOptions = [
            ['id', 'name', 'email'],
            ['id', 'name'],
        ];

        $filterOptions = [
            [['column' => 'id', 'operator' => '>', 'value' => 5]],
            [['column' => 'id', 'operator' => '<', 'value' => 45]],
            [
                ['column' => 'id', 'operator' => '>', 'value' => 10],
                ['column' => 'id', 'operator' => '<', 'value' => 40],
            ],
        ];

        $orderByOptions = [
            ['column' => 'id', 'direction' => 'asc'],
            ['column' => 'id', 'direction' => 'desc'],
            ['column' => 'name', 'direction' => 'asc'],
        ];

        $limitOptions = [10, 15, 20];

        for ($i = 0; $i < 100; $i++) {
            yield [
                'fields' => $fieldOptions[array_rand($fieldOptions)],
                'filters' => $filterOptions[array_rand($filterOptions)],
                'orderBy' => $orderByOptions[array_rand($orderByOptions)],
                'limit' => $limitOptions[array_rand($limitOptions)],
            ];
        }
    }
}
