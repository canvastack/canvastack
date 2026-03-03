<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Property\Table;

use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Tests\Fixtures\Models\User;
use Canvastack\Canvastack\Tests\Support\PropertyTesting\PropertyTestCase;

/**
 * Property 13: Configuration Round-Trip.
 *
 * Validates: Requirements 45.4
 *
 * Property: For ALL valid Configuration objects, parsing then serializing then
 * parsing SHALL produce an equivalent object (round-trip property).
 *
 * This property ensures that table configurations can be reliably serialized
 * to arrays and deserialized back to objects without data loss, enabling
 * configuration persistence and restoration.
 */
class ConfigurationRoundTripPropertyTest extends PropertyTestCase
{
    private TableBuilder $table;

    protected function setUp(): void
    {
        parent::setUp();

        $this->table = app(TableBuilder::class);
    }

    /**
     * Property 13: Configuration Round-Trip.
     *
     * Test that serializing then deserializing produces equivalent configuration.
     *
     * @test
     * @group property
     * @group configuration
     * @group canvastack-table-complete
     */
    public function property_configuration_round_trip_preserves_all_properties(): void
    {
        $this->forAll(
            $this->generateTableConfigurations(),
            function (array $config) {
                // Step 1: Apply configuration to table
                $this->applyConfiguration($this->table, $config);

                // Step 2: Serialize configuration to array
                $serialized = $this->table->toArray();

                // Step 3: Create new table and deserialize
                $newTable = app(TableBuilder::class);
                $newTable->fromArray($serialized);

                // Step 4: Serialize again
                $reserialized = $newTable->toArray();

                // Verify: Serialized configurations are identical
                $this->assertEquals(
                    $serialized,
                    $reserialized,
                    'Round-trip serialization should preserve all configuration properties'
                );

                return true;
            },
            100
        );
    }

    /**
     * Property 13.1: Configuration property preservation.
     *
     * Test that all individual properties are preserved through round-trip.
     *
     * @test
     * @group property
     * @group configuration
     */
    public function property_all_configuration_properties_are_preserved(): void
    {
        $this->forAll(
            $this->generateTableConfigurations(),
            function (array $config) {
                // Apply configuration
                $this->applyConfiguration($this->table, $config);

                // Serialize and deserialize
                $serialized = $this->table->toArray();
                $newTable = app(TableBuilder::class);
                $newTable->fromArray($serialized);

                // Verify each property individually
                $this->assertConfigurationPropertiesMatch($this->table, $newTable, $config);

                return true;
            },
            100
        );
    }

    /**
     * Property 13.2: Configuration idempotence.
     *
     * Test that multiple round-trips produce the same result.
     *
     * @test
     * @group property
     * @group configuration
     */
    public function property_multiple_round_trips_are_idempotent(): void
    {
        $this->forAll(
            $this->generateTableConfigurations(),
            function (array $config) {
                // Apply configuration
                $this->applyConfiguration($this->table, $config);

                // First round-trip
                $serialized1 = $this->table->toArray();
                $table1 = app(TableBuilder::class);
                $table1->fromArray($serialized1);

                // Second round-trip
                $serialized2 = $table1->toArray();
                $table2 = app(TableBuilder::class);
                $table2->fromArray($serialized2);

                // Third round-trip
                $serialized3 = $table2->toArray();

                // Verify: All serializations are identical
                $this->assertEquals(
                    $serialized1,
                    $serialized2,
                    'First and second round-trip should be identical'
                );

                $this->assertEquals(
                    $serialized2,
                    $serialized3,
                    'Second and third round-trip should be identical'
                );

                return true;
            },
            100
        );
    }

    /**
     * Property 13.3: Configuration with complex types.
     *
     * Test that complex configuration types (arrays, nested structures) are preserved.
     *
     * @test
     * @group property
     * @group configuration
     */
    public function property_complex_configuration_types_are_preserved(): void
    {
        $this->forAll(
            $this->generateComplexTableConfigurations(),
            function (array $config) {
                // Apply configuration
                $this->applyConfiguration($this->table, $config);

                // Round-trip
                $serialized = $this->table->toArray();
                $newTable = app(TableBuilder::class);
                $newTable->fromArray($serialized);
                $reserialized = $newTable->toArray();

                // Verify: Complex types are preserved
                if (isset($config['columnAlignments'])) {
                    $this->assertEquals(
                        $serialized['columnAlignments'],
                        $reserialized['columnAlignments'],
                        'Column alignments should be preserved'
                    );
                }

                if (isset($config['columnColors'])) {
                    $this->assertEquals(
                        $serialized['columnColors'],
                        $reserialized['columnColors'],
                        'Column colors should be preserved'
                    );
                }

                if (isset($config['whereConditions']) && isset($serialized['whereConditions'])) {
                    $this->assertEquals(
                        $serialized['whereConditions'],
                        $reserialized['whereConditions'],
                        'Where conditions should be preserved'
                    );
                }

                return true;
            },
            100
        );
    }

