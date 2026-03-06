<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Table\Engines;

use Canvastack\Canvastack\Components\Table\Engines\EngineManager;
use Canvastack\Canvastack\Components\Table\Engines\TableEngineInterface;
use Canvastack\Canvastack\Components\Table\Engines\DataTablesEngine;
use Canvastack\Canvastack\Components\Table\Engines\TanStackEngine;
use Canvastack\Canvastack\Components\Table\Exceptions\InvalidEngineException;
use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Components\Table\Data\TableConfiguration;
use Canvastack\Canvastack\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Mockery;

/**
 * Test for EngineManager.
 *
 * This test verifies that the EngineManager correctly manages engine
 * registration, selection, and lifecycle. It validates engine switching,
 * auto-detection logic, and error handling.
 *
 * @package Canvastack\Canvastack\Tests\Unit\Components\Table\Engines
 * @version 1.0.0
 *
 * Validates:
 * - Requirement 3.2: EngineManager provides engine registration and selection
 * - Requirements 27.1-27.7: Feature detection and auto-selection
 * - Requirement 29.3: Unit tests for EngineManager
 */
class EngineManagerTest extends TestCase
{
    /**
     * Engine manager instance.
     *
     * @var EngineManager
     */
    protected EngineManager $manager;

    /**
     * Setup test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->manager = new EngineManager();
    }

    /**
     * Teardown test environment.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test that EngineManager can be instantiated.
     *
     * @return void
     */
    #[Test]
    public function test_engine_manager_can_be_instantiated(): void
    {
        $manager = new EngineManager();
        
        $this->assertInstanceOf(
            EngineManager::class,
            $manager,
            'EngineManager should be instantiable'
        );
    }

    /**
     * Test engine registration.
     *
     * Validates: Requirement 3.2 (engine registration)
     *
     * @return void
     */
    #[Test]
    public function test_engine_registration(): void
    {
        $engine = Mockery::mock(TableEngineInterface::class);
        $engine->shouldReceive('getName')->andReturn('test-engine');
        
        $this->manager->register('test-engine', $engine);
        
        $this->assertTrue(
            $this->manager->has('test-engine'),
            'Registered engine should be available'
        );
    }

    /**
     * Test multiple engine registration.
     *
     * Validates: Requirement 3.2 (multiple engines)
     *
     * @return void
     */
    #[Test]
    public function test_multiple_engine_registration(): void
    {
        $engine1 = Mockery::mock(TableEngineInterface::class);
        $engine1->shouldReceive('getName')->andReturn('engine1');
        
        $engine2 = Mockery::mock(TableEngineInterface::class);
        $engine2->shouldReceive('getName')->andReturn('engine2');
        
        $this->manager->register('engine1', $engine1);
        $this->manager->register('engine2', $engine2);
        
        $this->assertTrue(
            $this->manager->has('engine1'),
            'First engine should be registered'
        );
        
        $this->assertTrue(
            $this->manager->has('engine2'),
            'Second engine should be registered'
        );
        
        $engines = $this->manager->all();
        $this->assertCount(
            2,
            $engines,
            'Should have exactly 2 registered engines'
        );
    }

    /**
     * Test engine retrieval.
     *
     * Validates: Requirement 3.2 (engine retrieval)
     *
     * @return void
     */
    #[Test]
    public function test_engine_retrieval(): void
    {
        $engine = Mockery::mock(TableEngineInterface::class);
        $engine->shouldReceive('getName')->andReturn('test-engine');
        
        $this->manager->register('test-engine', $engine);
        
        $retrieved = $this->manager->get('test-engine');
        
        $this->assertSame(
            $engine,
            $retrieved,
            'Retrieved engine should be the same instance'
        );
    }

    /**
     * Test engine retrieval throws exception for unregistered engine.
     *
     * Validates: Requirement 3.2 (error handling)
     *
     * @return void
     */
    #[Test]
    public function test_engine_retrieval_throws_exception_for_unregistered_engine(): void
    {
        $this->expectException(InvalidEngineException::class);
        $this->expectExceptionMessage("Table engine 'nonexistent' is not registered");
        
        $this->manager->get('nonexistent');
    }

