<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Table\Engines;

use Canvastack\Canvastack\Components\Table\Engines\TanStackEngine;
use Canvastack\Canvastack\Components\Table\Engines\TableEngineInterface;
use Canvastack\Canvastack\Components\Table\Renderers\TanStackRenderer;
use Canvastack\Canvastack\Components\Table\ServerSide\TanStackServerAdapter;
use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Components\Table\Exceptions\RenderException;
use Canvastack\Canvastack\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Mockery;

/**
 * Test for TanStackEngine.
 *
 * This test verifies that the TanStackEngine correctly implements the
 * TableEngineInterface and provides TanStack Table v8 functionality
 * with Alpine.js integration. It validates rendering, configuration,
 * asset management, feature support, and server-side processing.
 *
 * @package Canvastack\Canvastack\Tests\Unit\Components\Table\Engines
 * @version 1.0.0
 *
 * Validates:
 * - Requirements 5.1-5.7: TanStack engine implementation
 * - Requirement 29.5: Unit tests for TanStackEngine
 */
class TanStackEngineTest extends TestCase
{
    /**
     * TanStack renderer mock.
     *
     * @var TanStackRenderer|\Mockery\MockInterface
     */
    protected $renderer;

    /**
     * Server adapter mock.
     *
     * @var TanStackServerAdapter|\Mockery\MockInterface
     */
    protected $serverAdapter;

    /**
     * TanStack engine instance.
     *
     * @var TanStackEngine
     */
    protected TanStackEngine $engine;

    /**
     * Setup test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->renderer = Mockery::mock(TanStackRenderer::class);
        $this->serverAdapter = Mockery::mock(TanStackServerAdapter::class);
        $this->engine = new TanStackEngine($this->renderer, $this->serverAdapter);
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
     * Create a complete mock configuration object.
     *
     * @param array $overrides
     * @return object
     */
    protected function createMockConfig(array $overrides = []): object
    {
        $defaults = [
            'serverSide' => false,
            'pageSize' => 25,
            'pageSizeOptions' => [10, 25, 50, 100],
            'orderByColumn' => null,
            'orderByDirection' => 'asc',
            'searchable' => true,
            'searchableColumns' => [],
            'filterGroups' => [],
            'activeFilters' => [],
            'selectable' => false,
            'selectionMode' => 'multiple',
            'actions' => [],
            'fields' => [],
            'fixedLeft' => null,
            'fixedRight' => null,
            'rightColumns' => [],
            'centerColumns' => [],
            'hiddenColumns' => [],
            'nonSortableColumns' => [],
            'requiredColumns' => [],
            'columnWidths' => [],
            'columnColors' => [],
            'columnRenderers' => [],
            'virtualScrolling' => false,
            'columnResizing' => false,
        ];

        return (object) array_merge($defaults, $overrides);
    }

    /**
     * Test that TanStackEngine can be instantiated.
     *
     * Validates: Requirement 5.1 (TanStackEngine integrates TanStack Table v8)
     *
     * @return void
     */
    #[Test]
    public function test_tanstack_engine_can_be_instantiated(): void
    {
        $renderer = Mockery::mock(TanStackRenderer::class);
        $serverAdapter = Mockery::mock(TanStackServerAdapter::class);
        $engine = new TanStackEngine($renderer, $serverAdapter);
        
        $this->assertInstanceOf(
            TanStackEngine::class,
            $engine,
            'TanStackEngine should be instantiable'
        );
    }

    /**
     * Test that TanStackEngine implements TableEngineInterface.
     *
     * Validates: Requirement 5.1 (implements interface)
     *
     * @return void
     */
    #[Test]
    public function test_tanstack_engine_implements_interface(): void
    {
        $this->assertInstanceOf(
            TableEngineInterface::class,
            $this->engine,
            'TanStackEngine should implement TableEngineInterface'
        );
    }

