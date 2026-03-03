<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Property\Table;

use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Tests\Fixtures\Models\User;
use Canvastack\Canvastack\Tests\Support\PropertyTesting\PropertyTestCase;

/**
 * Property 18: Render Idempotence.
 *
 * Validates: Requirements 47.3
 *
 * Property: FOR ALL table configurations, calling render() multiple times
 * without changes SHALL produce identical HTML output for all calls.
 *
 * This tests the mathematical property render(config) = render(render(config)),
 * which means that rendering is idempotent - calling render() multiple times
 * produces the same output as calling it once.
 *
 * This is critical for:
 * - Predictable rendering behavior
 * - Safe retry logic in case of failures
 * - Caching strategies (same input = same output)
 * - Avoiding unintended side effects in rendering
 * - Ensuring render() doesn't mutate state
 */
class RenderIdempotencePropertyTest extends PropertyTestCase
{
    private TableBuilder $table;

    protected function setUp(): void
    {
        parent::setUp();

        $this->table = app(TableBuilder::class);

        // Create test data
        $this->createTestData();
    }

    /**
     * Create test data for idempotence testing.
     */
    protected function createTestData(): void
    {
        // Create test users
        for ($i = 1; $i <= 10; $i++) {
            User::create([
                'name' => "User {$i}",
                'email' => "user{$i}@example.com",
                'password' => bcrypt('password'),
            ]);
        }
    }

    /**
     * Property 18: Render Idempotence.
     *
     * Test that calling render() multiple times produces identical HTML output.
     *
     * @test
     * @group property
     * @group rendering
     * @group canvastack-table-complete
     */
    public function property_render_is_idempotent(): void
    {
        $this->forAll(
            $this->generateRandomTableConfiguration(),
            function (array $config) {
                // Create fresh table instance with configuration
                $table = $this->createConfiguredTable($config);

                // Render first time
                $html1 = $table->render();

                // Render second time (without any changes)
                $html2 = $table->render();

                // Render third time (to be extra sure)
                $html3 = $table->render();

                // Verify all renders produce identical output
                $this->assertEquals(
                    $html1,
                    $html2,
                    'First and second render() calls produced different HTML output. ' .
                    'Render should be idempotent.'
                );

                $this->assertEquals(
                    $html2,
                    $html3,
                    'Second and third render() calls produced different HTML output. ' .
                    'Render should be idempotent.'
                );

                $this->assertEquals(
                    $html1,
                    $html3,
                    'First and third render() calls produced different HTML output. ' .
                    'Render should be idempotent.'
                );

                return true;
            },
            100
        );
    }

    /**
     * Property 18.1: Render idempotence with simple configuration.
     *
     * Test render idempotence with minimal configuration.
     *
     * @test
     * @group property
     * @group rendering
     * @group canvastack-table-complete
     */
    public function property_render_is_idempotent_with_simple_config(): void
    {
        $this->forAll(
            $this->generateSimpleConfiguration(),
            function (array $config) {
                $table = app(TableBuilder::class);
                $table->setModel(new User());
                $table->setFields($config['fields']);

                $html1 = $table->render();
                $html2 = $table->render();
                $html3 = $table->render();

                $this->assertEquals($html1, $html2);
                $this->assertEquals($html2, $html3);

                return true;
            },
            100
        );
    }

    /**
     * Property 18.2: Render idempotence with sorting.
     *
     * Test render idempotence with sorting configuration.
     *
     * @test
     * @group property
     * @group rendering
     * @group canvastack-table-complete
     */
    public function property_render_is_idempotent_with_sorting(): void
    {
        $this->forAll(
            $this->generateSortingConfiguration(),
            function (array $config) {
                $table = app(TableBuilder::class);
                $table->setModel(new User());
                $table->setFields(['id', 'name', 'email']);
                $table->orderby($config['column'], $config['direction']);
                $table->sortable($config['sortable']);

                $html1 = $table->render();
                $html2 = $table->render();

                $this->assertEquals($html1, $html2);

                return true;
            },
            100
        );
    }