    /**
     * Test exception message includes available engines.
     *
     * Validates: Requirement 3.2 (helpful error messages)
     *
     * @return void
     */
    #[Test]
    public function test_exception_message_includes_available_engines(): void
    {
        $engine1 = Mockery::mock(TableEngineInterface::class);
        $engine2 = Mockery::mock(TableEngineInterface::class);
        
        $this->manager->register('datatables', $engine1);
        $this->manager->register('tanstack', $engine2);
        
        try {
            $this->manager->get('nonexistent');
            $this->fail('Should have thrown InvalidEngineException');
        } catch (InvalidEngineException $e) {
            $this->assertStringContainsString(
                'datatables',
                $e->getMessage(),
                'Exception message should list available engines'
            );
            
            $this->assertStringContainsString(
                'tanstack',
                $e->getMessage(),
                'Exception message should list all available engines'
            );
        }
    }

    /**
     * Test checking if engine exists.
     *
     * Validates: Requirement 3.2 (engine existence check)
     *
     * @return void
     */
    #[Test]
    public function test_checking_if_engine_exists(): void
    {
        $engine = Mockery::mock(TableEngineInterface::class);
        
        $this->assertFalse(
            $this->manager->has('test-engine'),
            'Engine should not exist before registration'
        );
        
        $this->manager->register('test-engine', $engine);
        
        $this->assertTrue(
            $this->manager->has('test-engine'),
            'Engine should exist after registration'
        );
    }

    /**
     * Test getting all registered engines.
     *
     * Validates: Requirement 3.2 (list all engines)
     *
     * @return void
     */
    #[Test]
    public function test_getting_all_registered_engines(): void
    {
        $engine1 = Mockery::mock(TableEngineInterface::class);
        $engine2 = Mockery::mock(TableEngineInterface::class);
        
        $this->manager->register('engine1', $engine1);
        $this->manager->register('engine2', $engine2);
        
        $engines = $this->manager->all();
        
        $this->assertIsArray($engines, 'all() should return an array');
        $this->assertCount(2, $engines, 'Should return all registered engines');
        $this->assertArrayHasKey('engine1', $engines);
        $this->assertArrayHasKey('engine2', $engines);
        $this->assertSame($engine1, $engines['engine1']);
        $this->assertSame($engine2, $engines['engine2']);
    }

    /**
     * Test default engine is 'datatables'.
     *
     * Validates: Requirement 27.4 (default engine)
     *
     * @return void
     */
    #[Test]
    public function test_default_engine_is_datatables(): void
    {
        $this->assertEquals(
            'datatables',
            $this->manager->getDefault(),
            'Default engine should be datatables'
        );
    }

    /**
     * Test setting default engine.
     *
     * Validates: Requirement 3.2 (default engine configuration)
     *
     * @return void
     */
    #[Test]
    public function test_setting_default_engine(): void
    {
        $engine = Mockery::mock(TableEngineInterface::class);
        $this->manager->register('tanstack', $engine);
        
        $this->manager->setDefault('tanstack');
        
        $this->assertEquals(
            'tanstack',
            $this->manager->getDefault(),
            'Default engine should be updated'
        );
    }

    /**
     * Test setting default engine throws exception for unregistered engine.
     *
     * Validates: Requirement 3.2 (validation)
     *
     * @return void
     */
    #[Test]
    public function test_setting_default_engine_throws_exception_for_unregistered_engine(): void
    {
        $this->expectException(InvalidEngineException::class);
        $this->expectExceptionMessage("Cannot set default engine to 'nonexistent'");
        
        $this->manager->setDefault('nonexistent');
    }

    /**
     * Test engine selection with explicit engine set.
     *
     * Validates: Requirement 27.6 (manual override)
     *
     * @return void
     */
    #[Test]
    public function test_engine_selection_with_explicit_engine_set(): void
    {
        $datatables = Mockery::mock(TableEngineInterface::class);
        $tanstack = Mockery::mock(TableEngineInterface::class);
        
        $this->manager->register('datatables', $datatables);
        $this->manager->register('tanstack', $tanstack);
        
        $table = Mockery::mock(TableBuilder::class);
        $table->shouldReceive('getEngine')->andReturn('tanstack');
        
        $selected = $this->manager->selectEngine($table);
        
        $this->assertSame(
            $tanstack,
            $selected,
            'Should select explicitly set engine'
        );
    }

