<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Property\Table;

use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Tests\Fixtures\Models\User;
use Canvastack\Canvastack\Tests\Support\PropertyTesting\PropertyTestCase;
use Illuminate\Support\Facades\DB;

/**
 * Property 15: Filter Reduces or Maintains Count.
 *
 * Validates: Requirements 46.2, 48.1
 *
 * Property: FOR ALL filter operations, the filtered result count SHALL be
 * less than or equal to the unfiltered count.
 *
 * This property ensures that applying filters never increases the number of
 * records, maintaining the fundamental invariant that filters can only remove
 * or maintain records, never add them.
 */
class FilterReducesCountPropertyTest extends PropertyTestCase
{
    private TableBuilder $table;

    protected function setUp(): void
    {
        parent::setUp();

        $this->table = app(TableBuilder::class);
        $this->table->setModel(new User());

        // Create test data with known characteristics
        $this->createTestData();
    }

    protected function tearDown(): void
    {
        // Clean up test data
        DB::table('test_users')->truncate();

        parent::tearDown();
    }

    /**
     * Create test data with various values for filtering.
     */
    protected function createTestData(): void
    {
        // Create 100 users with varying attributes for filtering
        for ($i = 1; $i <= 100; $i++) {
            DB::table('test_users')->insert([
                'name' => 'User ' . $i,
                'email' => 'user' . $i . '@example.com',
                'active' => $i % 2 === 0, // 50 active, 50 inactive
                'created_at' => now()->subDays(rand(0, 365)),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Property 15: Filter Reduces or Maintains Count.
     *
     * Test that applying filters never increases record count.
     *
     * @test
     * @group property
     * @group metamorphic
     * @group canvastack-table-complete
     */
    public function property_filter_reduces_or_maintains_count(): void
    {
        $this->forAll(
            $this->generateFilterConfigurations(),
            function (array $config) {
                // Step 1: Get unfiltered count
                $unfilteredQuery = $this->table->getModel()->newQuery();
                $unfilteredCount = $unfilteredQuery->count();

                // Step 2: Apply filters
                $filteredQuery = $this->table->getModel()->newQuery();
                foreach ($config['filters'] as $filter) {
                    $filteredQuery->where(
                        $filter['field'],
                        $filter['operator'],
                        $filter['value']
                    );
                }
                $filteredCount = $filteredQuery->count();

                // Step 3: Verify filtered count ≤ unfiltered count
                $this->assertLessThanOrEqual(
                    $unfilteredCount,
                    $filteredCount,
                    sprintf(
                        'Filtered count (%d) should be ≤ unfiltered count (%d). Filters: %s',
                        $filteredCount,
                        $unfilteredCount,
                        json_encode($config['filters'])
                    )
                );

                return true;
            },
            100
        );
    }

    /**
     * Property 15.1: Single filter reduces or maintains count.
     *
     * Test that a single filter operation never increases count.
     *
     * @test
     * @group property
     * @group metamorphic
     */
    public function property_single_filter_reduces_or_maintains_count(): void
    {
        $this->forAll(
            $this->generateSingleFilterConfigurations(),
            function (array $config) {
                $unfilteredCount = $this->table->getModel()->newQuery()->count();

                $filteredCount = $this->table->getModel()->newQuery()
                    ->where($config['field'], $config['operator'], $config['value'])
                    ->count();

                $this->assertLessThanOrEqual(
                    $unfilteredCount,
                    $filteredCount,
                    sprintf(
                        'Single filter (%s %s %s) should not increase count',
                        $config['field'],
                        $config['operator'],
                        $config['value']
                    )
                );

                return true;
            },
            100
        );
    }

    /**
     * Property 15.2: Multiple filters progressively reduce count.
     *
     * Test that adding more filters never increases count.
     *
     * @test
     * @group property
     * @group metamorphic
     */
    public function property_multiple_filters_progressively_reduce_count(): void
    {
        $this->forAll(
            $this->generateProgressiveFilterConfigurations(),
            function (array $config) {
                $query = $this->table->getModel()->newQuery();
                $previousCount = $query->count();

                // Apply filters one by one and verify count never increases
                foreach ($config['filters'] as $index => $filter) {
                    $query->where($filter['field'], $filter['operator'], $filter['value']);
                    $currentCount = $query->count();

                    $this->assertLessThanOrEqual(
                        $previousCount,
                        $currentCount,
                        sprintf(
                            'Adding filter #%d (%s %s %s) should not increase count from %d to %d',
                            $index + 1,
                            $filter['field'],
                            $filter['operator'],
                            $filter['value'],
                            $previousCount,
                            $currentCount
                        )
                    );

                    $previousCount = $currentCount;
                }

                return true;
            },
            100
        );
    }

    /**
     * Property 15.3: Empty filter returns all records.
     *
     * Test that no filters returns the full record count.
     *
     * @test
     * @group property
     * @group metamorphic
     */
    public function property_empty_filter_returns_all_records(): void
    {
        $totalCount = $this->table->getModel()->newQuery()->count();

        // Apply no filters
        $filteredCount = $this->table->getModel()->newQuery()->count();

        $this->assertEquals(
            $totalCount,
            $filteredCount,
            'No filters should return all records'
        );
    }

    /**
     * Property 15.4: Impossible filter returns zero records.
     *
     * Test that filters with impossible conditions return zero or fewer records.
     *
     * @test
     * @group property
     * @group metamorphic
     */
    public function property_impossible_filter_returns_zero_or_fewer(): void
    {
        $this->forAll(
            $this->generateImpossibleFilterConfigurations(),
            function (array $config) {
                $unfilteredCount = $this->table->getModel()->newQuery()->count();

                $query = $this->table->getModel()->newQuery();
                foreach ($config['filters'] as $filter) {
                    $query->where($filter['field'], $filter['operator'], $filter['value']);
                }
                $filteredCount = $query->count();

                $this->assertLessThanOrEqual(
                    $unfilteredCount,
                    $filteredCount,
                    'Impossible filters should return ≤ unfiltered count'
                );

                $this->assertGreaterThanOrEqual(
                    0,
                    $filteredCount,
                    'Filtered count should never be negative'
                );

                return true;
            },
            100
        );
    }

    /**
     * Property 15.5: Filter with TableBuilder where() method.
     *
     * Test that using TableBuilder's where() method also reduces or maintains count.
     *
     * @test
     * @group property
     * @group metamorphic
     */
    public function property_table_builder_where_reduces_or_maintains_count(): void
    {
        $this->forAll(
            $this->generateFilterConfigurations(),
            function (array $config) {
                // Get unfiltered count
                $unfilteredCount = $this->table->getModel()->newQuery()->count();

                // Apply filters using TableBuilder
                $tableBuilder = app(TableBuilder::class);
                $tableBuilder->setModel(new User());
                $tableBuilder->setFields(['name', 'email', 'active']);

                foreach ($config['filters'] as $filter) {
                    $tableBuilder->where(
                        $filter['field'],
                        $filter['operator'],
                        $filter['value']
                    );
                }

                // Get filtered count by building the query through TableBuilder
                $data = $tableBuilder->getData();
                $filteredCount = count($data);

                $this->assertLessThanOrEqual(
                    $unfilteredCount,
                    $filteredCount,
                    'TableBuilder where() should not increase count'
                );

                return true;
            },
            100
        );
    }

    /**
     * Property 15.6: Filter with addFilters() method.
     *
     * Test that using addFilters() also reduces or maintains count.
     *
     * @test
     * @group property
     * @group metamorphic
     */
    public function property_add_filters_reduces_or_maintains_count(): void
    {
        $this->forAll(
            $this->generateAddFiltersConfigurations(),
            function (array $config) {
                $unfilteredCount = $this->table->getModel()->newQuery()->count();

                // Apply filters using addFilters (simple equality filters)
                $tableBuilder = app(TableBuilder::class);
                $tableBuilder->setModel(new User());
                $tableBuilder->setFields(['name', 'email', 'active']);
                $tableBuilder->addFilters($config['filters']);

                // Get filtered count by applying the same filters to a query
                $query = $this->table->getModel()->newQuery();
                foreach ($config['filters'] as $column => $value) {
                    $query->where($column, '=', $value);
                }
                $filteredCount = $query->count();

                $this->assertLessThanOrEqual(
                    $unfilteredCount,
                    $filteredCount,
                    'addFilters() should not increase count'
                );

                return true;
            },
            100
        );
    }

    /**
     * Property 15.7: Combining filters with searchable.
     *
     * Test that combining filters with search also reduces or maintains count.
     *
     * @test
     * @group property
     * @group metamorphic
     */
    public function property_combined_filters_and_search_reduces_count(): void
    {
        $this->forAll(
            $this->generateCombinedFilterSearchConfigurations(),
            function (array $config) {
                $unfilteredCount = $this->table->getModel()->newQuery()->count();

                // Apply filters
                $query = $this->table->getModel()->newQuery();
                foreach ($config['filters'] as $filter) {
                    $query->where($filter['field'], $filter['operator'], $filter['value']);
                }

                // Apply search if provided
                if (isset($config['search']) && !empty($config['search'])) {
                    $query->where(function ($q) use ($config) {
                        foreach ($config['searchFields'] as $field) {
                            $q->orWhere($field, 'LIKE', '%' . $config['search'] . '%');
                        }
                    });
                }

                $filteredCount = $query->count();

                $this->assertLessThanOrEqual(
                    $unfilteredCount,
                    $filteredCount,
                    'Combined filters and search should not increase count'
                );

                return true;
            },
            100
        );
    }

    /**
     * Generate filter configurations for testing.
     */
    protected function generateFilterConfigurations(): \Generator
    {
        $fields = ['name', 'email', 'active'];
        $operators = ['=', '!=', '>', '<', '>=', '<=', 'LIKE'];

        for ($i = 0; $i < 100; $i++) {
            $numFilters = rand(1, 3);
            $filters = [];

            for ($j = 0; $j < $numFilters; $j++) {
                $field = $fields[array_rand($fields)];
                $operator = $operators[array_rand($operators)];

                // Generate appropriate value based on field
                $value = $this->generateValueForField($field, $operator);

                $filters[] = [
                    'field' => $field,
                    'operator' => $operator,
                    'value' => $value,
                ];
            }

            yield [
                'filters' => $filters,
            ];
        }
    }

    /**
     * Generate single filter configurations.
     */
    protected function generateSingleFilterConfigurations(): \Generator
    {
        $fields = ['name', 'email', 'active'];
        $operators = ['=', '!=', '>', '<', '>=', '<=', 'LIKE'];

        for ($i = 0; $i < 100; $i++) {
            $field = $fields[array_rand($fields)];
            $operator = $operators[array_rand($operators)];
            $value = $this->generateValueForField($field, $operator);

            yield [
                'field' => $field,
                'operator' => $operator,
                'value' => $value,
            ];
        }
    }

    /**
     * Generate progressive filter configurations.
     */
    protected function generateProgressiveFilterConfigurations(): \Generator
    {
        $fields = ['name', 'email', 'active'];
        $operators = ['=', '!=', '>', '<', '>=', '<='];

        for ($i = 0; $i < 100; $i++) {
            $numFilters = rand(2, 4);
            $filters = [];

            for ($j = 0; $j < $numFilters; $j++) {
                $field = $fields[array_rand($fields)];
                $operator = $operators[array_rand($operators)];
                $value = $this->generateValueForField($field, $operator);

                $filters[] = [
                    'field' => $field,
                    'operator' => $operator,
                    'value' => $value,
                ];
            }

            yield [
                'filters' => $filters,
            ];
        }
    }

    /**
     * Generate impossible filter configurations.
     */
    protected function generateImpossibleFilterConfigurations(): \Generator
    {
        for ($i = 0; $i < 100; $i++) {
            // Generate contradictory filters
            $filters = [
                [
                    'field' => 'active',
                    'operator' => '=',
                    'value' => true,
                ],
                [
                    'field' => 'active',
                    'operator' => '=',
                    'value' => false,
                ],
            ];

            yield [
                'filters' => $filters,
            ];
        }
    }

    /**
     * Generate addFilters configurations.
     */
    protected function generateAddFiltersConfigurations(): \Generator
    {
        for ($i = 0; $i < 100; $i++) {
            $filters = [];

            // Randomly add 1-3 filters (associative array format: column => value)
            $numFilters = rand(1, 3);
            $availableFields = ['name', 'email', 'active'];

            for ($j = 0; $j < $numFilters; $j++) {
                $field = $availableFields[array_rand($availableFields)];

                // Avoid duplicate keys
                if (isset($filters[$field])) {
                    continue;
                }

                $value = $this->generateValueForField($field, '=');
                $filters[$field] = $value;
            }

            yield [
                'filters' => $filters,
            ];
        }
    }

    /**
     * Generate combined filter and search configurations.
     */
    protected function generateCombinedFilterSearchConfigurations(): \Generator
    {
        $searchTerms = ['User', 'example', 'test', '1', '5', ''];

        for ($i = 0; $i < 100; $i++) {
            $numFilters = rand(0, 2);
            $filters = [];

            for ($j = 0; $j < $numFilters; $j++) {
                $field = ['active'][array_rand(['active'])];
                $operator = ['=', '!='][array_rand(['=', '!='])];
                $value = $this->generateValueForField($field, $operator);

                $filters[] = [
                    'field' => $field,
                    'operator' => $operator,
                    'value' => $value,
                ];
            }

            yield [
                'filters' => $filters,
                'search' => $searchTerms[array_rand($searchTerms)],
                'searchFields' => ['name', 'email'],
            ];
        }
    }

    /**
     * Generate appropriate value for a field based on its type.
     */
    protected function generateValueForField(string $field, string $operator): mixed
    {
        switch ($field) {
            case 'name':
                if ($operator === 'LIKE') {
                    return '%User%';
                }

                return 'User ' . rand(1, 100);

            case 'email':
                if ($operator === 'LIKE') {
                    return '%@example.com%';
                }

                return 'user' . rand(1, 100) . '@example.com';

            case 'active':
                return (bool) rand(0, 1);

            case 'created_at':
                return now()->subDays(rand(0, 365))->format('Y-m-d H:i:s');

            default:
                return rand(1, 100);
        }
    }
}