    /**
     * Property 13.4: Configuration with null values.
     *
     * Test that null values are preserved correctly.
     *
     * @test
     * @group property
     * @group configuration
     */
    public function property_null_values_are_preserved(): void
    {
        $this->forAll(
            $this->generateTableConfigurationsWithNulls(),
            function (array $config) {
                // Apply configuration
                $this->applyConfiguration($this->table, $config);

                // Round-trip
                $serialized = $this->table->toArray();
                $newTable = app(TableBuilder::class);
                $newTable->fromArray($serialized);
                $reserialized = $newTable->toArray();

                // Verify: Null values are preserved
                foreach (['orderColumn', 'tableWidth', 'fixedLeft', 'fixedRight'] as $property) {
                    if (array_key_exists($property, $serialized)) {
                        $this->assertEquals(
                            $serialized[$property],
                            $reserialized[$property],
                            "Property '{$property}' should be preserved (including null)"
                        );
                    }
                }

                return true;
            },
            100
        );
    }

    /**
     * Property 13.5: Configuration with boolean values.
     *
     * Test that boolean values are preserved correctly.
     *
     * @test
     * @group property
     * @group configuration
     */
    public function property_boolean_values_are_preserved(): void
    {
        $this->forAll(
            $this->generateTableConfigurations(),
            function (array $config) {
                // Apply configuration
                $this->applyConfiguration($this->table, $config);

                // Round-trip
                $serialized = $this->table->toArray();
                $newTable = app(TableBuilder::class);
                $newTable->fromArray($serialized);
                $reserialized = $newTable->toArray();

                // Verify: Boolean values are preserved
                foreach (['serverSide', 'isDatatable', 'showNumbering'] as $property) {
                    if (isset($serialized[$property])) {
                        $this->assertSame(
                            $serialized[$property],
                            $reserialized[$property],
                            "Boolean property '{$property}' should be preserved with correct type"
                        );
                    }
                }

                return true;
            },
            100
        );
    }

    /**
     * Property 13.6: Configuration with array values.
     *
     * Test that array values maintain order and structure.
     *
     * @test
     * @group property
     * @group configuration
     */
    public function property_array_values_maintain_order_and_structure(): void
    {
        $this->forAll(
            $this->generateTableConfigurations(),
            function (array $config) {
                // Apply configuration
                $this->applyConfiguration($this->table, $config);

                // Round-trip
                $serialized = $this->table->toArray();
                $newTable = app(TableBuilder::class);
                $newTable->fromArray($serialized);
                $reserialized = $newTable->toArray();

                // Verify: Array properties maintain order
                if (isset($serialized['columns'])) {
                    $this->assertEquals(
                        $serialized['columns'],
                        $reserialized['columns'],
                        'Columns array should maintain order'
                    );
                }

                if (isset($serialized['hiddenColumns'])) {
                    $this->assertEquals(
                        $serialized['hiddenColumns'],
                        $reserialized['hiddenColumns'],
                        'Hidden columns array should maintain order'
                    );
                }

                return true;
            },
            100
        );
    }

    /**
     * Property 13.7: Configuration serialization format.
     *
     * Test that serialized format is valid and parseable.
     *
     * @test
     * @group property
     * @group configuration
     */
    public function property_serialized_format_is_valid_array(): void
    {
        $this->forAll(
            $this->generateTableConfigurations(),
            function (array $config) {
                // Apply configuration
                $this->applyConfiguration($this->table, $config);

                // Serialize
                $serialized = $this->table->toArray();

                // Verify: Serialized is an array
                $this->assertIsArray(
                    $serialized,
                    'Serialized configuration should be an array'
                );

                // Verify: Can be JSON encoded (for storage)
                $json = json_encode($serialized);
                $this->assertNotFalse(
                    $json,
                    'Serialized configuration should be JSON encodable'
                );

                // Verify: Can be JSON decoded back
                $decoded = json_decode($json, true);
                $this->assertEquals(
                    $serialized,
                    $decoded,
                    'JSON round-trip should preserve configuration'
                );

                return true;
            },
            100
        );
    }

    /**
     * Apply configuration to table builder.
     *
     * Uses fromArray() to bypass validation for testing purposes.
     */
    protected function applyConfiguration(TableBuilder $table, array $config): void
    {
        // Build a complete configuration array for fromArray()
        $fullConfig = [
            'tableName' => 'test_users',
            'tableLabel' => $config['label'] ?? null,
            'serverSide' => $config['serverSide'] ?? true,
            'columns' => $config['fields'] ?? [],
            'hiddenColumns' => $config['hiddenColumns'] ?? [],
            'displayLimit' => $config['displayLimit'] ?? 10,
            'isDatatable' => $config['isDatatable'] ?? true,
            'orderColumn' => $config['orderColumn'] ?? null,
            'orderDirection' => $config['orderDirection'] ?? 'asc',
            'sortableColumns' => $config['sortableColumns'] ?? null,
            'searchableColumns' => $config['searchableColumns'] ?? null,
            'columnAlignments' => $config['columnAlignments'] ?? [],
            'columnColors' => $config['columnColors'] ?? [],
            'fixedLeft' => $config['fixedLeft'] ?? null,
            'fixedRight' => $config['fixedRight'] ?? null,
            'whereConditions' => $config['whereConditions'] ?? [],
            'modelClass' => User::class,
        ];

        // Use fromArray to set configuration without validation
        $table->fromArray($fullConfig);
    }

