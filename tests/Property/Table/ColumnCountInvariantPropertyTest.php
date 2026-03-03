<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Property\Table;

use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Tests\Fixtures\Models\User;
use Canvastack\Canvastack\Tests\Support\PropertyTesting\PropertyTestCase;

/**
 * Property 14: Column Count Invariant.
 *
 * Validates: Requirements 46.1
 *
 * Property: FOR ALL table operations, the number of columns SHALL remain
 * constant unless explicitly changed via setFields() or similar methods.
 *
 * This property ensures that operations like sorting, filtering, styling,
 * and other configurations do not inadvertently add or remove columns,
 * maintaining data integrity throughout the table lifecycle.
 */
class ColumnCountInvariantPropertyTest extends PropertyTestCase
{
    private TableBuilder $table;

    protected function setUp(): void
    {
        parent::setUp();

        $this->table = app(TableBuilder::class);
        $this->table->setModel(new User());
    }

    /**
     * Property 14: Column Count Invariant.
     *
     * Test that column count remains constant across non-column-modifying operations.
     *
     * @test
     * @group property
     * @group invariant
     * @group canvastack-table-complete
     */
    public function property_column_count_remains_constant_across_operations(): void
    {
        $this->forAll(
            $this->generateTableConfigurationsWithOperations(),
            function (array $config) {
                // Step 1: Set initial columns
                $this->table->setFields($config['fields']);
                $initialColumnCount = count($this->table->getColumns());

                // Step 2: Apply various operations that should NOT change column count
                foreach ($config['operations'] as $operation) {
                    $this->applyOperation($this->table, $operation);
                }

                // Step 3: Verify column count is unchanged
                $finalColumnCount = count($this->table->getColumns());

                $this->assertEquals(
                    $initialColumnCount,
                    $finalColumnCount,
                    sprintf(
                        'Column count should remain constant (%d) after operations, but got %d. Operations: %s',
                        $initialColumnCount,
                        $finalColumnCount,
                        json_encode($config['operations'])
                    )
                );

                return true;
            },
            100
        );
    }

    /**
     * Property 14.1: Column count invariant for sorting operations.
     *
     * Test that sorting operations don't change column count.
     *
     * @test
     * @group property
     * @group invariant
     */
    public function property_sorting_operations_preserve_column_count(): void
    {
        $this->forAll(
            $this->generateSortingOperations(),
            function (array $config) {
                $this->table->setFields($config['fields']);
                $initialCount = count($this->table->getColumns());

                // Apply sorting operations
                if (isset($config['orderColumn'])) {
                    $this->table->orderby($config['orderColumn'], $config['orderDirection']);
                }

                if (isset($config['sortableColumns'])) {
                    $this->table->sortable($config['sortableColumns']);
                }

                $finalCount = count($this->table->getColumns());

                $this->assertEquals(
                    $initialCount,
                    $finalCount,
                    'Sorting operations should not change column count'
                );

                return true;
            },
            100
        );
    }

    /**
     * Property 14.2: Column count invariant for filtering operations.
     *
     * Test that filtering operations don't change column count.
     *
     * @test
     * @group property
     * @group invariant
     */
    public function property_filtering_operations_preserve_column_count(): void
    {
        $this->forAll(
            $this->generateFilteringOperations(),
            function (array $config) {
                $this->table->setFields($config['fields']);
                $initialCount = count($this->table->getColumns());

                // Apply filtering operations
                if (isset($config['whereConditions'])) {
                    foreach ($config['whereConditions'] as $condition) {
                        $this->table->where(
                            $condition['field'],
                            $condition['operator'],
                            $condition['value']
                        );
                    }
                }

                if (isset($config['searchableColumns'])) {
                    $this->table->searchable($config['searchableColumns']);
                }

                $finalCount = count($this->table->getColumns());

                $this->assertEquals(
                    $initialCount,
                    $finalCount,
                    'Filtering operations should not change column count'
                );

                return true;
            },
            100
        );
    }

