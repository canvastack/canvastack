<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Table\Engines;

use Canvastack\Canvastack\Components\Table\Engines\DataTablesEngine;
use Canvastack\Canvastack\Components\Table\Renderers\AdminRenderer;
use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Tests\TestCase;

/**
 * Test DataTablesEngine FixedColumns functionality.
 *
 * This test verifies that task 4.2.1 is complete:
 * - FixedColumns extension is added to DataTablesEngine
 * - fixedColumns() method works in TableBuilder
 * - Left and right pinned columns are configured correctly
 */
class DataTablesEngineFixedColumnsTest extends TestCase
{
    protected DataTablesEngine $engine;
    protected TableBuilder $table;

    protected function setUp(): void
    {
        parent::setUp();

        $renderer = new AdminRenderer();
        $this->engine = new DataTablesEngine($renderer);
        
        // Create TableBuilder using container
        $this->table = $this->app->make(TableBuilder::class);
    }

    /**
     * Test that FixedColumns extension is included in assets.
     *
     * Validates: Task 4.2.1 sub-task 1 - Add FixedColumns extension to DataTablesEngine
     */
    public function test_fixed_columns_extension_is_included_in_assets(): void
    {
        $assets = $this->engine->getAssets();

        // Check CSS includes FixedColumns
        $cssString = strtolower(implode('', $assets['css']));
        $this->assertStringContainsString(
            'fixedcolumns',
            $cssString,
            'FixedColumns CSS should be included in assets'
        );

        // Check JS includes FixedColumns
        $jsString = strtolower(implode('', $assets['js']));
        $this->assertStringContainsString(
            'fixedcolumns',
            $jsString,
            'FixedColumns JS should be included in assets'
        );
    }

    /**
     * Test that fixedColumns() method exists and works in TableBuilder.
     *
     * Validates: Task 4.2.1 sub-task 2 - Implement fixedColumns() method in TableBuilder
     */
    public function test_fixed_columns_method_exists_in_table_builder(): void
    {
        // Test that method exists
        $this->assertTrue(
            method_exists($this->table, 'fixedColumns'),
            'fixedColumns() method should exist in TableBuilder'
        );

        // Test that method is chainable
        $result = $this->table->fixedColumns(2, 1);
        $this->assertInstanceOf(
            TableBuilder::class,
            $result,
            'fixedColumns() should return TableBuilder instance for chaining'
        );
    }

    /**
     * Test that left pinned columns are configured correctly.
     *
     * Validates: Task 4.2.1 sub-task 3 - Configure left and right pinned columns
     */
    public function test_left_pinned_columns_configuration(): void
    {
        // Set left fixed columns
        $this->table->fixedColumns(2, null);

        // Get fixed columns configuration
        $fixedLeft = $this->table->getFixedLeft();
        $fixedRight = $this->table->getFixedRight();

        $this->assertEquals(2, $fixedLeft, 'Left fixed columns should be 2');
        $this->assertNull($fixedRight, 'Right fixed columns should be null');
    }

    /**
     * Test that right pinned columns are configured correctly.
     *
     * Validates: Task 4.2.1 sub-task 3 - Configure left and right pinned columns
     */
    public function test_right_pinned_columns_configuration(): void
    {
        // Set right fixed columns
        $this->table->fixedColumns(null, 1);

        // Get fixed columns configuration
        $fixedLeft = $this->table->getFixedLeft();
        $fixedRight = $this->table->getFixedRight();

        $this->assertNull($fixedLeft, 'Left fixed columns should be null');
        $this->assertEquals(1, $fixedRight, 'Right fixed columns should be 1');
    }

    /**
     * Test that both left and right pinned columns are configured correctly.
     *
     * Validates: Task 4.2.1 sub-task 3 - Configure left and right pinned columns
     */
    public function test_both_left_and_right_pinned_columns_configuration(): void
    {
        // Set both left and right fixed columns
        $this->table->fixedColumns(2, 1);

        // Get fixed columns configuration
        $fixedLeft = $this->table->getFixedLeft();
        $fixedRight = $this->table->getFixedRight();

        $this->assertEquals(2, $fixedLeft, 'Left fixed columns should be 2');
        $this->assertEquals(1, $fixedRight, 'Right fixed columns should be 1');
    }

    /**
     * Test that clearFixedColumns() method works.
     */
    public function test_clear_fixed_columns(): void
    {
        // Set fixed columns
        $this->table->fixedColumns(2, 1);

        // Clear fixed columns
        $this->table->clearFixedColumns();

        // Verify cleared
        $fixedLeft = $this->table->getFixedLeft();
        $fixedRight = $this->table->getFixedRight();

        $this->assertNull($fixedLeft, 'Left fixed columns should be null after clear');
        $this->assertNull($fixedRight, 'Right fixed columns should be null after clear');
    }

    /**
     * Test that DataTablesEngine supports fixed-columns feature.
     *
     * Validates: Requirement 12.4 - DataTables FixedColumns extension support
     */
    public function test_datatables_engine_supports_fixed_columns_feature(): void
    {
        $this->assertTrue(
            $this->engine->supports('fixed-columns'),
            'DataTablesEngine should support fixed-columns feature'
        );
    }

    /**
     * Test that engine name is correct.
     */
    public function test_engine_name_is_datatables(): void
    {
        $this->assertEquals(
            'datatables',
            $this->engine->getName(),
            'Engine name should be "datatables"'
        );
    }

    /**
     * Test that engine version is returned.
     */
    public function test_engine_version_is_returned(): void
    {
        $version = $this->engine->getVersion();

        $this->assertNotEmpty($version, 'Engine version should not be empty');
        $this->assertIsString($version, 'Engine version should be a string');
    }
}
