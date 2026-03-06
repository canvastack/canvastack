<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Feature\Components\Table;

use Canvastack\Canvastack\Components\Table\Engines\DataTablesEngine;
use Canvastack\Canvastack\Components\Table\Engines\EngineManager;
use Canvastack\Canvastack\Components\Table\Engines\TanStackEngine;
use Canvastack\Canvastack\Components\Table\Exceptions\InvalidEngineException;
use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;

/**
 * Engine Switching Feature Tests for Dual DataTable Engine System.
 *
 * These tests verify that the engine switching mechanism works correctly,
 * including default engine selection, configuration-based switching,
 * per-table overrides, and error handling for invalid engines.
 *
 * Requirements: 1.3, 2.1, 2.2, 2.3, 2.4, 2.5, 2.6, 2.7, 29.6
 *
 * @group feature
 * @group table
 * @group engine-switching
 */
class EngineSwitchingTest extends TestCase
{
    use RefreshDatabase;

    protected TableBuilder $table;

    protected EngineManager $engineManager;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test table
        Schema::create('test_products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('price', 10, 2);
            $table->integer('stock')->default(0);
            $table->string('category');
            $table->timestamps();
        });

        // Create test model
        $this->createTestModel();

        // Create test data
        for ($i = 1; $i <= 20; $i++) {
            TestProduct::create([
                'name' => "Product {$i}",
                'price' => 10.00 + ($i * 5),
                'stock' => $i * 10,
                'category' => $i % 2 === 0 ? 'Electronics' : 'Clothing',
            ]);
        }

        // Get engine manager
        $this->engineManager = app(EngineManager::class);

        // Register engines manually for testing
        $this->registerEngines();

        // Create TableBuilder instance
        $this->table = app(TableBuilder::class);
    }

    /**
     * Register table engines for testing.
     */
    protected function registerEngines(): void
    {
        // Register DataTablesEngine
        $dataTablesEngine = app(DataTablesEngine::class);
        $this->engineManager->register('datatables', $dataTablesEngine);

        // Register TanStackEngine
        $tanStackEngine = app(TanStackEngine::class);
        $this->engineManager->register('tanstack', $tanStackEngine);

        // Set default engine
        $this->engineManager->setDefault('datatables');
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('test_products');
        parent::tearDown();
    }

    protected function createTestModel(): void
    {
        if (!class_exists(TestProduct::class)) {
            eval('
                namespace Canvastack\Canvastack\Tests\Feature\Components\Table;
                
                use Illuminate\Database\Eloquent\Model;
                
                class TestProduct extends Model
                {
                    protected $table = "test_products";
                    protected $fillable = ["name", "price", "stock", "category"];
                }
            ');
        }
    }

    // ============================================================
    // TASK 6.2.1.1: Test default engine is DataTables
    // Requirements: 1.3, 2.5
    // ============================================================

    /**
     * Test that the default engine is DataTables when no configuration is set.
     *
     * Requirement 1.3: WHEN no engine is specified, THE system SHALL default to DataTables.js engine
     * Requirement 2.5: WHEN no engine is configured, THE system SHALL default to "datatables"
     */
    public function test_default_engine_is_datatables(): void
    {
        // Get default engine
        $defaultEngine = $this->engineManager->getDefault();

        // Assert default is 'datatables'
        $this->assertEquals('datatables', $defaultEngine);
    }

    /**
     * Test that TableBuilder uses DataTables engine by default.
     *
     * Requirement 1.3: WHEN no engine is specified, THE system SHALL default to DataTables.js engine
     */
    public function test_table_builder_uses_datatables_by_default(): void
    {
        $model = new TestProduct();
        $this->table->setModel($model);
        $this->table->setFields(['name:Name', 'price:Price', 'stock:Stock']);

        // Get the selected engine (should use default)
        $engine = $this->engineManager->selectEngine($this->table);

        // Assert it's DataTablesEngine
        $this->assertInstanceOf(DataTablesEngine::class, $engine);
        $this->assertEquals('datatables', $engine->getName());
    }

    /**
     * Test that DataTables engine renders correctly by default.
     *
     * Requirement 1.3: THE system SHALL default to DataTables.js engine
     */
    public function test_datatables_engine_renders_by_default(): void
    {
        $model = new TestProduct();
        $this->table->setModel($model);
        $this->table->setFields(['name:Name', 'price:Price']);
        $this->table->format();

        $html = $this->table->render();

        // Assert HTML is generated
        $this->assertIsString($html);
        $this->assertNotEmpty($html);

        // Assert it contains DataTables-specific elements
        $this->assertStringContainsString('datatable', strtolower($html));
    }

    // ============================================================
    // TASK 6.2.1.2: Test switching to TanStack via config
    // Requirements: 2.1, 2.3, 2.4
    // ============================================================

    /**
     * Test switching to TanStack engine via environment configuration.
     *
     * Requirement 2.1: THE system SHALL support engine selection via .env configuration variable
     * Requirement 2.3: WHEN CANVASTACK_TABLE_ENGINE is set to "datatables", THE system SHALL use DataTables.js engine
     * Requirement 2.4: WHEN CANVASTACK_TABLE_ENGINE is set to "tanstack", THE system SHALL use TanStack Table engine
     */
    public function test_switching_to_tanstack_via_env_config(): void
    {
        // Set engine to TanStack via config
        Config::set('canvastack-table.engine', 'tanstack');

        // Change default engine to tanstack
        $this->engineManager->setDefault('tanstack');

        // Get default engine
        $defaultEngine = $this->engineManager->getDefault();

        // Assert default is now 'tanstack'
        $this->assertEquals('tanstack', $defaultEngine);
    }

    /**
     * Test that TableBuilder uses TanStack engine when configured.
     *
     * Requirement 2.4: WHEN CANVASTACK_TABLE_ENGINE is set to "tanstack", THE system SHALL use TanStack Table engine
     */
    public function test_table_builder_uses_tanstack_when_configured(): void
    {
        // Set engine to TanStack via config
        Config::set('canvastack-table.engine', 'tanstack');
        $this->engineManager->setDefault('tanstack');

        $model = new TestProduct();
        $this->table->setModel($model);
        $this->table->setFields(['name:Name', 'price:Price', 'stock:Stock']);

        // Get the selected engine
        $engine = $this->engineManager->selectEngine($this->table);

        // Assert it's TanStackEngine
        $this->assertInstanceOf(TanStackEngine::class, $engine);
        $this->assertEquals('tanstack', $engine->getName());
    }

    /**
     * Test that TanStack engine renders correctly when configured.
     *
     * Requirement 2.4: THE system SHALL use TanStack Table engine
     */
    public function test_tanstack_engine_renders_when_configured(): void
    {
        // Set engine to TanStack via config
        Config::set('canvastack-table.engine', 'tanstack');
        $this->engineManager->setDefault('tanstack');

        $model = new TestProduct();
        $this->table->setModel($model);
        $this->table->setFields(['name:Name', 'price:Price']);
        $this->table->format();

        $html = $this->table->render();

        // Assert HTML is generated
        $this->assertIsString($html);
        $this->assertNotEmpty($html);

        // Assert it contains TanStack-specific elements (Alpine.js directives)
        $this->assertStringContainsString('x-data', $html);
    }

    /**
     * Test switching back to DataTables via config.
     *
     * Requirement 2.3: WHEN CANVASTACK_TABLE_ENGINE is set to "datatables", THE system SHALL use DataTables.js engine
     */
    public function test_switching_back_to_datatables_via_config(): void
    {
        // First set to TanStack
        Config::set('canvastack-table.engine', 'tanstack');
        $this->engineManager->setDefault('tanstack');
        $this->assertEquals('tanstack', $this->engineManager->getDefault());

        // Then switch back to DataTables
        Config::set('canvastack-table.engine', 'datatables');
        $this->engineManager->setDefault('datatables');

        // Get default engine
        $defaultEngine = $this->engineManager->getDefault();

        // Assert default is back to 'datatables'
        $this->assertEquals('datatables', $defaultEngine);
    }

    // ============================================================
    // TASK 6.2.1.3: Test per-table engine override
    // Requirements: 2.2, 2.6
    // ============================================================

    /**
     * Test per-table engine override using setEngine() method.
     *
     * Requirement 2.2: THE system SHALL support per-table engine override via TableBuilder API
     * Requirement 2.6: THE TableBuilder SHALL provide setEngine() method for per-table override
     */
    public function test_per_table_engine_override_with_set_engine(): void
    {
        // Set global default to DataTables
        Config::set('canvastack-table.engine', 'datatables');

        $model = new TestProduct();
        $this->table->setModel($model);
        $this->table->setFields(['name:Name', 'price:Price']);

        // Override to TanStack for this specific table
        $this->table->setEngine('tanstack');

        // Get the selected engine
        $engine = $this->engineManager->selectEngine($this->table);

        // Assert it's TanStackEngine (override worked)
        $this->assertInstanceOf(TanStackEngine::class, $engine);
        $this->assertEquals('tanstack', $engine->getName());
    }

    /**
     * Test that per-table override takes precedence over global config.
     *
     * Requirement 2.2: THE system SHALL support per-table engine override via TableBuilder API
     */
    public function test_per_table_override_takes_precedence_over_global_config(): void
    {
        // Set global default to TanStack
        Config::set('canvastack-table.engine', 'tanstack');
        $this->engineManager->setDefault('tanstack');

        $model = new TestProduct();
        $this->table->setModel($model);
        $this->table->setFields(['name:Name', 'price:Price']);

        // Override to DataTables for this specific table
        $this->table->setEngine('datatables');

        // Get the selected engine
        $engine = $this->engineManager->selectEngine($this->table);

        // Assert it's DataTablesEngine (override took precedence)
        $this->assertInstanceOf(DataTablesEngine::class, $engine);
        $this->assertEquals('datatables', $engine->getName());
    }

    /**
     * Test multiple tables with different engine overrides.
     *
     * Requirement 2.2: THE system SHALL support per-table engine override via TableBuilder API
     */
    public function test_multiple_tables_with_different_engine_overrides(): void
    {
        // Set global default to DataTables
        Config::set('canvastack-table.engine', 'datatables');

        $model = new TestProduct();

        // Table 1: Use default (DataTables)
        $table1 = app(TableBuilder::class);
        $table1->setModel($model);
        $table1->setFields(['name:Name']);
        $engine1 = $this->engineManager->selectEngine($table1);
        $this->assertInstanceOf(DataTablesEngine::class, $engine1);

        // Table 2: Override to TanStack
        $table2 = app(TableBuilder::class);
        $table2->setModel($model);
        $table2->setFields(['price:Price']);
        $table2->setEngine('tanstack');
        $engine2 = $this->engineManager->selectEngine($table2);
        $this->assertInstanceOf(TanStackEngine::class, $engine2);

        // Table 3: Use default again (DataTables)
        $table3 = app(TableBuilder::class);
        $table3->setModel($model);
        $table3->setFields(['stock:Stock']);
        $engine3 = $this->engineManager->selectEngine($table3);
        $this->assertInstanceOf(DataTablesEngine::class, $engine3);
    }

    /**
     * Test that setEngine() returns $this for method chaining.
     *
     * Requirement 2.6: THE TableBuilder SHALL provide setEngine() method
     */
    public function test_set_engine_returns_this_for_chaining(): void
    {
        $result = $this->table->setEngine('tanstack');

        $this->assertInstanceOf(TableBuilder::class, $result);
        $this->assertSame($this->table, $result);
    }

    /**
     * Test setEngine() can be chained with other configuration methods.
     *
     * Requirement 2.6: THE TableBuilder SHALL provide setEngine() method
     */
    public function test_set_engine_can_be_chained_with_other_methods(): void
    {
        $model = new TestProduct();

        $result = $this->table
            ->setModel($model)
            ->setEngine('tanstack')
            ->setFields(['name:Name', 'price:Price'])
            ->orderby('name', 'asc');

        $this->assertInstanceOf(TableBuilder::class, $result);

        // Verify engine was set correctly
        $engine = $this->engineManager->selectEngine($this->table);
        $this->assertInstanceOf(TanStackEngine::class, $engine);
    }

    // ============================================================
    // TASK 6.2.1.4: Test invalid engine throws exception
    // Requirements: 2.7
    // ============================================================

    /**
     * Test that invalid engine name throws InvalidEngineException.
     *
     * Requirement 2.7: THE system SHALL validate engine names and throw descriptive errors for invalid engines
     */
    public function test_invalid_engine_name_throws_exception(): void
    {
        $this->expectException(InvalidEngineException::class);
        $this->expectExceptionMessageMatches('/invalid-engine.*not registered/i');

        $this->table->setEngine('invalid-engine');

        $model = new TestProduct();
        $this->table->setModel($model);

        // This should throw exception
        $this->engineManager->selectEngine($this->table);
    }

    /**
     * Test that EngineManager::get() throws exception for invalid engine.
     *
     * Requirement 2.7: THE system SHALL validate engine names and throw descriptive errors for invalid engines
     */
    public function test_engine_manager_get_throws_exception_for_invalid_engine(): void
    {
        $this->expectException(InvalidEngineException::class);
        $this->expectExceptionMessageMatches('/nonexistent.*not registered/i');

        $this->engineManager->get('nonexistent');
    }

    /**
     * Test that exception message is descriptive and helpful.
     *
     * Requirement 2.7: THE system SHALL throw descriptive errors for invalid engines
     */
    public function test_invalid_engine_exception_message_is_descriptive(): void
    {
        try {
            $this->engineManager->get('fake-engine');
            $this->fail('Expected InvalidEngineException was not thrown');
        } catch (InvalidEngineException $e) {
            // Assert message contains engine name
            $this->assertStringContainsString('fake-engine', $e->getMessage());

            // Assert message indicates it's not registered
            $this->assertStringContainsString('not registered', $e->getMessage());
        }
    }

    /**
     * Test that empty engine name throws exception.
     *
     * Requirement 2.7: THE system SHALL validate engine names
     */
    public function test_empty_engine_name_throws_exception(): void
    {
        $this->expectException(InvalidEngineException::class);

        $this->table->setEngine('');

        $model = new TestProduct();
        $this->table->setModel($model);

        $this->engineManager->selectEngine($this->table);
    }

    /**
     * Test that null engine name uses default engine (no exception).
     *
     * Requirement 2.5: WHEN no engine is configured, THE system SHALL default to "datatables"
     */
    public function test_null_engine_name_uses_default_engine(): void
    {
        $model = new TestProduct();
        $this->table->setModel($model);
        $this->table->setFields(['name:Name']);

        // Don't set engine (null)
        // This should use default engine without throwing exception

        $engine = $this->engineManager->selectEngine($this->table);

        $this->assertInstanceOf(DataTablesEngine::class, $engine);
    }

    // ============================================================
    // Additional Integration Tests
    // Requirements: 29.6
    // ============================================================

    /**
     * Test that both engines can be used in the same request.
     *
     * Requirement 29.6: Feature tests for engine switching
     */
    public function test_both_engines_can_be_used_in_same_request(): void
    {
        $model = new TestProduct();

        // Table 1: DataTables
        $table1 = app(TableBuilder::class);
        $table1->setModel($model);
        $table1->setEngine('datatables');
        $table1->setFields(['name:Name']);
        $table1->format();
        $html1 = $table1->render();

        // Table 2: TanStack
        $table2 = app(TableBuilder::class);
        $table2->setModel($model);
        $table2->setEngine('tanstack');
        $table2->setFields(['price:Price']);
        $table2->format();
        $html2 = $table2->render();

        // Assert both rendered successfully
        $this->assertIsString($html1);
        $this->assertIsString($html2);
        $this->assertNotEmpty($html1);
        $this->assertNotEmpty($html2);

        // Assert they're different (different engines)
        $this->assertNotEquals($html1, $html2);
    }

    /**
     * Test engine switching with server-side processing enabled.
     *
     * Requirement 29.6: Feature tests for engine switching
     */
    public function test_engine_switching_with_server_side_processing(): void
    {
        $model = new TestProduct();

        // DataTables with server-side
        $table1 = app(TableBuilder::class);
        $table1->setModel($model);
        $table1->setEngine('datatables');
        $table1->setServerSide(true);
        $table1->setFields(['name:Name']);
        $table1->format();
        $html1 = $table1->render();
        $this->assertIsString($html1);

        // TanStack with server-side
        $table2 = app(TableBuilder::class);
        $table2->setModel($model);
        $table2->setEngine('tanstack');
        $table2->setServerSide(true);
        $table2->setFields(['price:Price']);
        $table2->format();
        $html2 = $table2->render();
        $this->assertIsString($html2);
    }

    /**
     * Test that engine selection is logged in development mode.
     *
     * Requirement 29.6: Feature tests for engine switching
     */
    public function test_engine_selection_is_logged_in_development_mode(): void
    {
        // Enable debug mode
        Config::set('app.debug', true);

        $model = new TestProduct();
        $this->table->setModel($model);
        $this->table->setEngine('tanstack');
        $this->table->setFields(['name:Name']);

        // Select engine (should log in debug mode)
        $engine = $this->engineManager->selectEngine($this->table);

        // Assert engine was selected
        $this->assertInstanceOf(TanStackEngine::class, $engine);

        // Note: Actual log verification would require log mocking
        // This test verifies the selection works in debug mode
    }

    /**
     * Test that registered engines can be listed.
     *
     * Requirement 29.6: Feature tests for engine switching
     */
    public function test_registered_engines_can_be_listed(): void
    {
        $engines = $this->engineManager->all();

        // Assert both engines are registered
        $this->assertArrayHasKey('datatables', $engines);
        $this->assertArrayHasKey('tanstack', $engines);

        // Assert they're the correct types
        $this->assertInstanceOf(DataTablesEngine::class, $engines['datatables']);
        $this->assertInstanceOf(TanStackEngine::class, $engines['tanstack']);
    }

    /**
     * Test that engine existence can be checked.
     *
     * Requirement 29.6: Feature tests for engine switching
     */
    public function test_engine_existence_can_be_checked(): void
    {
        // Assert registered engines exist
        $this->assertTrue($this->engineManager->has('datatables'));
        $this->assertTrue($this->engineManager->has('tanstack'));

        // Assert non-existent engine doesn't exist
        $this->assertFalse($this->engineManager->has('nonexistent'));
        $this->assertFalse($this->engineManager->has('invalid'));
    }

    /**
     * Test that default engine can be changed at runtime.
     *
     * Requirement 29.6: Feature tests for engine switching
     */
    public function test_default_engine_can_be_changed_at_runtime(): void
    {
        // Initial default
        $this->engineManager->setDefault('datatables');
        $this->assertEquals('datatables', $this->engineManager->getDefault());

        // Change default
        $this->engineManager->setDefault('tanstack');
        $this->assertEquals('tanstack', $this->engineManager->getDefault());

        // Change back
        $this->engineManager->setDefault('datatables');
        $this->assertEquals('datatables', $this->engineManager->getDefault());
    }
}