    /**
     * Property 18.3: Render idempotence with searching.
     *
     * Test render idempotence with search configuration.
     *
     * @test
     * @group property
     * @group rendering
     * @group canvastack-table-complete
     */
    public function property_render_is_idempotent_with_searching(): void
    {
        $this->forAll(
            $this->generateSearchConfiguration(),
            function (array $config) {
                $table = app(TableBuilder::class);
                $table->setModel(new User());
                $table->setFields(['id', 'name', 'email']);
                $table->searchable($config['searchable']);

                $html1 = $table->render();
                $html2 = $table->render();

                $this->assertEquals($html1, $html2);

                return true;
            },
            100
        );
    }

    /**
     * Property 18.4: Render idempotence with styling.
     *
     * Test render idempotence with column styling.
     *
     * @test
     * @group property
     * @group rendering
     * @group canvastack-table-complete
     */
    public function property_render_is_idempotent_with_styling(): void
    {
        $this->forAll(
            $this->generateStylingConfiguration(),
            function (array $config) {
                $table = app(TableBuilder::class);
                $table->setModel(new User());
                $table->setFields(['id', 'name', 'email']);

                if (isset($config['width'])) {
                    $table->setWidth($config['width'], $config['unit']);
                }

                if (isset($config['columnWidth'])) {
                    $table->setColumnWidth($config['columnWidth']['column'], $config['columnWidth']['width']);
                }

                if (isset($config['align'])) {
                    $table->setAlignColumns($config['align']['alignment'], $config['align']['columns']);
                }

                $html1 = $table->render();
                $html2 = $table->render();

                $this->assertEquals($html1, $html2);

                return true;
            },
            100
        );
    }

    /**
     * Property 18.5: Render idempotence with actions.
     *
     * Test render idempotence with action buttons.
     *
     * @test
     * @group property
     * @group rendering
     * @group canvastack-table-complete
     */
    public function property_render_is_idempotent_with_actions(): void
    {
        $this->forAll(
            $this->generateActionsConfiguration(),
            function ($actions) {
                $table = app(TableBuilder::class);
                $table->setModel(new User());
                $table->setFields(['id', 'name', 'email']);
                $table->setActions($actions);

                $html1 = $table->render();
                $html2 = $table->render();

                $this->assertEquals($html1, $html2);

                return true;
            },
            100
        );
    }

    /**
     * Property 18.6: Render idempotence with pagination.
     *
     * Test render idempotence with pagination settings.
     *
     * @test
     * @group property
     * @group rendering
     * @group canvastack-table-complete
     */
    public function property_render_is_idempotent_with_pagination(): void
    {
        $this->forAll(
            $this->generatePaginationConfiguration(),
            function (array $config) {
                $table = app(TableBuilder::class);
                $table->setModel(new User());
                $table->setFields(['id', 'name', 'email']);
                $table->displayRowsLimitOnLoad($config['limit']);
                $table->setServerSide($config['serverSide']);

                $html1 = $table->render();
                $html2 = $table->render();

                $this->assertEquals($html1, $html2);

                return true;
            },
            100
        );
    }

    /**
     * Property 18.7: Render idempotence with complex configuration.
     *
     * Test render idempotence with multiple configuration options combined.
     *
     * @test
     * @group property
     * @group rendering
     * @group canvastack-table-complete
     */
    public function property_render_is_idempotent_with_complex_config(): void
    {
        $this->forAll(
            $this->generateComplexConfiguration(),
            function (array $config) {
                $table = $this->createConfiguredTable($config);

                $html1 = $table->render();
                $html2 = $table->render();
                $html3 = $table->render();

                // All three renders should be identical
                $this->assertEquals($html1, $html2);
                $this->assertEquals($html2, $html3);

                return true;
            },
            100
        );
    }

    /**
     * Property 18.8: Render doesn't mutate state.
     *
     * Test that render() doesn't mutate the table's internal state.
     *
     * @test
     * @group property
     * @group rendering
     * @group canvastack-table-complete
     */
    public function property_render_does_not_mutate_state(): void
    {
        $this->forAll(
            $this->generateRandomTableConfiguration(),
            function (array $config) {
                $table = $this->createConfiguredTable($config);

                // Capture state before rendering
                $stateBefore = $table->toArray();

                // Render
                $table->render();

                // Capture state after rendering
                $stateAfter = $table->toArray();

                // Verify state is unchanged
                $this->assertEquals(
                    $stateBefore,
                    $stateAfter,
                    "render() mutated the table's internal state. " .
                    'Rendering should not have side effects.'
                );

                return true;
            },
            100
        );
    }