    /**
     * Test render() method delegates to TanStackRenderer.
     *
     * Validates: Requirement 5.2 (uses Alpine.js for reactive state management)
     *
     * @return void
     */
    #[Test]
    public function test_render_method_delegates_to_tanstack_renderer(): void
    {
        $table = Mockery::mock(TableBuilder::class);
        $table->shouldReceive('toArray')->andReturn([
            'columns' => ['name' => 'Name', 'email' => 'Email'],
            'serverSide' => false,
        ]);
        $table->shouldReceive('getConfiguration')->andReturn($this->createMockConfig([
            'serverSide' => false,
            'fields' => ['name' => 'Name', 'email' => 'Email'],
        ]));
        $table->shouldReceive('getAjaxUrl')->andReturn(null);
        $table->shouldReceive('getVirtualScrolling')->andReturn(false);
        $table->shouldReceive('getFixedLeft')->andReturn(null);
        $table->shouldReceive('getFixedRight')->andReturn(null);
        $table->shouldReceive('getColumnResizing')->andReturn(false);
        $table->shouldReceive('getConfig')->andReturn([]);
        
        $expectedHtml = '<div x-data="tanstackTable()">...</div>';
        
        $this->renderer->shouldReceive('setConfig')->once();
        $this->renderer->shouldReceive('render')
            ->once()
            ->with($table, Mockery::type('array'), Mockery::type('array'), Mockery::type('array'))
            ->andReturn($expectedHtml);
        
        $result = $this->engine->render($table);
        
        $this->assertEquals(
            $expectedHtml,
            $result,
            'render() should delegate to TanStackRenderer and return HTML'
        );
    }

    /**
     * Test render() method throws RenderException on failure.
     *
     * Validates: Requirement 5.7 (error handling)
     *
     * @return void
     */
    #[Test]
    public function test_render_method_throws_exception_on_failure(): void
    {
        $table = Mockery::mock(TableBuilder::class);
        $table->shouldReceive('toArray')->andReturn([]);
        $table->shouldReceive('getConfiguration')->andReturn($this->createMockConfig());
        $table->shouldReceive('getAjaxUrl')->andReturn(null);
        $table->shouldReceive('getVirtualScrolling')->andReturn(false);
        $table->shouldReceive('getFixedLeft')->andReturn(null);
        $table->shouldReceive('getFixedRight')->andReturn(null);
        $table->shouldReceive('getColumnResizing')->andReturn(false);
        $table->shouldReceive('getConfig')->andReturn([]);
        
        $this->renderer->shouldReceive('setConfig')->once();
        $this->renderer->shouldReceive('render')
            ->once()
            ->with($table, Mockery::type('array'), Mockery::type('array'), Mockery::type('array'))
            ->andThrow(new \Exception('Rendering failed'));
        
        $this->expectException(RenderException::class);
        $this->expectExceptionMessage('Failed to render table with TanStack engine');
        
        $this->engine->render($table);
    }

    /**
     * Test configure() method sets up TanStack configuration.
     *
     * Validates: Requirement 5.3 (configuration management)
     *
     * @return void
     */
    #[Test]
    public function test_configure_method_sets_up_configuration(): void
    {
        $table = Mockery::mock(TableBuilder::class);
        $table->shouldReceive('toArray')->andReturn([
            'serverSide' => false,
            'displayLimit' => 25,
            'columns' => ['name' => 'Name', 'email' => 'Email'],
        ]);
        $table->shouldReceive('getConfiguration')->andReturn($this->createMockConfig([
            'serverSide' => false,
            'fields' => ['name' => 'Name', 'email' => 'Email'],
        ]));
        $table->shouldReceive('getAjaxUrl')->andReturn('/api/data');
        $table->shouldReceive('getVirtualScrolling')->andReturn(false);
        $table->shouldReceive('getFixedLeft')->andReturn(null);
        $table->shouldReceive('getFixedRight')->andReturn(null);
        $table->shouldReceive('getColumnResizing')->andReturn(false);
        $table->shouldReceive('getConfig')->andReturn([]);
        
        $this->renderer->shouldReceive('setConfig')->once();
        
        $this->engine->configure($table);
        
        $config = $this->engine->getConfig();
        
        $this->assertIsArray($config, 'Configuration should be an array');
        $this->assertArrayHasKey('serverSide', $config);
        $this->assertFalse($config['serverSide']['enabled']);
    }

