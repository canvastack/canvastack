<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Table\Engines;

use Canvastack\Canvastack\Components\Table\Engines\DataTablesEngine;
use Canvastack\Canvastack\Components\Table\Renderers\AdminRenderer;
use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;

/**
 * Test DataTables Row Selection Implementation
 *
 * Validates Requirements:
 * - 16.1: THE system SHALL support single row selection
 * - 16.2: THE system SHALL support multiple row selection
 * - 16.3: THE system SHALL provide select all checkbox
 * - 16.6: THE DataTablesEngine SHALL use DataTables.js select extension
 */
class DataTablesEngineRowSelectionTest extends TestCase
{
    protected DataTablesEngine $engine;
    protected TableBuilder $table;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test table
        $this->createTestTable();

        $renderer = new AdminRenderer();
        $this->engine = new DataTablesEngine($renderer);
        $this->table = $this->createTableBuilder();
    }

    /**
     * Create test table in database.
     */
    protected function createTestTable(): void
    {
        $capsule = \Illuminate\Database\Capsule\Manager::connection();
        $schema = $capsule->getSchemaBuilder();

        if (!$schema->hasTable('test_table')) {
            $schema->create('test_table', function ($table) {
                $table->id();
                $table->string('name');
                $table->string('email');
                $table->timestamps();
            });
        }
    }

    protected function tearDown(): void
    {
        // Drop test table
        $capsule = \Illuminate\Database\Capsule\Manager::connection();
        $schema = $capsule->getSchemaBuilder();

        if ($schema->hasTable('test_table')) {
            $schema->drop('test_table');
        }

        parent::tearDown();
    }

    /**
     * Test that Select extension assets are included.
     *
     * Validates Requirement 16.6: DataTablesEngine SHALL use DataTables.js select extension
     */
    public function test_select_extension_assets_are_included(): void
    {
        $assets = $this->engine->getAssets();

        // Check CSS includes Select extension
        $selectCssFound = false;
        foreach ($assets['css'] as $css) {
            if (str_contains($css, 'select') && str_contains($css, '.css')) {
                $selectCssFound = true;
                break;
            }
        }
        $this->assertTrue($selectCssFound, 'Select extension CSS should be included');

        // Check JS includes Select extension
        $selectJsFound = false;
        foreach ($assets['js'] as $js) {
            if (str_contains($js, 'select') && str_contains($js, '.js')) {
                $selectJsFound = true;
                break;
            }
        }
        $this->assertTrue($selectJsFound, 'Select extension JS should be included');
    }

    /**
     * Test single row selection mode configuration.
     *
     * Validates Requirement 16.1: THE system SHALL support single row selection
     */
    public function test_single_row_selection_mode(): void
    {
        // Enable single row selection
        $this->table->setSelectable(true, 'single');

        // Configure engine
        $this->engine->configure($this->table);

        // Get configuration
        $config = $this->engine->getConfig();

        // Assert select configuration exists
        $this->assertArrayHasKey('select', $config, 'Select configuration should exist');

        // Assert single selection mode
        $this->assertEquals(
            'single',
            $config['select']['style'],
            'Selection style should be single'
        );

        // Assert selector is configured
        $this->assertEquals(
            'td:first-child',
            $config['select']['selector'],
            'Selector should target first column'
        );

        // Assert CSS class is configured
        $this->assertEquals(
            'selected',
            $config['select']['className'],
            'Selected rows should have "selected" class'
        );
    }

    /**
     * Test multiple row selection mode configuration.
     *
     * Validates Requirement 16.2: THE system SHALL support multiple row selection
     */
    public function test_multiple_row_selection_mode(): void
    {
        // Enable multiple row selection
        $this->table->setSelectable(true, 'multiple');

        // Configure engine
        $this->engine->configure($this->table);

        // Get configuration
        $config = $this->engine->getConfig();

        // Assert select configuration exists
        $this->assertArrayHasKey('select', $config, 'Select configuration should exist');

        // Assert multiple selection mode
        $this->assertEquals(
            'multi',
            $config['select']['style'],
            'Selection style should be multi'
        );
    }

    /**
     * Test select all checkbox is enabled for multiple selection.
     *
     * Validates Requirement 16.3: THE system SHALL provide select all checkbox
     */
    public function test_select_all_checkbox_for_multiple_selection(): void
    {
        // Enable multiple row selection
        $this->table->setSelectable(true, 'multiple');

        // Configure engine
        $this->engine->configure($this->table);

        // Get configuration
        $config = $this->engine->getConfig();

        // Assert select all is enabled
        $this->assertTrue(
            $config['select']['selectAll'],
            'Select all checkbox should be enabled for multiple selection'
        );
    }

    /**
     * Test select all checkbox is not enabled for single selection.
     */
    public function test_no_select_all_for_single_selection(): void
    {
        // Enable single row selection
        $this->table->setSelectable(true, 'single');

        // Configure engine
        $this->engine->configure($this->table);

        // Get configuration
        $config = $this->engine->getConfig();

        // Assert select all is not set for single selection
        $this->assertArrayNotHasKey(
            'selectAll',
            $config['select'],
            'Select all should not be enabled for single selection'
        );
    }

    /**
     * Test row selection is disabled by default.
     */
    public function test_row_selection_disabled_by_default(): void
    {
        // Don't enable selection
        // Configure engine
        $this->engine->configure($this->table);

        // Get configuration
        $config = $this->engine->getConfig();

        // Assert select configuration does not exist
        $this->assertArrayNotHasKey(
            'select',
            $config,
            'Select configuration should not exist when selection is disabled'
        );
    }

    /**
     * Test row selection can be disabled after being enabled.
     */
    public function test_row_selection_can_be_disabled(): void
    {
        // Enable selection
        $this->table->setSelectable(true);

        // Disable selection
        $this->table->setSelectable(false);

        // Configure engine
        $this->engine->configure($this->table);

        // Get configuration
        $config = $this->engine->getConfig();

        // Assert select configuration does not exist
        $this->assertArrayNotHasKey(
            'select',
            $config,
            'Select configuration should not exist when selection is disabled'
        );
    }

    /**
     * Test TableBuilder getSelectable method.
     */
    public function test_table_builder_get_selectable(): void
    {
        // Initially disabled
        $this->assertFalse(
            $this->table->getSelectable(),
            'Selection should be disabled by default'
        );

        // Enable selection
        $this->table->setSelectable(true);

        $this->assertTrue(
            $this->table->getSelectable(),
            'Selection should be enabled after setSelectable(true)'
        );
    }

    /**
     * Test TableBuilder getSelectionMode method.
     */
    public function test_table_builder_get_selection_mode(): void
    {
        // Default mode
        $this->assertEquals(
            'multiple',
            $this->table->getSelectionMode(),
            'Default selection mode should be multiple'
        );

        // Set single mode
        $this->table->setSelectionMode('single');

        $this->assertEquals(
            'single',
            $this->table->getSelectionMode(),
            'Selection mode should be single after setSelectionMode'
        );
    }

    /**
     * Test TableBuilder setSelectionMode validates input.
     */
    public function test_table_builder_set_selection_mode_validates_input(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid selection mode: invalid. Must be 'single' or 'multiple'.");

        $this->table->setSelectionMode('invalid');
    }

    /**
     * Test row selection configuration is included in toArray.
     */
    public function test_row_selection_in_to_array(): void
    {
        $this->table->setSelectable(true, 'single');

        $config = $this->table->toArray();

        $this->assertArrayHasKey('selectable', $config);
        $this->assertTrue($config['selectable']);

        $this->assertArrayHasKey('selectionMode', $config);
        $this->assertEquals('single', $config['selectionMode']);
    }

    /**
     * Test bulk actions configuration.
     */
    public function test_bulk_actions_configuration(): void
    {
        $this->table->addBulkAction(
            'delete',
            'Delete Selected',
            '/users/bulk-delete',
            'DELETE',
            'trash',
            'Are you sure?'
        );

        $bulkActions = $this->table->getBulkActions();

        $this->assertCount(1, $bulkActions);
        $this->assertArrayHasKey('delete', $bulkActions);
        $this->assertEquals('Delete Selected', $bulkActions['delete']['label']);
        $this->assertEquals('DELETE', $bulkActions['delete']['method']);
        $this->assertEquals('trash', $bulkActions['delete']['icon']);
        $this->assertEquals('Are you sure?', $bulkActions['delete']['confirm']);
    }

    /**
     * Test hasBulkActions method.
     */
    public function test_has_bulk_actions(): void
    {
        $this->assertFalse($this->table->hasBulkActions());

        $this->table->addBulkAction('delete', 'Delete', '/users/bulk-delete');

        $this->assertTrue($this->table->hasBulkActions());
    }

    /**
     * Test clearBulkActions method.
     */
    public function test_clear_bulk_actions(): void
    {
        $this->table->addBulkAction('delete', 'Delete', '/users/bulk-delete');
        $this->assertTrue($this->table->hasBulkActions());

        $this->table->clearBulkActions();
        $this->assertFalse($this->table->hasBulkActions());
    }

    /**
     * Create a test TableBuilder instance.
     */
    protected function createTableBuilder(): TableBuilder
    {
        $queryOptimizer = $this->app->make(\Canvastack\Canvastack\Components\Table\Query\QueryOptimizer::class);
        $filterBuilder = $this->app->make(\Canvastack\Canvastack\Components\Table\Query\FilterBuilder::class);
        $schemaInspector = $this->app->make(\Canvastack\Canvastack\Components\Table\Validation\SchemaInspector::class);
        $columnValidator = $this->app->make(\Canvastack\Canvastack\Components\Table\Validation\ColumnValidator::class);

        $table = new TableBuilder(
            $queryOptimizer,
            $filterBuilder,
            $schemaInspector,
            $columnValidator
        );

        // Set a test model
        $model = new class extends Model {
            protected $table = 'test_table';
        };

        $table->setModel($model);
        $table->setFields(['id:ID', 'name:Name', 'email:Email']);

        return $table;
    }
}