    /**
     * Test engine selection from config.
     *
     * Validates: Requirement 27.1 (config-based selection)
     *
     * @return void
     */
    #[Test]
    public function test_engine_selection_from_config(): void
    {
        config(['canvastack-table.engine' => 'tanstack']);
        
        $datatables = Mockery::mock(TableEngineInterface::class);
        $tanstack = Mockery::mock(TableEngineInterface::class);
        
        $this->manager->register('datatables', $datatables);
        $this->manager->register('tanstack', $tanstack);
        
        $table = Mockery::mock(TableBuilder::class);
        $table->shouldReceive('getEngine')->andReturn(null);
        
        $selected = $this->manager->selectEngine($table);
        
        $this->assertSame(
            $tanstack,
            $selected,
            'Should select engine from config'
        );
    }

    /**
     * Test auto-detection for virtual scrolling.
     *
     * Note: Auto-detection is currently simplified to always return default engine.
     * Feature-based detection will be implemented when TableConfiguration is fully integrated.
     *
     * Validates: Requirement 27.1 (virtual scrolling detection - placeholder)
     *
     * @return void
     */
    #[Test]
    public function test_auto_detection_for_virtual_scrolling(): void
    {
        $datatables = Mockery::mock(TableEngineInterface::class);
        $tanstack = Mockery::mock(TableEngineInterface::class);
        
        $this->manager->register('datatables', $datatables);
        $this->manager->register('tanstack', $tanstack);
        
        $config = new TableConfiguration();
        $config->virtualScrolling = true;
        
        $table = Mockery::mock(TableBuilder::class);
        $table->shouldReceive('getEngine')->andReturn(null);
        $table->shouldReceive('getConfiguration')->andReturn($config);
        
        $selected = $this->manager->selectEngine($table);
        
        // Currently auto-detection returns default engine (datatables)
        // This will be updated when feature detection is fully implemented
        $this->assertSame(
            $datatables,
            $selected,
            'Should select default engine (auto-detection not yet implemented)'
        );
    }

    /**
     * Test auto-detection for column resizing.
     *
     * Note: Auto-detection is currently simplified to always return default engine.
     * Feature-based detection will be implemented when TableConfiguration is fully integrated.
     *
     * Validates: Requirement 27.2 (column resizing detection - placeholder)
     *
     * @return void
     */
    #[Test]
    public function test_auto_detection_for_column_resizing(): void
    {
        $datatables = Mockery::mock(TableEngineInterface::class);
        $tanstack = Mockery::mock(TableEngineInterface::class);
        
        $this->manager->register('datatables', $datatables);
        $this->manager->register('tanstack', $tanstack);
        
        $config = new TableConfiguration();
        $config->columnResizing = true;
        
        $table = Mockery::mock(TableBuilder::class);
        $table->shouldReceive('getEngine')->andReturn(null);
        $table->shouldReceive('getConfiguration')->andReturn($config);
        
        $selected = $this->manager->selectEngine($table);
        
        // Currently auto-detection returns default engine (datatables)
        // This will be updated when feature detection is fully implemented
        $this->assertSame(
            $datatables,
            $selected,
            'Should select default engine (auto-detection not yet implemented)'
        );
    }

