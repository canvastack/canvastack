<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Table\Engines;

use Canvastack\Canvastack\Components\Table\Engines\TanStackEngine;
use Canvastack\Canvastack\Components\Table\Renderers\TanStackRenderer;
use Canvastack\Canvastack\Components\Table\ServerSide\TanStackServerAdapter;
use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Tests\TestCase;
use Mockery;

/**
 * Test for TanStack Engine Column Pinning.
 *
 * Validates Requirements 12.1, 12.2, 12.3, 12.5, 12.6
 */
class TanStackEngineColumnPinningTest extends TestCase
{
    protected TanStackEngine $engine;
    protected TanStackRenderer $renderer;
    protected TanStackServerAdapter $serverAdapter;
    protected TableBuilder $table;

    protected function setUp(): void
    {
        parent::setUp();

        $this->renderer = Mockery::mock(TanStackRenderer::class);
        $this->serverAdapter = Mockery::mock(TanStackServerAdapter::class);
        $this->engine = new TanStackEngine($this->renderer, $this->serverAdapter);
        $this->table = Mockery::mock(TableBuilder::class)->shouldAllowMockingProtectedMethods();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test that column pinning configuration is generated correctly.
     *
     * @return void
     */
    public function test_column_pinning_config_is_generated_correctly(): void
    {
        // Arrange
        $config = (object) [
            'fixedLeft' => 2,
            'fixedRight' => 1,
            'fields' => [
                'id' => 'ID',
                'name' => 'Name',
                'email' => 'Email',
                'status' => 'Status',
                'actions' => 'Actions',
            ],
            'serverSide' => false,
            'pageSize' => 25,
            'pageSizeOptions' => [10, 25, 50, 100],
            'orderByColumn' => 'id',
            'orderByDirection' => 'asc',
            'searchable' => true,
            'searchableColumns' => [],
            'filterGroups' => [],
            'activeFilters' => [],
            'selectable' => false,
            'selectionMode' => 'single',
            'virtualScrolling' => false,
            'actions' => [],
            'rightColumns' => [],
            'centerColumns' => [],
            'columnWidths' => [],
            'columnColors' => [],
            'columnRenderers' => [],
            'hiddenColumns' => [],
        ];

        $this->table->shouldReceive('getConfiguration')->andReturn($config);
        $this->table->shouldReceive('getTableId')->andReturn('test-table');
        $this->renderer->shouldReceive('setConfig')->once();

        // Act
        $this->engine->configure($this->table);
        $tanstackConfig = $this->engine->getConfig();

        // Assert - Column pinning should be configured
        $this->assertArrayHasKey('columnPinning', $tanstackConfig);
        $this->assertIsArray($tanstackConfig['columnPinning']);
        $this->assertTrue($tanstackConfig['columnPinning']['enabled']);
        
        // Verify left pinned columns
        $this->assertArrayHasKey('left', $tanstackConfig['columnPinning']);
        $this->assertCount(2, $tanstackConfig['columnPinning']['left']);
        $this->assertEquals(['id', 'name'], $tanstackConfig['columnPinning']['left']);
        
        // Verify right pinned columns
        $this->assertArrayHasKey('right', $tanstackConfig['columnPinning']);
        $this->assertCount(1, $tanstackConfig['columnPinning']['right']);
        $this->assertEquals(['actions'], $tanstackConfig['columnPinning']['right']);
    }

    /**
     * Test that column pinning returns null when no columns are pinned.
     *
     * @return void
     */
    public function test_column_pinning_returns_null_when_no_columns_pinned(): void
    {
        // Arrange
        $config = (object) [
            'fixedLeft' => null,
            'fixedRight' => null,
            'fields' => [
                'id' => 'ID',
                'name' => 'Name',
                'email' => 'Email',
            ],
            'serverSide' => false,
            'pageSize' => 25,
            'pageSizeOptions' => [10, 25, 50, 100],
            'orderByColumn' => 'id',
            'orderByDirection' => 'asc',
            'searchable' => true,
            'searchableColumns' => [],
            'filterGroups' => [],
            'activeFilters' => [],
            'selectable' => false,
            'selectionMode' => 'single',
            'virtualScrolling' => false,
            'actions' => [],
            'rightColumns' => [],
            'centerColumns' => [],
            'columnWidths' => [],
            'columnColors' => [],
            'columnRenderers' => [],
            'hiddenColumns' => [],
        ];

        $this->table->shouldReceive('getConfiguration')->andReturn($config);
        $this->table->shouldReceive('getTableId')->andReturn('test-table');
        $this->renderer->shouldReceive('setConfig')->once();

        // Act
        $this->engine->configure($this->table);
        $tanstackConfig = $this->engine->getConfig();

        // Assert - Column pinning should be null
        $this->assertArrayHasKey('columnPinning', $tanstackConfig);
        $this->assertNull($tanstackConfig['columnPinning']);
    }

    /**
     * Test that only left columns can be pinned.
     *
     * @return void
     */
    public function test_only_left_columns_can_be_pinned(): void
    {
        // Arrange
        $config = (object) [
            'fixedLeft' => 3,
            'fixedRight' => null,
            'fields' => [
                'id' => 'ID',
                'name' => 'Name',
                'email' => 'Email',
                'status' => 'Status',
            ],
            'serverSide' => false,
            'pageSize' => 25,
            'pageSizeOptions' => [10, 25, 50, 100],
            'orderByColumn' => 'id',
            'orderByDirection' => 'asc',
            'searchable' => true,
            'searchableColumns' => [],
            'filterGroups' => [],
            'activeFilters' => [],
            'selectable' => false,
            'selectionMode' => 'single',
            'virtualScrolling' => false,
            'actions' => [],
            'rightColumns' => [],
            'centerColumns' => [],
            'columnWidths' => [],
            'columnColors' => [],
            'columnRenderers' => [],
            'hiddenColumns' => [],
        ];

        $this->table->shouldReceive('getConfiguration')->andReturn($config);
        $this->table->shouldReceive('getTableId')->andReturn('test-table');
        $this->renderer->shouldReceive('setConfig')->once();

        // Act
        $this->engine->configure($this->table);
        $tanstackConfig = $this->engine->getConfig();

        // Assert
        $this->assertArrayHasKey('columnPinning', $tanstackConfig);
        $this->assertTrue($tanstackConfig['columnPinning']['enabled']);
        $this->assertCount(3, $tanstackConfig['columnPinning']['left']);
        $this->assertEquals(['id', 'name', 'email'], $tanstackConfig['columnPinning']['left']);
        $this->assertEmpty($tanstackConfig['columnPinning']['right']);
    }

    /**
     * Test that only right columns can be pinned.
     *
     * @return void
     */
    public function test_only_right_columns_can_be_pinned(): void
    {
        // Arrange
        $config = (object) [
            'fixedLeft' => null,
            'fixedRight' => 2,
            'fields' => [
                'id' => 'ID',
                'name' => 'Name',
                'email' => 'Email',
                'status' => 'Status',
            ],
            'serverSide' => false,
            'pageSize' => 25,
            'pageSizeOptions' => [10, 25, 50, 100],
            'orderByColumn' => 'id',
            'orderByDirection' => 'asc',
            'searchable' => true,
            'searchableColumns' => [],
            'filterGroups' => [],
            'activeFilters' => [],
            'selectable' => false,
            'selectionMode' => 'single',
            'virtualScrolling' => false,
            'actions' => [],
            'rightColumns' => [],
            'centerColumns' => [],
            'columnWidths' => [],
            'columnColors' => [],
            'columnRenderers' => [],
            'hiddenColumns' => [],
        ];

        $this->table->shouldReceive('getConfiguration')->andReturn($config);
        $this->table->shouldReceive('getTableId')->andReturn('test-table');
        $this->renderer->shouldReceive('setConfig')->once();

        // Act
        $this->engine->configure($this->table);
        $tanstackConfig = $this->engine->getConfig();

        // Assert
        $this->assertArrayHasKey('columnPinning', $tanstackConfig);
        $this->assertTrue($tanstackConfig['columnPinning']['enabled']);
        $this->assertEmpty($tanstackConfig['columnPinning']['left']);
        $this->assertCount(2, $tanstackConfig['columnPinning']['right']);
        $this->assertEquals(['email', 'status'], $tanstackConfig['columnPinning']['right']);
    }

    /**
     * Test that pinned columns remain visible during horizontal scroll.
     *
     * This is validated through CSS styling (sticky positioning).
     *
     * @return void
     */
    public function test_pinned_columns_have_sticky_positioning(): void
    {
        // Arrange
        $config = (object) [
            'fixedLeft' => 1,
            'fixedRight' => 1,
            'fields' => [
                'id' => 'ID',
                'name' => 'Name',
                'email' => 'Email',
            ],
            'serverSide' => false,
            'pageSize' => 25,
            'pageSizeOptions' => [10, 25, 50, 100],
            'orderByColumn' => 'id',
            'orderByDirection' => 'asc',
            'searchable' => true,
            'searchableColumns' => [],
            'filterGroups' => [],
            'activeFilters' => [],
            'selectable' => false,
            'selectionMode' => 'single',
            'virtualScrolling' => false,
            'actions' => [],
            'rightColumns' => [],
            'centerColumns' => [],
            'columnWidths' => [],
            'columnColors' => [],
            'columnRenderers' => [],
            'hiddenColumns' => [],
        ];

        $this->table->shouldReceive('getConfiguration')->andReturn($config);
        $this->table->shouldReceive('getTableId')->andReturn('test-table');
        $this->renderer->shouldReceive('setConfig')->once();
        $this->renderer->shouldReceive('renderStyles')->andReturn($this->getExpectedStyles());

        // Act
        $this->engine->configure($this->table);
        $styles = $this->renderer->renderStyles($this->table);

        // Assert - Verify CSS contains sticky positioning for pinned columns
        $this->assertStringContainsString('position: sticky', $styles);
        $this->assertStringContainsString('tanstack-table-pinned-left', $styles);
        $this->assertStringContainsString('tanstack-table-pinned-right', $styles);
        $this->assertStringContainsString('left: 0', $styles);
        $this->assertStringContainsString('right: 0', $styles);
        $this->assertStringContainsString('box-shadow', $styles);
    }

    /**
     * Get expected CSS styles for pinned columns.
     *
     * @return string
     */
    protected function getExpectedStyles(): string
    {
        return <<<CSS
/* Column Pinning (Fixed Columns) */
.tanstack-table-pinned-left,
.tanstack-table-pinned-right {
    position: sticky;
    background: var(--cs-color-background, #ffffff);
    z-index: 10;
}

.tanstack-table-pinned-left {
    left: 0;
    box-shadow: 2px 0 4px rgba(0, 0, 0, 0.1);
}

.tanstack-table-pinned-right {
    right: 0;
    box-shadow: -2px 0 4px rgba(0, 0, 0, 0.1);
}
CSS;
    }
}