    /**
     * Create a configured table instance.
     */
    protected function createConfiguredTable(array $config): TableBuilder
    {
        $table = app(TableBuilder::class);
        $table->setModel(new User());

        // Apply configuration
        if (isset($config['fields'])) {
            $table->setFields($config['fields']);
        }

        if (isset($config['label'])) {
            $table->label($config['label']);
        }

        if (isset($config['orderby'])) {
            $table->orderby($config['orderby']['column'], $config['orderby']['direction']);
        }

        if (isset($config['sortable'])) {
            $table->sortable($config['sortable']);
        }

        if (isset($config['searchable'])) {
            $table->searchable($config['searchable']);
        }

        if (isset($config['clickable'])) {
            $table->clickable($config['clickable']);
        }

        if (isset($config['width'])) {
            $table->setWidth($config['width']['value'], $config['width']['unit']);
        }

        if (isset($config['columnWidth'])) {
            foreach ($config['columnWidth'] as $column => $width) {
                $table->setColumnWidth($column, $width);
            }
        }

        if (isset($config['align'])) {
            $table->setAlignColumns(
                $config['align']['alignment'],
                $config['align']['columns'],
                $config['align']['header'],
                $config['align']['body']
            );
        }

        if (isset($config['actions'])) {
            $table->setActions($config['actions']);
        }

        if (isset($config['displayLimit'])) {
            $table->displayRowsLimitOnLoad($config['displayLimit']);
        }

        if (isset($config['serverSide'])) {
            $table->setServerSide($config['serverSide']);
        }

        if (isset($config['urlValue'])) {
            $table->setUrlValue($config['urlValue']);
        }

        return $table;
    }

    /**
     * Generate a random table configuration.
     */
    protected function generateRandomTableConfiguration(): \Generator
    {
        for ($i = 0; $i < 100; $i++) {
            $config = [
                'fields' => $this->randomFields(),
                'label' => $this->randomLabel(),
            ];

            // Randomly add optional configurations
            if (rand(0, 1)) {
                $config['orderby'] = [
                    'column' => $this->randomColumn(),
                    'direction' => $this->randomDirection(),
                ];
            }

            if (rand(0, 1)) {
                $config['sortable'] = $this->randomSortable();
            }

            if (rand(0, 1)) {
                $config['searchable'] = $this->randomSearchable();
            }

            if (rand(0, 1)) {
                $config['actions'] = $this->randomActions();
            }

            if (rand(0, 1)) {
                $config['displayLimit'] = $this->randomDisplayLimit();
            }

            if (rand(0, 1)) {
                $config['serverSide'] = (bool) rand(0, 1);
            }

            yield $config;
        }
    }

    /**
     * Generate simple configuration.
     */
    protected function generateSimpleConfiguration(): \Generator
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
     * Generate sorting configuration.
     */
    protected function generateSortingConfiguration(): \Generator
    {
        $columns = ['id', 'name', 'email', 'created_at'];
        $directions = ['asc', 'desc'];
        $sortableOptions = [null, false, ['id'], ['name'], ['id', 'name']];

        for ($i = 0; $i < 100; $i++) {
            yield [
                'column' => $columns[array_rand($columns)],
                'direction' => $directions[array_rand($directions)],
                'sortable' => $sortableOptions[array_rand($sortableOptions)],
            ];
        }
    }

    /**
     * Generate search configuration.
     */
    protected function generateSearchConfiguration(): \Generator
    {
        $searchableOptions = [null, false, ['name'], ['email'], ['name', 'email']];

        for ($i = 0; $i < 100; $i++) {
            yield [
                'searchable' => $searchableOptions[array_rand($searchableOptions)],
            ];
        }
    }