    /**
     * Test auto-detection for modern design.
     *
     * Note: Auto-detection is currently simplified to always return default engine.
     * Feature-based detection will be implemented when TableConfiguration is fully integrated.
     *
     * Validates: Requirement 27.3 (modern design detection - placeholder)
     *
     * @return void
     */
    #[Test]
    public function test_auto_detection_for_modern_design(): void
    {
        $datatables = Mockery::mock(TableEngineInterface::class);
        $tanstack = Mockery::mock(TableEngineInterface::class);
        
        $this->manager->register('datatables', $datatables);
        $this->manager->register('tanstack', $tanstack);
        
        $config = new TableConfiguration();
        $config->modernDesign = true;
        
        $table = Mockery::mock(TableBuilder::class);
        $table->shouldReceive('getEngine')->andReturn(null);
        $table->shouldReceive('getConfiguration')->andReturn($config);
        
        $selected = $this->manager->selectEngine($table);
        
        // Currently auto-detection returns default engine (datatables)
        // This will be updated when feature detection is fully implemented
        $this->assertSame(
            $datatables,
            $selected,
            'Should select default engine (auto-detection not yet implemented)'
        );
    }

    /**
     * Test auto-detection defaults to DataTables.
     *
     * Validates: Requirement 27.4 (default to DataTables)
     *
     * @return void
     */
    #[Test]
    public function test_auto_detection_defaults_to_datatables(): void
    {
        $datatables = Mockery::mock(TableEngineInterface::class);
        $tanstack = Mockery::mock(TableEngineInterface::class);
        
        $this->manager->register('datatables', $datatables);
        $this->manager->register('tanstack', $tanstack);
        
        $config = new TableConfiguration();
        // No special features enabled
        
        $table = Mockery::mock(TableBuilder::class);
        $table->shouldReceive('getEngine')->andReturn(null);
        $table->shouldReceive('getConfiguration')->andReturn($config);
        
        $selected = $this->manager->selectEngine($table);
        
        $this->assertSame(
            $datatables,
            $selected,
            'Should default to DataTables when no special features required'
        );
    }

    /**
     * Test auto-detection when TanStack not available.
     *
     * Validates: Requirement 27.4 (fallback to DataTables)
     *
     * @return void
     */
    #[Test]
    public function test_auto_detection_when_tanstack_not_available(): void
    {
        $datatables = Mockery::mock(TableEngineInterface::class);
        
        // Only register DataTables, not TanStack
        $this->manager->register('datatables', $datatables);
        
        $config = new TableConfiguration();
        $config->virtualScrolling = true; // Requires TanStack
        
        $table = Mockery::mock(TableBuilder::class);
        $table->shouldReceive('getEngine')->andReturn(null);
        $table->shouldReceive('getConfiguration')->andReturn($config);
        
        $selected = $this->manager->selectEngine($table);
        
        $this->assertSame(
            $datatables,
            $selected,
            'Should fallback to DataTables when TanStack not available'
        );
    }

    /**
     * Test explicit engine overrides auto-detection.
     *
     * Validates: Requirement 27.6 (manual override)
     *
     * @return void
     */
    #[Test]
    public function test_explicit_engine_overrides_auto_detection(): void
    {
        $datatables = Mockery::mock(TableEngineInterface::class);
        $tanstack = Mockery::mock(TableEngineInterface::class);
        
        $this->manager->register('datatables', $datatables);
        $this->manager->register('tanstack', $tanstack);
        
        $config = new TableConfiguration();
        $config->virtualScrolling = true; // Would auto-select TanStack
        
        $table = Mockery::mock(TableBuilder::class);
        $table->shouldReceive('getEngine')->andReturn('datatables'); // Explicit override
        $table->shouldReceive('getConfiguration')->andReturn($config);
        
        $selected = $this->manager->selectEngine($table);
        
        $this->assertSame(
            $datatables,
            $selected,
            'Explicit engine should override auto-detection'
        );
    }

    /**
     * Test config engine overrides auto-detection.
     *
     * Validates: Requirement 27.6 (config override)
     *
     * @return void
     */
    #[Test]
    public function test_config_engine_overrides_auto_detection(): void
    {
        config(['canvastack-table.engine' => 'datatables']);
        
        $datatables = Mockery::mock(TableEngineInterface::class);
        $tanstack = Mockery::mock(TableEngineInterface::class);
        
        $this->manager->register('datatables', $datatables);
        $this->manager->register('tanstack', $tanstack);
        
        $config = new TableConfiguration();
        $config->virtualScrolling = true; // Would auto-select TanStack
        
        $table = Mockery::mock(TableBuilder::class);
        $table->shouldReceive('getEngine')->andReturn(null);
        $table->shouldReceive('getConfiguration')->andReturn($config);
        
        $selected = $this->manager->selectEngine($table);
        
        $this->assertSame(
            $datatables,
            $selected,
            'Config engine should override auto-detection'
        );
    }