    /**
     * Property 14.3: Column count invariant for styling operations.
     *
     * Test that styling operations don't change column count.
     *
     * @test
     * @group property
     * @group invariant
     */
    public function property_styling_operations_preserve_column_count(): void
    {
        $this->forAll(
            $this->generateStylingOperations(),
            function (array $config) {
                $this->table->setFields($config['fields']);
                $initialCount = count($this->table->getColumns());

                // Apply styling operations
                if (isset($config['columnWidths'])) {
                    foreach ($config['columnWidths'] as $column => $width) {
                        $this->table->setColumnWidth($column, $width);
                    }
                }

                if (isset($config['alignments'])) {
                    foreach ($config['alignments'] as $alignment) {
                        $this->table->setAlignColumns(
                            $alignment['align'],
                            $alignment['columns'],
                            $alignment['header'],
                            $alignment['body']
                        );
                    }
                }

                if (isset($config['colors'])) {
                    foreach ($config['colors'] as $color) {
                        $this->table->setBackgroundColor(
                            $color['background'],
                            $color['text'] ?? null,
                            $color['columns'] ?? null,
                            $color['header'] ?? true,
                            $color['body'] ?? false
                        );
                    }
                }

                $finalCount = count($this->table->getColumns());

                $this->assertEquals(
                    $initialCount,
                    $finalCount,
                    'Styling operations should not change column count'
                );

                return true;
            },
            100
        );
    }

    /**
     * Property 14.4: Column count invariant for display operations.
     *
     * Test that display configuration operations don't change column count.
     *
     * @test
     * @group property
     * @group invariant
     */
    public function property_display_operations_preserve_column_count(): void
    {
        $this->forAll(
            $this->generateDisplayOperations(),
            function (array $config) {
                $this->table->setFields($config['fields']);
                $initialCount = count($this->table->getColumns());

                // Apply display operations
                if (isset($config['displayLimit'])) {
                    $this->table->displayRowsLimitOnLoad($config['displayLimit']);
                }

                if (isset($config['serverSide'])) {
                    $this->table->setServerSide($config['serverSide']);
                }

                if (isset($config['isDatatable'])) {
                    $this->table->setDatatableType($config['isDatatable']);
                }

                if (isset($config['urlValueField'])) {
                    $this->table->setUrlValue($config['urlValueField']);
                }

                $finalCount = count($this->table->getColumns());

                $this->assertEquals(
                    $initialCount,
                    $finalCount,
                    'Display operations should not change column count'
                );

                return true;
            },
            100
        );
    }

    /**
     * Property 14.5: Column count invariant for action operations.
     *
     * Test that action configuration operations don't change column count.
     *
     * @test
     * @group property
     * @group invariant
     */
    public function property_action_operations_preserve_column_count(): void
    {
        $this->forAll(
            $this->generateActionOperations(),
            function (array $config) {
                $this->table->setFields($config['fields']);
                $initialCount = count($this->table->getColumns());

                // Apply action operations
                if (isset($config['actions'])) {
                    $this->table->setActions($config['actions']);
                }

                if (isset($config['removeButtons'])) {
                    $this->table->removeButtons($config['removeButtons']);
                }

                if (isset($config['clickableColumns'])) {
                    $this->table->clickable($config['clickableColumns']);
                }

                $finalCount = count($this->table->getColumns());

                $this->assertEquals(
                    $initialCount,
                    $finalCount,
                    'Action operations should not change column count'
                );

                return true;
            },
            100
        );
    }

    /**
     * Property 14.6: Column count invariant for fixed column operations.
     *
     * Test that fixed column operations don't change column count.
     *
     * @test
     * @group property
     * @group invariant
     */
    public function property_fixed_column_operations_preserve_column_count(): void
    {
        $this->forAll(
            $this->generateFixedColumnOperations(),
            function (array $config) {
                $this->table->setFields($config['fields']);
                $initialCount = count($this->table->getColumns());

                // Apply fixed column operations
                if (isset($config['fixedLeft']) || isset($config['fixedRight'])) {
                    $this->table->fixedColumns(
                        $config['fixedLeft'] ?? null,
                        $config['fixedRight'] ?? null
                    );
                }

                if (isset($config['clearFixed']) && $config['clearFixed']) {
                    $this->table->clearFixedColumns();
                }

                $finalCount = count($this->table->getColumns());

                $this->assertEquals(
                    $initialCount,
                    $finalCount,
                    'Fixed column operations should not change column count'
                );

                return true;
            },
            100
        );
    }