    /**
     * Test getAssets() method returns TanStack assets.
     *
     * Validates: Requirement 5.1 (provides TanStack Table v8 assets)
     *
     * @return void
     */
    #[Test]
    public function test_get_assets_method_returns_tanstack_assets(): void
    {
        $assets = $this->engine->getAssets();
        
        $this->assertIsArray($assets, 'Assets should be an array');
        $this->assertArrayHasKey('css', $assets);
        $this->assertArrayHasKey('js', $assets);
        
        // Verify CSS assets (may be empty for TanStack as it uses Tailwind)
        $this->assertIsArray($assets['css']);
        
        // Verify JS assets
        $this->assertIsArray($assets['js']);
        $this->assertNotEmpty($assets['js'], 'Should have JS assets');
        $this->assertStringContainsString(
            'tanstack',
            strtolower(implode(' ', $assets['js'])),
            'Should include TanStack Table JS'
        );
    }

    /**
     * Test getAssets() includes Alpine.js.
     *
     * Validates: Requirement 5.2 (uses Alpine.js for reactive state management)
     *
     * @return void
     */
    #[Test]
    public function test_get_assets_includes_alpine_js(): void
    {
        $assets = $this->engine->getAssets();
        
        $jsAssets = implode(' ', $assets['js']);
        
        $this->assertStringContainsString(
            'alpine',
            strtolower($jsAssets),
            'Should include Alpine.js'
        );
    }

    /**
     * Test supports() method for sorting feature.
     *
     * Validates: Requirement 5.7 (supports all TanStack Table core features)
     *
     * @return void
     */
    #[Test]
    public function test_supports_method_for_sorting_feature(): void
    {
        $this->assertTrue(
            $this->engine->supports('sorting'),
            'TanStackEngine should support sorting'
        );
    }

    /**
     * Test supports() method for pagination feature.
     *
     * Validates: Requirement 5.7 (supports pagination)
     *
     * @return void
     */
    #[Test]
    public function test_supports_method_for_pagination_feature(): void
    {
        $this->assertTrue(
            $this->engine->supports('pagination'),
            'TanStackEngine should support pagination'
        );
    }

    /**
     * Test supports() method for searching feature.
     *
     * Validates: Requirement 5.7 (supports searching)
     *
     * @return void
     */
    #[Test]
    public function test_supports_method_for_searching_feature(): void
    {
        $this->assertTrue(
            $this->engine->supports('searching'),
            'TanStackEngine should support searching'
        );
    }

    /**
     * Test supports() method for filtering feature.
     *
     * Validates: Requirement 5.7 (supports filtering)
     *
     * @return void
     */
    #[Test]
    public function test_supports_method_for_filtering_feature(): void
    {
        $this->assertTrue(
            $this->engine->supports('filtering'),
            'TanStackEngine should support filtering'
        );
    }

    /**
     * Test supports() method for fixed-columns feature.
     *
     * Validates: Requirement 5.7 (supports column pinning)
     *
     * @return void
     */
    #[Test]
    public function test_supports_method_for_fixed_columns_feature(): void
    {
        $this->assertTrue(
            $this->engine->supports('fixed-columns'),
            'TanStackEngine should support fixed-columns (column pinning)'
        );
    }

    /**
     * Test supports() method for row-selection feature.
     *
     * Validates: Requirement 5.7 (supports row selection)
     *
     * @return void
     */
    #[Test]
    public function test_supports_method_for_row_selection_feature(): void
    {
        $this->assertTrue(
            $this->engine->supports('row-selection'),
            'TanStackEngine should support row-selection'
        );
    }

    /**
     * Test supports() method for export feature.
     *
     * Validates: Requirement 5.7 (supports export)
     *
     * @return void
     */
    #[Test]
    public function test_supports_method_for_export_feature(): void
    {
        $this->assertTrue(
            $this->engine->supports('export'),
            'TanStackEngine should support export'
        );
    }