    /**
     * Generate styling configuration.
     */
    protected function generateStylingConfiguration(): \Generator
    {
        $widths = [100, 500, 800, 1000, 1200];
        $units = ['px', '%', 'em', 'rem'];
        $columns = ['id', 'name', 'email'];
        $alignments = ['left', 'center', 'right'];

        for ($i = 0; $i < 100; $i++) {
            $config = [];

            if (rand(0, 1)) {
                $config['width'] = $widths[array_rand($widths)];
                $config['unit'] = $units[array_rand($units)];
            }

            if (rand(0, 1)) {
                $config['columnWidth'] = [
                    'column' => $columns[array_rand($columns)],
                    'width' => rand(50, 500),
                ];
            }

            if (rand(0, 1)) {
                $config['align'] = [
                    'alignment' => $alignments[array_rand($alignments)],
                    'columns' => rand(0, 1) ? [] : [$columns[array_rand($columns)]],
                ];
            }

            yield $config;
        }
    }

    /**
     * Generate actions configuration.
     */
    protected function generateActionsConfiguration(): \Generator
    {
        $actionsOptions = [
            true,
            false,
            [],
            [
                ['label' => 'View', 'url' => '/view/{id}', 'icon' => 'eye'],
                ['label' => 'Edit', 'url' => '/edit/{id}', 'icon' => 'pencil'],
            ],
        ];

        for ($i = 0; $i < 100; $i++) {
            yield $actionsOptions[array_rand($actionsOptions)];
        }
    }

    /**
     * Generate pagination configuration.
     */
    protected function generatePaginationConfiguration(): \Generator
    {
        $limits = [10, 25, 50, 100, 'all'];
        $serverSideOptions = [true, false];

        for ($i = 0; $i < 100; $i++) {
            yield [
                'limit' => $limits[array_rand($limits)],
                'serverSide' => $serverSideOptions[array_rand($serverSideOptions)],
            ];
        }
    }

    /**
     * Generate complex configuration.
     */
    protected function generateComplexConfiguration(): \Generator
    {
        for ($i = 0; $i < 100; $i++) {
            yield [
                'fields' => $this->randomFields(),
                'label' => $this->randomLabel(),
                'orderby' => [
                    'column' => $this->randomColumn(),
                    'direction' => $this->randomDirection(),
                ],
                'sortable' => $this->randomSortable(),
                'searchable' => $this->randomSearchable(),
                'clickable' => $this->randomClickable(),
                'width' => [
                    'value' => rand(500, 1500),
                    'unit' => ['px', '%', 'em'][array_rand(['px', '%', 'em'])],
                ],
                'align' => [
                    'alignment' => ['left', 'center', 'right'][array_rand(['left', 'center', 'right'])],
                    'columns' => [],
                    'header' => (bool) rand(0, 1),
                    'body' => (bool) rand(0, 1),
                ],
                'actions' => $this->randomActions(),
                'displayLimit' => $this->randomDisplayLimit(),
                'serverSide' => (bool) rand(0, 1),
                'urlValue' => 'id',
            ];
        }
    }

    /**
     * Helper methods for random data generation.
     */
    protected function randomFields(): array
    {
        $options = [
            ['id'],
            ['name'],
            ['email'],
            ['id', 'name'],
            ['id', 'email'],
            ['name', 'email'],
            ['id', 'name', 'email'],
            ['id', 'name', 'email', 'created_at'],
        ];

        return $options[array_rand($options)];
    }

    protected function randomLabel(): string
    {
        $labels = ['User Table', 'User List', 'Users', 'User Management', 'User Data'];

        return $labels[array_rand($labels)];
    }

    protected function randomColumn(): string
    {
        $columns = ['id', 'name', 'email', 'created_at', 'updated_at'];

        return $columns[array_rand($columns)];
    }

    protected function randomDirection(): string
    {
        return ['asc', 'desc'][array_rand(['asc', 'desc'])];
    }

    protected function randomSortable()
    {
        $options = [null, false, ['id'], ['name'], ['id', 'name']];

        return $options[array_rand($options)];
    }

    protected function randomSearchable()
    {
        $options = [null, false, ['name'], ['email'], ['name', 'email']];

        return $options[array_rand($options)];
    }

    protected function randomClickable()
    {
        $options = [null, false, ['name'], ['id', 'name']];

        return $options[array_rand($options)];
    }

    protected function randomActions()
    {
        $options = [true, false, []];

        return $options[array_rand($options)];
    }

    protected function randomDisplayLimit()
    {
        $limits = [10, 25, 50, 100, 'all'];

        return $limits[array_rand($limits)];
    }
}