    /**
     * Property 14.7: Column count changes only with explicit column operations.
     *
     * Test that column count only changes when setFields() or setHiddenColumns() is called.
     *
     * @test
     * @group property
     * @group invariant
     */
    public function property_column_count_changes_only_with_explicit_operations(): void
    {
        $this->forAll(
            $this->generateColumnModifyingOperations(),
            function (array $config) {
                // Set initial columns
                $this->table->setFields($config['initialFields']);
                $initialCount = count($this->table->getColumns());

                // Apply non-column-modifying operations
                foreach ($config['nonModifyingOps'] as $operation) {
                    $this->applyOperation($this->table, $operation);
                }

                // Verify count unchanged
                $countAfterNonModifying = count($this->table->getColumns());
                $this->assertEquals(
                    $initialCount,
                    $countAfterNonModifying,
                    'Column count should not change after non-modifying operations'
                );

                // Apply column-modifying operation
                $this->table->setFields($config['newFields']);
                $countAfterModifying = count($this->table->getColumns());

                // Verify count changed to expected value
                $expectedCount = count($config['newFields']);
                $this->assertEquals(
                    $expectedCount,
                    $countAfterModifying,
                    'Column count should change to expected value after setFields()'
                );

                return true;
            },
            100
        );
    }

    /**
     * Apply an operation to the table.
     */
    protected function applyOperation(TableBuilder $table, array $operation): void
    {
        $method = $operation['method'];
        $params = $operation['params'] ?? [];

        switch ($method) {
            case 'orderby':
                $table->orderby($params[0], $params[1] ?? 'asc');
                break;
            case 'sortable':
                $table->sortable($params[0] ?? null);
                break;
            case 'searchable':
                $table->searchable($params[0] ?? null);
                break;
            case 'where':
                $table->where($params[0], $params[1], $params[2]);
                break;
            case 'setColumnWidth':
                $table->setColumnWidth($params[0], $params[1]);
                break;
            case 'setAlignColumns':
                $table->setAlignColumns(
                    $params[0],
                    $params[1] ?? [],
                    $params[2] ?? true,
                    $params[3] ?? true
                );
                break;
            case 'setBackgroundColor':
                $table->setBackgroundColor(
                    $params[0],
                    $params[1] ?? null,
                    $params[2] ?? null,
                    $params[3] ?? true,
                    $params[4] ?? false
                );
                break;
            case 'displayRowsLimitOnLoad':
                $table->displayRowsLimitOnLoad($params[0] ?? 10);
                break;
            case 'setServerSide':
                $table->setServerSide($params[0] ?? true);
                break;
            case 'setActions':
                $table->setActions($params[0] ?? true);
                break;
            case 'fixedColumns':
                $table->fixedColumns($params[0] ?? null, $params[1] ?? null);
                break;
            case 'clickable':
                $table->clickable($params[0] ?? null);
                break;
        }
    }

    /**
     * Generate table configurations with various operations.
     */
    protected function generateTableConfigurationsWithOperations(): \Generator
    {
        $fieldOptions = [
            ['name', 'email'],
            ['name', 'email', 'active'],
            ['name', 'email', 'active', 'created_at'],
            ['name', 'active'],
        ];

        $operationTypes = [
            'orderby', 'sortable', 'searchable', 'where',
            'setColumnWidth', 'setAlignColumns', 'setBackgroundColor',
            'displayRowsLimitOnLoad', 'setServerSide', 'setActions',
            'fixedColumns', 'clickable',
        ];

        for ($i = 0; $i < 100; $i++) {
            $fields = $fieldOptions[array_rand($fieldOptions)];
            $numOperations = rand(1, 5);
            $operations = [];

            for ($j = 0; $j < $numOperations; $j++) {
                $opType = $operationTypes[array_rand($operationTypes)];
                $operations[] = $this->generateOperation($opType, $fields);
            }

            yield [
                'fields' => $fields,
                'operations' => $operations,
            ];
        }
    }