    /**
     * Assert that configuration properties match between two tables.
     */
    protected function assertConfigurationPropertiesMatch(
        TableBuilder $table1,
        TableBuilder $table2,
        array $originalConfig
    ): void {
        $serialized1 = $table1->toArray();
        $serialized2 = $table2->toArray();

        // Check basic properties
        $basicProperties = [
            'tableName', 'tableLabel', 'serverSide', 'displayLimit',
            'isDatatable', 'showNumbering', 'orderColumn', 'orderDirection',
        ];

        foreach ($basicProperties as $property) {
            if (isset($serialized1[$property])) {
                $this->assertEquals(
                    $serialized1[$property],
                    $serialized2[$property],
                    "Property '{$property}' should match"
                );
            }
        }

        // Check array properties
        $arrayProperties = [
            'columns', 'hiddenColumns', 'columnWidths', 'columnAlignments',
            'columnColors', 'whereConditions',
        ];

        foreach ($arrayProperties as $property) {
            if (isset($serialized1[$property])) {
                $this->assertEquals(
                    $serialized1[$property],
                    $serialized2[$property],
                    "Array property '{$property}' should match"
                );
            }
        }
    }

    /**
     * Generate random table configurations.
     */
    protected function generateTableConfigurations(): \Generator
    {
        $fieldOptions = [
            ['id', 'name', 'email'],
            ['id', 'name'],
            ['name', 'email'],
            ['id'],
        ];

        $labelOptions = ['Users Table', 'User List', 'All Users', null];
        $serverSideOptions = [true, false];
        $displayLimitOptions = [10, 25, 50, 100, 'all'];
        $isDatatableOptions = [true, false];

        for ($i = 0; $i < 100; $i++) {
            $fields = $fieldOptions[array_rand($fieldOptions)];

            $config = [
                'fields' => $fields,
                'label' => $labelOptions[array_rand($labelOptions)],
                'serverSide' => $serverSideOptions[array_rand($serverSideOptions)],
                'displayLimit' => $displayLimitOptions[array_rand($displayLimitOptions)],
                'isDatatable' => $isDatatableOptions[array_rand($isDatatableOptions)],
            ];

            // Randomly add optional configurations
            if (rand(0, 1)) {
                $config['hiddenColumns'] = [array_rand(array_flip($fields))];
            }

            if (rand(0, 1)) {
                $config['orderColumn'] = $fields[array_rand($fields)];
                $config['orderDirection'] = ['asc', 'desc'][array_rand(['asc', 'desc'])];
            }

            if (rand(0, 1)) {
                $config['sortableColumns'] = rand(0, 1) ? $fields : null;
            }

            if (rand(0, 1)) {
                $config['searchableColumns'] = rand(0, 1) ? $fields : null;
            }

            yield $config;
        }
    }

    /**
     * Generate complex table configurations with nested structures.
     */
    protected function generateComplexTableConfigurations(): \Generator
    {
        $fieldOptions = [
            ['id', 'name', 'email'],
            ['id', 'name'],
        ];

        for ($i = 0; $i < 100; $i++) {
            $fields = $fieldOptions[array_rand($fieldOptions)];

            $config = [
                'fields' => $fields,
                'label' => 'Complex Table',
                'serverSide' => true,
            ];

            // Add column alignments
            $config['columnAlignments'] = [];
            foreach ($fields as $field) {
                if (rand(0, 1)) {
                    $config['columnAlignments'][$field] = [
                        'align' => ['left', 'center', 'right'][array_rand(['left', 'center', 'right'])],
                        'header' => (bool) rand(0, 1),
                        'body' => (bool) rand(0, 1),
                    ];
                }
            }

            // Add column colors
            $config['columnColors'] = [];
            foreach ($fields as $field) {
                if (rand(0, 1)) {
                    $config['columnColors'][$field] = [
                        'background' => '#' . str_pad(dechex(rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT),
                        'text' => '#' . str_pad(dechex(rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT),
                        'header' => (bool) rand(0, 1),
                        'body' => (bool) rand(0, 1),
                    ];
                }
            }

            // Add where conditions
            if (rand(0, 1)) {
                $config['whereConditions'] = [
                    [
                        'field' => 'id',
                        'operator' => '>',
                        'value' => rand(1, 5),
                    ],
                ];
            }

            yield $config;
        }
    }

    /**
     * Generate table configurations with null values.
     */
    protected function generateTableConfigurationsWithNulls(): \Generator
    {
        $fieldOptions = [
            ['id', 'name', 'email'],
            ['id', 'name'],
        ];

        for ($i = 0; $i < 100; $i++) {
            $config = [
                'fields' => $fieldOptions[array_rand($fieldOptions)],
                'label' => rand(0, 1) ? 'Table with Nulls' : null,
                'serverSide' => true,
                'orderColumn' => null,
                'tableWidth' => null,
                'fixedLeft' => null,
                'fixedRight' => null,
            ];

            yield $config;
        }
    }
}