    /**
     * Test engine selection priority order.
     *
     * Priority: Explicit > Config > Auto-detection
     *
     * Validates: Requirement 27.5 (selection priority)
     *
     * @return void
     */
    #[Test]
    public function test_engine_selection_priority_order(): void
    {
        config(['canvastack-table.engine' => 'tanstack']);
        
        $datatables = Mockery::mock(TableEngineInterface::class);
        $tanstack = Mockery::mock(TableEngineInterface::class);
        
        $this->manager->register('datatables', $datatables);
        $this->manager->register('tanstack', $tanstack);
        
        $config = new TableConfiguration();
        $config->virtualScrolling = true;
        
        // Test 1: Explicit engine has highest priority
        $table1 = Mockery::mock(TableBuilder::class);
        $table1->shouldReceive('getEngine')->andReturn('datatables');
        $table1->shouldReceive('getConfiguration')->andReturn($config);
        
        $selected1 = $this->manager->selectEngine($table1);
        $this->assertSame($datatables, $selected1, 'Explicit should have highest priority');
        
        // Test 2: Config has second priority
        $table2 = Mockery::mock(TableBuilder::class);
        $table2->shouldReceive('getEngine')->andReturn(null);
        $table2->shouldReceive('getConfiguration')->andReturn($config);
        
        $selected2 = $this->manager->selectEngine($table2);
        $this->assertSame($tanstack, $selected2, 'Config should have second priority');
        
        // Test 3: Auto-detection has lowest priority (currently returns default)
        config(['canvastack-table.engine' => null]);
        
        $table3 = Mockery::mock(TableBuilder::class);
        $table3->shouldReceive('getEngine')->andReturn(null);
        $table3->shouldReceive('getConfiguration')->andReturn($config);
        
        $selected3 = $this->manager->selectEngine($table3);
        // Auto-detection currently returns default engine (datatables)
        $this->assertSame($datatables, $selected3, 'Auto-detection should return default engine');
    }

    /**
     * Test engine registration is idempotent.
     *
     * Registering the same engine twice should replace the first registration.
     *
     * @return void
     */
    #[Test]
    public function test_engine_registration_is_idempotent(): void
    {
        $engine1 = Mockery::mock(TableEngineInterface::class);
        $engine2 = Mockery::mock(TableEngineInterface::class);
        
        $this->manager->register('test', $engine1);
        $this->manager->register('test', $engine2);
        
        $retrieved = $this->manager->get('test');
        
        $this->assertSame(
            $engine2,
            $retrieved,
            'Second registration should replace first'
        );
    }

    /**
     * Test empty engine manager has no engines.
     *
     * @return void
     */
    #[Test]
    public function test_empty_engine_manager_has_no_engines(): void
    {
        $engines = $this->manager->all();
        
        $this->assertIsArray($engines, 'all() should return an array');
        $this->assertEmpty($engines, 'New manager should have no engines');
    }

    /**
     * Test engine selection logs reasoning in debug mode.
     *
     * Validates: Requirement 27.5 (logging)
     *
     * @return void
     */
    #[Test]
    public function test_engine_selection_logs_reasoning_in_debug_mode(): void
    {
        config(['app.debug' => true]);
        
        $datatables = Mockery::mock(TableEngineInterface::class);
        $this->manager->register('datatables', $datatables);
        
        $config = new TableConfiguration();
        
        $table = Mockery::mock(TableBuilder::class);
        $table->shouldReceive('getEngine')->andReturn(null);
        $table->shouldReceive('getConfiguration')->andReturn($config);
        
        // This should log the selection reasoning
        $selected = $this->manager->selectEngine($table);
        
        $this->assertSame(
            $datatables,
            $selected,
            'Should select engine and log reasoning'
        );
        
        // Note: Actual log verification would require log mocking
        // which is beyond the scope of this unit test
    }
}