    /**
     * Generate sorting operations.
     */
    protected function generateSortingOperations(): \Generator
    {
        $fieldOptions = [
            ['name', 'email', 'active'],
            ['name', 'email'],
        ];

        for ($i = 0; $i < 100; $i++) {
            $fields = $fieldOptions[array_rand($fieldOptions)];

            $config = [
                'fields' => $fields,
            ];

            if (rand(0, 1)) {
                $config['orderColumn'] = $fields[array_rand($fields)];
                $config['orderDirection'] = ['asc', 'desc'][array_rand(['asc', 'desc'])];
            }

            if (rand(0, 1)) {
                $config['sortableColumns'] = rand(0, 1) ? $fields : null;
            }

            yield $config;
        }
    }

    /**
     * Generate filtering operations.
     */
    protected function generateFilteringOperations(): \Generator
    {
        $fieldOptions = [
            ['name', 'email', 'active'],
            ['name', 'email'],
        ];

        for ($i = 0; $i < 100; $i++) {
            $fields = $fieldOptions[array_rand($fieldOptions)];

            $config = [
                'fields' => $fields,
            ];

            if (rand(0, 1)) {
                $config['whereConditions'] = [
                    [
                        'field' => $fields[array_rand($fields)],
                        'operator' => '>',
                        'value' => rand(1, 10),
                    ],
                ];
            }

            if (rand(0, 1)) {
                $config['searchableColumns'] = rand(0, 1) ? $fields : null;
            }

            yield $config;
        }
    }

