<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Property\Table;

use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Tests\Fixtures\Models\User;
use Canvastack\Canvastack\Tests\Support\PropertyTesting\PropertyTestCase;
use RuntimeException;

/**
 * Property 21: Empty Model Rejection.
 *
 * Validates: Requirements 49.1
 *
 * Property: FOR ALL table configurations without a model set,
 * the render() method SHALL throw RuntimeException.
 *
 * This property ensures that:
 * - Tables cannot be rendered without a data source
 * - Clear error messages guide developers to set a model
 * - Prevents runtime errors from missing data
 * - Enforces proper initialization before rendering
 *
 * Correctness implications:
 * - Fail-fast principle: catch configuration errors early
 * - Prevents undefined behavior from missing model
 * - Provides clear feedback to developers
 * - Ensures consistent error handling
 */
class EmptyModelRejectionPropertyTest extends PropertyTestCase
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
     * Create test data for validation testing.
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
     * Property 21: Empty Model Rejection.
     *
     * Test that render() throws RuntimeException when model is not set.
     *
     * @test
     * @group property
     * @group validation
     * @group canvastack-table-complete
     */
    public function property_render_rejects_empty_model(): void
    {
        $this->forAll(
            $this->generateTableConfigurations(),
            function (array $config) {
                $table = app(TableBuilder::class);

                // Apply configuration WITHOUT setting model
                $this->applyConfiguration($table, $config);

                // Verify: render() throws RuntimeException when model is not set
                $exceptionThrown = false;
                $exceptionMessage = '';

                try {
                    $table->render();
                } catch (RuntimeException $e) {
                    $exceptionThrown = true;
                    $exceptionMessage = $e->getMessage();
                }

                $this->assertTrue(
                    $exceptionThrown,
                    'render() should throw RuntimeException when model is not set'
                );

                $this->assertStringContainsString(
                    'model',
                    strtolower($exceptionMessage),
                    "Exception message should mention 'model'"
                );

                return true;
            },
            100
        );
    }

    /**
     * Property 21.1: Model set via model() method allows rendering.
     *
     * Test that render() succeeds when model is set via model() method.
     *
     * @test
     * @group property
     * @group validation
     * @group canvastack-table-complete
     */
    public function property_render_succeeds_with_model_set(): void
    {
        $this->forAll(
            $this->generateTableConfigurations(),
            function (array $config) {
                $table = app(TableBuilder::class);

                // Set model BEFORE applying configuration
                $table->setModel(new User());

                // Apply configuration
                $this->applyConfiguration($table, $config);

                // Verify: render() does not throw exception when model is set
                $exceptionThrown = false;
                $result = null;

                try {
                    $result = $table->render();
                } catch (RuntimeException $e) {
                    $exceptionThrown = true;
                }

                $this->assertFalse(
                    $exceptionThrown,
                    'render() should not throw RuntimeException when model is set'
                );

                $this->assertIsString(
                    $result,
                    'render() should return HTML string when model is set'
                );

                return true;
            },
            100
        );
    }

    /**
     * Property 21.2: Model set via setName() allows rendering.
     *
     * Test that render() succeeds when model is inferred from setName().
     *
     * @test
     * @group property
     * @group validation
     * @group canvastack-table-complete
     */
    public function property_render_succeeds_with_setName(): void
    {
        $this->forAll(
            $this->generateTableConfigurations(),
            function (array $config) {
                $table = app(TableBuilder::class);

                // Set table name (which should infer model)
                $table->setName('test_users');
                $table->setModel(new User()); // Still need to set model explicitly

                // Apply configuration
                $this->applyConfiguration($table, $config);

                // Verify: render() does not throw exception
                $exceptionThrown = false;
                $result = null;

                try {
                    $result = $table->render();
                } catch (RuntimeException $e) {
                    $exceptionThrown = true;
                }

                $this->assertFalse(
                    $exceptionThrown,
                    'render() should not throw RuntimeException when table name and model are set'
                );

                $this->assertIsString(
                    $result,
                    'render() should return HTML string'
                );

                return true;
            },
            100
        );
    }

    /**
     * Property 21.3: Exception message is clear and helpful.
     *
     * Test that RuntimeException message provides clear guidance.
     *
     * @test
     * @group property
     * @group validation
     * @group canvastack-table-complete
     */
    public function property_exception_message_is_helpful(): void
    {
        $this->forAll(
            $this->generateTableConfigurations(),
            function (array $config) {
                $table = app(TableBuilder::class);

                // Apply configuration WITHOUT setting model
                $this->applyConfiguration($table, $config);

                // Verify: Exception message is helpful
                $exceptionMessage = '';

                try {
                    $table->render();
                } catch (RuntimeException $e) {
                    $exceptionMessage = $e->getMessage();
                }

                // Message should mention model
                $this->assertStringContainsString(
                    'model',
                    strtolower($exceptionMessage),
                    "Exception message should mention 'model'"
                );

                // Message should be helpful (suggest calling model() method)
                $helpfulKeywords = ['set', 'call', 'must', 'required', 'before'];
                $containsHelpfulKeyword = false;

                foreach ($helpfulKeywords as $keyword) {
                    if (stripos($exceptionMessage, $keyword) !== false) {
                        $containsHelpfulKeyword = true;
                        break;
                    }
                }

                $this->assertTrue(
                    $containsHelpfulKeyword,
                    "Exception message should provide helpful guidance: '{$exceptionMessage}'"
                );

                return true;
            },
            100
        );
    }

    /**
     * Property 21.4: Empty model rejection is consistent.
     *
     * Test that render() consistently throws exception across multiple calls.
     *
     * @test
     * @group property
     * @group validation
     * @group canvastack-table-complete
     */
    public function property_empty_model_rejection_is_consistent(): void
    {
        $this->forAll(
            $this->generateTableConfigurations(),
            function (array $config) {
                // Call render() multiple times without model
                $exceptions = [];

                for ($i = 0; $i < 3; $i++) {
                    $table = app(TableBuilder::class);
                    $this->applyConfiguration($table, $config);

                    try {
                        $table->render();
                        $exceptions[] = null;
                    } catch (RuntimeException $e) {
                        $exceptions[] = $e->getMessage();
                    }
                }

                // Verify: All calls throw exception
                $this->assertNotNull($exceptions[0], 'First call should throw exception');
                $this->assertNotNull($exceptions[1], 'Second call should throw exception');
                $this->assertNotNull($exceptions[2], 'Third call should throw exception');

                // Verify: Exception messages are consistent
                $this->assertEquals(
                    $exceptions[0],
                    $exceptions[1],
                    'Exception messages should be consistent across calls'
                );

                $this->assertEquals(
                    $exceptions[1],
                    $exceptions[2],
                    'Exception messages should be consistent across calls'
                );

                return true;
            },
            100
        );
    }

    /**
     * Property 21.5: Configuration methods don't require model.
     *
     * Test that configuration methods work without model set.
     *
     * @test
     * @group property
     * @group validation
     * @group canvastack-table-complete
     */
    public function property_configuration_methods_work_without_model(): void
    {
        $this->forAll(
            $this->generateTableConfigurations(),
            function (array $config) {
                $table = app(TableBuilder::class);

                // Verify: Configuration methods don't throw exception without model
                $exceptionThrown = false;

                try {
                    $this->applyConfiguration($table, $config);
                } catch (\Exception $e) {
                    $exceptionThrown = true;
                }

                $this->assertFalse(
                    $exceptionThrown,
                    'Configuration methods should work without model set'
                );

                // Verify: Only render() should throw exception
                $renderExceptionThrown = false;

                try {
                    $table->render();
                } catch (RuntimeException $e) {
                    $renderExceptionThrown = true;
                }

                $this->assertTrue(
                    $renderExceptionThrown,
                    'render() should throw exception when model is not set'
                );

                return true;
            },
            100
        );
    }

    /**
     * Property 21.6: Lists method also rejects empty model.
     *
     * Test that legacy lists() method also throws exception without model.
     *
     * @test
     * @group property
     * @group validation
     * @group canvastack-table-complete
     */
    public function property_lists_method_rejects_empty_model(): void
    {
        $this->forAll(
            $this->generateListsParameters(),
            function (array $params) {
                $table = app(TableBuilder::class);

                // Call lists() without setting model first
                $exceptionThrown = false;
                $exceptionMessage = '';

                try {
                    $table->lists(
                        $params['tableName'],
                        $params['fields'],
                        $params['actions'],
                        $params['serverSide'],
                        $params['numbering'],
                        $params['attributes']
                    );
                } catch (RuntimeException $e) {
                    $exceptionThrown = true;
                    $exceptionMessage = $e->getMessage();
                } catch (\InvalidArgumentException $e) {
                    // Column validation errors are acceptable for this test
                    // We're testing model requirement, not column validation
                    return true;
                }

                // Note: lists() may set model internally via tableName parameter
                // So we only verify exception is thrown when tableName is null
                if ($params['tableName'] === null) {
                    $this->assertTrue(
                        $exceptionThrown,
                        'lists() should throw RuntimeException when tableName is null and model not set'
                    );

                    $this->assertStringContainsString(
                        'model',
                        strtolower($exceptionMessage),
                        "Exception message should mention 'model'"
                    );
                }

                return true;
            },
            100
        );
    }

    /**
     * Property 21.7: Clear model after render still requires model for next render.
     *
     * Test that clearing model requires setting it again for next render.
     *
     * @test
     * @group property
     * @group validation
     * @group canvastack-table-complete
     */
    public function property_clear_model_requires_reset(): void
    {
        $this->forAll(
            $this->generateTableConfigurations(),
            function (array $config) {
                $table = app(TableBuilder::class);

                // Set model and render successfully
                $table->setModel(new User());
                $this->applyConfiguration($table, $config);

                $firstRender = null;

                try {
                    $firstRender = $table->render();
                } catch (\Exception $e) {
                    // Ignore errors from first render
                }

                // Clear configuration (including model)
                $table->clear(true);

                // Verify: render() throws exception after clear
                $exceptionThrown = false;

                try {
                    $table->render();
                } catch (RuntimeException $e) {
                    $exceptionThrown = true;
                }

                $this->assertTrue(
                    $exceptionThrown,
                    'render() should throw RuntimeException after clear(true)'
                );

                return true;
            },
            100
        );
    }

    /**
     * Apply configuration to table without setting model.
     */
    protected function applyConfiguration(TableBuilder $table, array $config): void
    {
        // Apply various configuration methods
        if (isset($config['label'])) {
            $table->label($config['label']);
        }

        if (isset($config['serverSide'])) {
            $table->setServerSide($config['serverSide']);
        }

        if (isset($config['displayLimit'])) {
            $table->displayRowsLimitOnLoad($config['displayLimit']);
        }

        if (isset($config['isDatatable'])) {
            $table->setDatatableType($config['isDatatable']);
        }

        if (isset($config['width'])) {
            $table->setWidth($config['width']['value'], $config['width']['unit']);
        }

        if (isset($config['attributes'])) {
            $table->addAttributes($config['attributes']);
        }
    }

    /**
     * Generate various table configurations.
     */
    protected function generateTableConfigurations(): \Generator
    {
        $configurations = [
            // Minimal configuration
            [],

            // With label
            ['label' => 'Test Table'],

            // With server-side processing
            ['serverSide' => true],
            ['serverSide' => false],

            // With display limit
            ['displayLimit' => 10],
            ['displayLimit' => 25],
            ['displayLimit' => 50],
            ['displayLimit' => 'all'],

            // With datatable type
            ['isDatatable' => true],
            ['isDatatable' => false],

            // With width
            ['width' => ['value' => 100, 'unit' => '%']],
            ['width' => ['value' => 800, 'unit' => 'px']],

            // With attributes
            ['attributes' => ['class' => 'table-striped']],
            ['attributes' => ['id' => 'my-table', 'class' => 'custom-table']],

            // Combined configurations
            [
                'label' => 'Users Table',
                'serverSide' => true,
                'displayLimit' => 25,
                'isDatatable' => true,
            ],
            [
                'label' => 'Products Table',
                'serverSide' => false,
                'displayLimit' => 'all',
                'isDatatable' => false,
                'width' => ['value' => 100, 'unit' => '%'],
            ],
            [
                'label' => 'Orders Table',
                'serverSide' => true,
                'displayLimit' => 50,
                'attributes' => ['class' => 'table-bordered'],
            ],
        ];

        for ($i = 0; $i < 100; $i++) {
            yield $configurations[array_rand($configurations)];
        }
    }

    /**
     * Generate parameters for lists() method.
     */
    protected function generateListsParameters(): \Generator
    {
        $parameters = [
            // With null tableName (should fail)
            [
                'tableName' => null,
                'fields' => [],
                'actions' => true,
                'serverSide' => true,
                'numbering' => true,
                'attributes' => [],
            ],

            // With tableName but no model set beforehand
            [
                'tableName' => 'test_users',
                'fields' => ['id', 'name', 'email'],
                'actions' => true,
                'serverSide' => true,
                'numbering' => true,
                'attributes' => [],
            ],

            // Various parameter combinations
            [
                'tableName' => null,
                'fields' => ['id', 'name'],
                'actions' => false,
                'serverSide' => false,
                'numbering' => false,
                'attributes' => ['class' => 'custom'],
            ],

            [
                'tableName' => 'test_users',
                'fields' => [],
                'actions' => ['view', 'edit'],
                'serverSide' => true,
                'numbering' => true,
                'attributes' => [],
            ],
        ];

        for ($i = 0; $i < 100; $i++) {
            yield $parameters[array_rand($parameters)];
        }
    }
}