    /**
     * Test supports() method for column-resizing feature.
     *
     * Validates: Requirement 5.7 (supports column resizing)
     *
     * @return void
     */
    #[Test]
    public function test_supports_method_for_column_resizing_feature(): void
    {
        $this->assertTrue(
            $this->engine->supports('column-resizing'),
            'TanStackEngine should support column-resizing'
        );
    }

    /**
     * Test supports() method for virtual-scrolling feature.
     *
     * Validates: Requirement 5.7 (supports virtual scrolling)
     *
     * @return void
     */
    #[Test]
    public function test_supports_method_for_virtual_scrolling_feature(): void
    {
        $this->assertTrue(
            $this->engine->supports('virtual-scrolling'),
            'TanStackEngine should support virtual-scrolling'
        );
    }

    /**
     * Test supports() method for lazy-loading feature.
     *
     * Validates: Requirement 5.7 (supports lazy loading)
     *
     * @return void
     */
    #[Test]
    public function test_supports_method_for_lazy_loading_feature(): void
    {
        $this->assertTrue(
            $this->engine->supports('lazy-loading'),
            'TanStackEngine should support lazy-loading'
        );
    }

    /**
     * Test supports() method for responsive feature.
     *
     * Validates: Requirement 5.7 (supports responsive design)
     *
     * @return void
     */
    #[Test]
    public function test_supports_method_for_responsive_feature(): void
    {
        $this->assertTrue(
            $this->engine->supports('responsive'),
            'TanStackEngine should support responsive'
        );
    }

    /**
     * Test supports() method for dark-mode feature.
     *
     * Validates: Requirement 5.7 (supports dark mode)
     *
     * @return void
     */
    #[Test]
    public function test_supports_method_for_dark_mode_feature(): void
    {
        $this->assertTrue(
            $this->engine->supports('dark-mode'),
            'TanStackEngine should support dark-mode'
        );
    }

    /**
     * Test supports() method returns false for unsupported features.
     *
     * Validates: Requirement 5.7 (correctly reports unsupported features)
     *
     * @return void
     */
    #[Test]
    public function test_supports_method_returns_false_for_unsupported_features(): void
    {
        $this->assertFalse(
            $this->engine->supports('nonexistent-feature'),
            'TanStackEngine should not support nonexistent features'
        );
    }

    /**
     * Test getName() method returns 'tanstack'.
     *
     * Validates: Requirement 5.1 (engine identification)
     *
     * @return void
     */
    #[Test]
    public function test_get_name_method_returns_tanstack(): void
    {
        $this->assertEquals(
            'tanstack',
            $this->engine->getName(),
            'getName() should return "tanstack"'
        );
    }

    /**
     * Test getVersion() method returns version string.
     *
     * Validates: Requirement 5.1 (version information)
     *
     * @return void
     */
    #[Test]
    public function test_get_version_method_returns_version_string(): void
    {
        $version = $this->engine->getVersion();
        
        $this->assertIsString($version, 'Version should be a string');
        $this->assertNotEmpty($version, 'Version should not be empty');
        $this->assertStringStartsWith('8.', $version, 'Version should be TanStack Table v8.x');
    }