    /**
     * Generate styling operations.
     */
    protected function generateStylingOperations(): \Generator
    {
        $fieldOptions = [
            ['name', 'email', 'active'],
            ['name', 'email'],
        ];

        for ($i = 0; $i < 100; $i++) {
            $fields = $fieldOptions[array_rand($fieldOptions)];

            $config = [
                'fields' => $fields,
            ];

            if (rand(0, 1)) {
                $config['columnWidths'] = [];
                foreach ($fields as $field) {
                    if (rand(0, 1)) {
                        $config['columnWidths'][$field] = rand(50, 300);
                    }
                }
            }

            if (rand(0, 1)) {
                $config['alignments'] = [
                    [
                        'align' => ['left', 'center', 'right'][array_rand(['left', 'center', 'right'])],
                        'columns' => rand(0, 1) ? [$fields[array_rand($fields)]] : [],
                        'header' => (bool) rand(0, 1),
                        'body' => (bool) rand(0, 1),
                    ],
                ];
            }

            if (rand(0, 1)) {
                $config['colors'] = [
                    [
                        'background' => '#' . str_pad(dechex(rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT),
                        'text' => '#' . str_pad(dechex(rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT),
                        'columns' => rand(0, 1) ? [$fields[array_rand($fields)]] : null,
                        'header' => (bool) rand(0, 1),
                        'body' => (bool) rand(0, 1),
                    ],
                ];
            }

            yield $config;
        }
    }

    /**
     * Generate display operations.
     */
    protected function generateDisplayOperations(): \Generator
    {
        $fieldOptions = [
            ['name', 'email', 'active'],
            ['name', 'email'],
        ];

        for ($i = 0; $i < 100; $i++) {
            $fields = $fieldOptions[array_rand($fieldOptions)];

            $config = [
                'fields' => $fields,
                'displayLimit' => [10, 25, 50, 100, 'all'][array_rand([10, 25, 50, 100, 'all'])],
                'serverSide' => (bool) rand(0, 1),
                'isDatatable' => (bool) rand(0, 1),
            ];

            if (rand(0, 1)) {
                $config['urlValueField'] = $fields[array_rand($fields)];
            }

            yield $config;
        }
    }

    /**
     * Generate action operations.
     */
    protected function generateActionOperations(): \Generator
    {
        $fieldOptions = [
            ['name', 'email', 'active'],
            ['name', 'email'],
        ];

        for ($i = 0; $i < 100; $i++) {
            $fields = $fieldOptions[array_rand($fieldOptions)];

            $config = [
                'fields' => $fields,
                'actions' => [true, false, []][array_rand([true, false, []])],
            ];

            if (rand(0, 1)) {
                $config['removeButtons'] = ['edit', 'delete'];
            }

            if (rand(0, 1)) {
                $config['clickableColumns'] = rand(0, 1) ? $fields : null;
            }

            yield $config;
        }
    }

    /**
     * Generate fixed column operations.
     */
    protected function generateFixedColumnOperations(): \Generator
    {
        $fieldOptions = [
            ['name', 'email', 'active'],
            ['name', 'email', 'active', 'created_at'],
        ];

        for ($i = 0; $i < 100; $i++) {
            $fields = $fieldOptions[array_rand($fieldOptions)];

            $config = [
                'fields' => $fields,
            ];

            if (rand(0, 1)) {
                $config['fixedLeft'] = rand(0, 2);
            }

            if (rand(0, 1)) {
                $config['fixedRight'] = rand(0, 2);
            }

            if (rand(0, 1)) {
                $config['clearFixed'] = true;
            }

            yield $config;
        }
    }

    /**
     * Generate column modifying operations.
     */
    protected function generateColumnModifyingOperations(): \Generator
    {
        $fieldOptions = [
            ['name', 'email', 'active'],
            ['name', 'email'],
            ['name', 'active'],
        ];

        $nonModifyingOps = [
            'orderby', 'sortable', 'searchable', 'setColumnWidth',
            'displayRowsLimitOnLoad', 'setServerSide',
        ];

        for ($i = 0; $i < 100; $i++) {
            $initialFields = $fieldOptions[array_rand($fieldOptions)];
            $newFields = $fieldOptions[array_rand($fieldOptions)];

            // Generate 2-4 non-modifying operations
            $numOps = rand(2, 4);
            $operations = [];
            for ($j = 0; $j < $numOps; $j++) {
                $opType = $nonModifyingOps[array_rand($nonModifyingOps)];
                $operations[] = $this->generateOperation($opType, $initialFields);
            }

            yield [
                'initialFields' => $initialFields,
                'nonModifyingOps' => $operations,
                'newFields' => $newFields,
            ];
        }
    }

    /**
     * Generate a single operation.
     */
    protected function generateOperation(string $type, array $fields): array
    {
        switch ($type) {
            case 'orderby':
                return [
                    'method' => 'orderby',
                    'params' => [$fields[array_rand($fields)], ['asc', 'desc'][rand(0, 1)]],
                ];

            case 'sortable':
                return [
                    'method' => 'sortable',
                    'params' => [rand(0, 1) ? $fields : null],
                ];

            case 'searchable':
                return [
                    'method' => 'searchable',
                    'params' => [rand(0, 1) ? $fields : null],
                ];

            case 'where':
                return [
                    'method' => 'where',
                    'params' => [$fields[array_rand($fields)], '>', rand(1, 10)],
                ];

            case 'setColumnWidth':
                return [
                    'method' => 'setColumnWidth',
                    'params' => [$fields[array_rand($fields)], rand(50, 300)],
                ];

            case 'setAlignColumns':
                return [
                    'method' => 'setAlignColumns',
                    'params' => [
                        ['left', 'center', 'right'][rand(0, 2)],
                        rand(0, 1) ? [$fields[array_rand($fields)]] : [],
                        (bool) rand(0, 1),
                        (bool) rand(0, 1),
                    ],
                ];

            case 'setBackgroundColor':
                return [
                    'method' => 'setBackgroundColor',
                    'params' => [
                        '#' . str_pad(dechex(rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT),
                        '#' . str_pad(dechex(rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT),
                        rand(0, 1) ? [$fields[array_rand($fields)]] : null,
                        (bool) rand(0, 1),
                        (bool) rand(0, 1),
                    ],
                ];

            case 'displayRowsLimitOnLoad':
                return [
                    'method' => 'displayRowsLimitOnLoad',
                    'params' => [[10, 25, 50, 100][rand(0, 3)]],
                ];

            case 'setServerSide':
                return [
                    'method' => 'setServerSide',
                    'params' => [(bool) rand(0, 1)],
                ];

            case 'setActions':
                return [
                    'method' => 'setActions',
                    'params' => [[true, false][rand(0, 1)]],
                ];

            case 'fixedColumns':
                return [
                    'method' => 'fixedColumns',
                    'params' => [rand(0, 1) ? rand(0, 2) : null, rand(0, 1) ? rand(0, 2) : null],
                ];

            case 'clickable':
                return [
                    'method' => 'clickable',
                    'params' => [rand(0, 1) ? $fields : null],
                ];

            default:
                return [
                    'method' => 'setServerSide',
                    'params' => [true],
                ];
        }
    }
}