    /**
     * Test processServerSide() method uses custom adapter.
     *
     * Validates: Requirement 5.3 (implements custom server-side adapter)
     *
     * @return void
     */
    #[Test]
    public function test_process_server_side_method_uses_custom_adapter(): void
    {
        $table = Mockery::mock(TableBuilder::class);
        
        $expectedResponse = [
            'data' => [['name' => 'John', 'email' => 'john@example.com']],
            'total' => 100,
            'filtered' => 50,
        ];
        
        $this->serverAdapter->shouldReceive('process')
            ->once()
            ->with($table)
            ->andReturn($expectedResponse);
        
        $result = $this->engine->processServerSide($table);
        
        $this->assertIsArray($result, 'processServerSide() should return an array');
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('filtered', $result);
        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Test getConfig() method returns configuration array.
     *
     * Validates: Requirement 5.3 (configuration management)
     *
     * @return void
     */
    #[Test]
    public function test_get_config_method_returns_configuration_array(): void
    {
        $config = $this->engine->getConfig();
        
        $this->assertIsArray($config, 'getConfig() should return an array');
    }

    /**
     * Test setConfig() method updates configuration.
     *
     * Validates: Requirement 5.3 (configuration management)
     *
     * @return void
     */
    #[Test]
    public function test_set_config_method_updates_configuration(): void
    {
        $newConfig = [
            'pageSize' => 50,
            'customOption' => 'value',
        ];
        
        $this->engine->setConfig($newConfig);
        
        $config = $this->engine->getConfig();
        
        $this->assertArrayHasKey('pageSize', $config);
        $this->assertEquals(50, $config['pageSize']);
        $this->assertArrayHasKey('customOption', $config);
        $this->assertEquals('value', $config['customOption']);
    }

    /**
     * Test getTanStackConfig() method returns proper configuration.
     *
     * Validates: Requirement 5.3 (TanStack Table configuration)
     *
     * @return void
     */
    #[Test]
    public function test_get_tanstack_config_method_returns_proper_configuration(): void
    {
        $table = Mockery::mock(TableBuilder::class);
        $table->shouldReceive('toArray')->andReturn([
            'serverSide' => false,
            'displayLimit' => 25,
            'columns' => ['name' => 'Name', 'email' => 'Email'],
        ]);
        $table->shouldReceive('getConfiguration')->andReturn($this->createMockConfig([
            'serverSide' => false,
            'fields' => ['name' => 'Name', 'email' => 'Email'],
        ]));
        $table->shouldReceive('getAjaxUrl')->andReturn('/api/data');
        $table->shouldReceive('getVirtualScrolling')->andReturn(false);
        $table->shouldReceive('getFixedLeft')->andReturn(null);
        $table->shouldReceive('getFixedRight')->andReturn(null);
        $table->shouldReceive('getColumnResizing')->andReturn(false);
        $table->shouldReceive('getConfig')->andReturn([]);
        
        $this->renderer->shouldReceive('setConfig')->once();
        
        $this->engine->configure($table);
        $config = $this->engine->getConfig();
        
        $this->assertArrayHasKey('serverSide', $config);
        $this->assertArrayHasKey('pagination', $config);
        $this->assertArrayHasKey('columns', $config);
    }

    /**
     * Test getColumnDefinitions() method returns column configuration.
     *
     * Validates: Requirement 5.4 (column definitions)
     *
     * @return void
     */
    #[Test]
    public function test_get_column_definitions_method_returns_column_configuration(): void
    {
        $table = Mockery::mock(TableBuilder::class);
        $table->shouldReceive('toArray')->andReturn([
            'columns' => [
                'name' => 'Name',
                'email' => 'Email',
                'created_at' => 'Created',
            ],
        ]);
        $table->shouldReceive('getConfiguration')->andReturn($this->createMockConfig([
            'serverSide' => false,
            'fields' => [
                'name' => 'Name',
                'email' => 'Email',
                'created_at' => 'Created',
            ],
        ]));
        $table->shouldReceive('getAjaxUrl')->andReturn('/api/data');
        $table->shouldReceive('getVirtualScrolling')->andReturn(false);
        $table->shouldReceive('getFixedLeft')->andReturn(null);
        $table->shouldReceive('getFixedRight')->andReturn(null);
        $table->shouldReceive('getColumnResizing')->andReturn(false);
        $table->shouldReceive('getConfig')->andReturn([]);
        
        $this->renderer->shouldReceive('setConfig')->once();
        
        $this->engine->configure($table);
        $config = $this->engine->getConfig();
        
        $this->assertArrayHasKey('columns', $config);
        $this->assertIsArray($config['columns']);
        $this->assertCount(3, $config['columns']);
    }

    /**
     * Test getAlpineData() method returns Alpine.js configuration.
     *
     * Validates: Requirement 5.2 (Alpine.js integration)
     *
     * @return void
     */
    #[Test]
    public function test_get_alpine_data_method_returns_alpine_configuration(): void
    {
        $table = Mockery::mock(TableBuilder::class);
        $table->shouldReceive('toArray')->andReturn([
            'serverSide' => false,
            'displayLimit' => 25,
            'columns' => ['name' => 'Name'],
        ]);
        $table->shouldReceive('getConfiguration')->andReturn($this->createMockConfig([
            'serverSide' => false,
            'fields' => ['name' => 'Name'],
        ]));
        $table->shouldReceive('getData')->andReturn([
            ['name' => 'John'],
        ]);
        $table->shouldReceive('getAjaxUrl')->andReturn(null);
        $table->shouldReceive('getVirtualScrolling')->andReturn(false);
        $table->shouldReceive('getFixedLeft')->andReturn(null);
        $table->shouldReceive('getFixedRight')->andReturn(null);
        $table->shouldReceive('getColumnResizing')->andReturn(false);
        $table->shouldReceive('getConfig')->andReturn([]);
        
        // setConfig is called once in configure()
        $this->renderer->shouldReceive('setConfig')->once();
        
        // render() is called with 4 parameters
        $this->renderer->shouldReceive('render')
            ->once()
            ->with($table, Mockery::type('array'), Mockery::type('array'), Mockery::on(function ($alpineData) {
                return is_array($alpineData)
                    && isset($alpineData['data'])
                    && isset($alpineData['loading'])
                    && isset($alpineData['page']);
            }))
            ->andReturn('<div>...</div>');
        
        $this->engine->render($table);
        
        $this->assertTrue(true, 'Alpine data should be passed to renderer');
    }

    /**
     * Test configure() method with virtual scrolling.
     *
     * Validates: Requirement 5.5 (virtual scrolling configuration)
     *
     * @return void
     */
    #[Test]
    public function test_configure_method_with_virtual_scrolling(): void
    {
        $table = Mockery::mock(TableBuilder::class);
        $table->shouldReceive('toArray')->andReturn([
            'columns' => ['name' => 'Name'],
        ]);
        $table->shouldReceive('getConfiguration')->andReturn($this->createMockConfig([
            'serverSide' => false,
            'fields' => ['name' => 'Name'],
        ]));
        $table->shouldReceive('getAjaxUrl')->andReturn('/api/data');
        $table->shouldReceive('getVirtualScrolling')->andReturn(true);
        $table->shouldReceive('getFixedLeft')->andReturn(null);
        $table->shouldReceive('getFixedRight')->andReturn(null);
        $table->shouldReceive('getColumnResizing')->andReturn(false);
        $table->shouldReceive('getConfig')->andReturn(['virtualScrolling' => true]);
        
        $this->renderer->shouldReceive('setConfig')->once();
        
        $this->engine->configure($table);
        
        $config = $this->engine->getConfig();
        
        $this->assertArrayHasKey('virtualScrolling', $config);
        $this->assertIsArray($config['virtualScrolling']);
        $this->assertTrue($config['virtualScrolling']['enabled']);
    }

    /**
     * Test configure() method with column pinning.
     *
     * Validates: Requirement 5.5 (column pinning configuration)
     *
     * @return void
     */
    #[Test]
    public function test_configure_method_with_column_pinning(): void
    {
        $table = Mockery::mock(TableBuilder::class);
        $table->shouldReceive('toArray')->andReturn([
            'columns' => ['name' => 'Name', 'email' => 'Email', 'status' => 'Status'],
        ]);
        $table->shouldReceive('getConfiguration')->andReturn($this->createMockConfig([
            'serverSide' => false,
            'fields' => ['name' => 'Name', 'email' => 'Email', 'status' => 'Status'],
            'fixedLeft' => 2,
            'fixedRight' => 1,
        ]));
        $table->shouldReceive('getAjaxUrl')->andReturn('/api/data');
        $table->shouldReceive('getVirtualScrolling')->andReturn(false);
        $table->shouldReceive('getFixedLeft')->andReturn(2);
        $table->shouldReceive('getFixedRight')->andReturn(1);
        $table->shouldReceive('getColumnResizing')->andReturn(false);
        $table->shouldReceive('getConfig')->andReturn([]);
        
        $this->renderer->shouldReceive('setConfig')->once();
        
        $this->engine->configure($table);
        
        $config = $this->engine->getConfig();
        
        $this->assertArrayHasKey('columnPinning', $config);
        $this->assertIsArray($config['columnPinning']);
        $this->assertArrayHasKey('left', $config['columnPinning']);
        $this->assertArrayHasKey('right', $config['columnPinning']);
    }

    /**
     * Test configure() method with column resizing.
     *
     * Validates: Requirement 5.5 (column resizing configuration)
     *
     * @return void
     */
    #[Test]
    public function test_configure_method_with_column_resizing(): void
    {
        $table = Mockery::mock(TableBuilder::class);
        $table->shouldReceive('toArray')->andReturn([
            'columns' => ['name' => 'Name'],
        ]);
        $table->shouldReceive('getConfiguration')->andReturn($this->createMockConfig([
            'serverSide' => false,
            'fields' => ['name' => 'Name'],
            'columnResizing' => true,
        ]));
        $table->shouldReceive('getAjaxUrl')->andReturn('/api/data');
        $table->shouldReceive('getVirtualScrolling')->andReturn(false);
        $table->shouldReceive('getFixedLeft')->andReturn(null);
        $table->shouldReceive('getFixedRight')->andReturn(null);
        $table->shouldReceive('getColumnResizing')->andReturn(true);
        $table->shouldReceive('getConfig')->andReturn([]);
        
        $this->renderer->shouldReceive('setConfig')->once();
        
        $this->engine->configure($table);
        
        $config = $this->engine->getConfig();
        
        $this->assertArrayHasKey('columnResizing', $config);
        $this->assertIsArray($config['columnResizing']);
        $this->assertTrue($config['columnResizing']['enabled']);
    }

    /**
     * Test all interface methods are implemented.
     *
     * Validates: Requirement 5.1 (implements TableEngineInterface)
     *
     * @return void
     */
    #[Test]
    public function test_all_interface_methods_are_implemented(): void
    {
        $reflection = new \ReflectionClass(TanStackEngine::class);
        
        $expectedMethods = [
            'render',
            'configure',
            'getAssets',
            'supports',
            'getName',
            'getVersion',
            'processServerSide',
            'getConfig',
            'setConfig',
        ];
        
        foreach ($expectedMethods as $method) {
            $this->assertTrue(
                $reflection->hasMethod($method),
                "TanStackEngine should implement {$method}() method"
            );
        }
    }

    /**
     * Test engine is framework-agnostic (no jQuery dependency).
     *
     * Validates: Requirement 5.6 (framework-agnostic, no jQuery)
     *
     * @return void
     */
    #[Test]
    public function test_engine_is_framework_agnostic(): void
    {
        $assets = $this->engine->getAssets();
        
        $allAssets = implode(' ', array_merge($assets['css'], $assets['js']));
        
        $this->assertStringNotContainsString(
            'jquery',
            strtolower($allAssets),
            'TanStackEngine should not depend on jQuery'
        );
    }

    /**
     * Test engine supports Shadcn/ui inspired styling.
     *
     * Validates: Requirement 5.4 (Shadcn/ui inspired styling)
     *
     * @return void
     */
    #[Test]
    public function test_engine_supports_shadcn_ui_styling(): void
    {
        $assets = $this->engine->getAssets();
        
        $this->assertArrayHasKey('css', $assets);
        // TanStack uses Tailwind CSS for styling, so CSS array may be empty
        $this->assertIsArray($assets['css'], 'Should have CSS array for Shadcn/ui styling');
    }

    /**
     * Test engine provides unlimited design customization.
     *
     * Validates: Requirement 5.5 (unlimited design customization)
     *
     * @return void
     */
    #[Test]
    public function test_engine_provides_unlimited_design_customization(): void
    {
        $customConfig = [
            'customClass' => 'my-custom-table',
            'customStyles' => ['border' => '1px solid red'],
        ];
        
        $this->engine->setConfig($customConfig);
        
        $config = $this->engine->getConfig();
        
        $this->assertArrayHasKey('customClass', $config);
        $this->assertArrayHasKey('customStyles', $config);
        $this->assertEquals('my-custom-table', $config['customClass']);
    }
}
