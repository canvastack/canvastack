<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Table\Engines;

use Canvastack\Canvastack\Components\Table\Engines\DataTablesEngine;
use Canvastack\Canvastack\Components\Table\Engines\TableEngineInterface;
use Canvastack\Canvastack\Components\Table\Renderers\AdminRenderer;
use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Components\Table\Exceptions\RenderException;
use Canvastack\Canvastack\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Mockery;

/**
 * Test for DataTablesEngine.
 *
 * This test verifies that the DataTablesEngine correctly wraps the existing
 * AdminRenderer implementation and provides DataTables.js functionality
 * through the TableEngineInterface. It validates rendering, configuration,
 * asset management, feature support, and server-side processing.
 *
 * @package Canvastack\Canvastack\Tests\Unit\Components\Table\Engines
 * @version 1.0.0
 *
 * Validates:
 * - Requirements 4.1-4.7: DataTables engine implementation
 * - Requirement 29.4: Unit tests for DataTablesEngine
 */
class DataTablesEngineTest extends TestCase
{
    /**
     * Admin renderer mock.
     *
     * @var AdminRenderer|\Mockery\MockInterface
     */
    protected $renderer;

    /**
     * DataTables engine instance.
     *
     * @var DataTablesEngine
     */
    protected DataTablesEngine $engine;

    /**
     * Setup test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->renderer = Mockery::mock(AdminRenderer::class);
        $this->engine = new DataTablesEngine($this->renderer);
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
     * Test that DataTablesEngine can be instantiated.
     *
     * Validates: Requirement 4.1 (DataTablesEngine wraps AdminRenderer)
     *
     * @return void
     */
    #[Test]
    public function test_datatables_engine_can_be_instantiated(): void
    {
        $renderer = Mockery::mock(AdminRenderer::class);
        $engine = new DataTablesEngine($renderer);
        
        $this->assertInstanceOf(
            DataTablesEngine::class,
            $engine,
            'DataTablesEngine should be instantiable'
        );
    }

    /**
     * Test that DataTablesEngine implements TableEngineInterface.
     *
     * Validates: Requirement 4.1 (implements interface)
     *
     * @return void
     */
    #[Test]
    public function test_datatables_engine_implements_interface(): void
    {
        $this->assertInstanceOf(
            TableEngineInterface::class,
            $this->engine,
            'DataTablesEngine should implement TableEngineInterface'
        );
    }

    /**
     * Test render() method delegates to AdminRenderer.
     *
     * Validates: Requirement 4.2 (supports all current DataTables.js features)
     *
     * @return void
     */
    #[Test]
    public function test_render_method_delegates_to_admin_renderer(): void
    {
        $table = Mockery::mock(TableBuilder::class);
        $table->shouldAllowMockingProtectedMethods();
        $table->shouldReceive('toArray')->andReturn([
            'columns' => ['name' => 'Name', 'email' => 'Email'],
            'serverSide' => false,
            'fixedLeft' => null,
            'fixedRight' => null,
        ]);
        $table->shouldReceive('getData')->andReturn([]);
        $table->shouldReceive('getAjaxUrl')->andReturn('/api/data');
        $table->shouldReceive('getExportButtons')->andReturn([]);
        $table->shouldReceive('getFixedLeft')->andReturn(null);
        $table->shouldReceive('getFixedRight')->andReturn(null);
        $table->shouldReceive('getSelectable')->andReturn(false);
        $table->shouldReceive('getTableName')->andReturn('test_table');
        
        $expectedHtml = '<table id="datatable">...</table>';
        
        $this->renderer->shouldReceive('render')
            ->once()
            ->andReturn($expectedHtml);
        
        $result = $this->engine->render($table);
        
        $this->assertEquals(
            $expectedHtml,
            $result,
            'render() should delegate to AdminRenderer and return HTML'
        );
    }

    /**
     * Test render() method throws RenderException on failure.
     *
     * Validates: Requirement 4.7 (error handling)
     *
     * @return void
     */
    #[Test]
    public function test_render_method_throws_exception_on_failure(): void
    {
        $table = Mockery::mock(TableBuilder::class);
        $table->shouldAllowMockingProtectedMethods();
        $table->shouldReceive('toArray')->andReturn([
            'fixedLeft' => null,
            'fixedRight' => null,
        ]);
        $table->shouldReceive('getData')->andReturn([]);
        $table->shouldReceive('getAjaxUrl')->andReturn('/api/data');
        $table->shouldReceive('getExportButtons')->andReturn([]);
        $table->shouldReceive('getFixedLeft')->andReturn(null);
        $table->shouldReceive('getFixedRight')->andReturn(null);
        $table->shouldReceive('getSelectable')->andReturn(false);
        $table->shouldReceive('getTableName')->andReturn('test_table');
        
        $this->renderer->shouldReceive('render')
            ->once()
            ->andThrow(new \Exception('Rendering failed'));
        
        $this->expectException(RenderException::class);
        $this->expectExceptionMessage('Failed to render table with DataTables engine');
        
        $this->engine->render($table);
    }

    /**
     * Test configure() method sets up DataTables configuration.
     *
     * Validates: Requirement 4.7 (handles all current configuration options)
     *
     * @return void
     */
    #[Test]
    public function test_configure_method_sets_up_configuration(): void
    {
        $table = Mockery::mock(TableBuilder::class);
        $table->shouldReceive('toArray')->andReturn([
            'serverSide' => true,
            'displayLimit' => 25,
            'fixedLeft' => null,
            'fixedRight' => null,
        ]);
        $table->shouldReceive('getAjaxUrl')->andReturn('/api/data');
        $table->shouldReceive('getFixedLeft')->andReturn(null);
        $table->shouldReceive('getFixedRight')->andReturn(null);
        $table->shouldReceive('getExportButtons')->andReturn([]);
        $table->shouldReceive('getSelectable')->andReturn(false);
        
        $this->engine->configure($table);
        
        $config = $this->engine->getConfig();
        
        $this->assertIsArray($config, 'Configuration should be an array');
        $this->assertArrayHasKey('serverSide', $config);
        $this->assertTrue($config['serverSide']);
    }

    /**
     * Test getAssets() method returns DataTables assets.
     *
     * Validates: Requirement 4.2 (provides required assets)
     *
     * @return void
     */
    #[Test]
    public function test_get_assets_method_returns_datatables_assets(): void
    {
        $assets = $this->engine->getAssets();
        
        $this->assertIsArray($assets, 'Assets should be an array');
        $this->assertArrayHasKey('css', $assets);
        $this->assertArrayHasKey('js', $assets);
        
        // Verify CSS assets
        $this->assertIsArray($assets['css']);
        $this->assertNotEmpty($assets['css'], 'Should have CSS assets');
        $this->assertStringContainsString(
            'dataTables',
            $assets['css'][0],
            'Should include DataTables CSS'
        );
        
        // Verify JS assets
        $this->assertIsArray($assets['js']);
        $this->assertNotEmpty($assets['js'], 'Should have JS assets');
        $this->assertStringContainsString(
            'dataTables',
            $assets['js'][0],
            'Should include DataTables JS'
        );
    }

    /**
     * Test getAssets() includes FixedColumns extension.
     *
     * Validates: Requirement 4.5 (supports FixedColumns extension)
     *
     * @return void
     */
    #[Test]
    public function test_get_assets_includes_fixed_columns_extension(): void
    {
        $assets = $this->engine->getAssets();
        
        $cssAssets = implode(' ', $assets['css']);
        $jsAssets = implode(' ', $assets['js']);
        
        $this->assertStringContainsString(
            'fixedColumns',
            $cssAssets,
            'Should include FixedColumns CSS'
        );
        
        $this->assertStringContainsString(
            'fixedColumns',
            $jsAssets,
            'Should include FixedColumns JS'
        );
    }

    /**
     * Test getAssets() includes Buttons extension.
     *
     * Validates: Requirement 4.6 (supports Buttons extension for export)
     *
     * @return void
     */
    #[Test]
    public function test_get_assets_includes_buttons_extension(): void
    {
        $assets = $this->engine->getAssets();
        
        $cssAssets = implode(' ', $assets['css']);
        $jsAssets = implode(' ', $assets['js']);
        
        $this->assertStringContainsString(
            'buttons',
            $cssAssets,
            'Should include Buttons CSS'
        );
        
        $this->assertStringContainsString(
            'buttons',
            $jsAssets,
            'Should include Buttons JS'
        );
        
        // Verify export libraries
        $this->assertStringContainsString(
            'jszip',
            $jsAssets,
            'Should include JSZip for Excel export'
        );
        
        $this->assertStringContainsString(
            'pdfmake',
            $jsAssets,
            'Should include pdfmake for PDF export'
        );
    }

    /**
     * Test supports() method for sorting feature.
     *
     * Validates: Requirement 4.2 (supports sorting)
     *
     * @return void
     */
    #[Test]
    public function test_supports_method_for_sorting_feature(): void
    {
        $this->assertTrue(
            $this->engine->supports('sorting'),
            'DataTablesEngine should support sorting'
        );
    }

    /**
     * Test supports() method for pagination feature.
     *
     * Validates: Requirement 4.2 (supports pagination)
     *
     * @return void
     */
    #[Test]
    public function test_supports_method_for_pagination_feature(): void
    {
        $this->assertTrue(
            $this->engine->supports('pagination'),
            'DataTablesEngine should support pagination'
        );
    }

    /**
     * Test supports() method for searching feature.
     *
     * Validates: Requirement 4.2 (supports searching)
     *
     * @return void
     */
    #[Test]
    public function test_supports_method_for_searching_feature(): void
    {
        $this->assertTrue(
            $this->engine->supports('searching'),
            'DataTablesEngine should support searching'
        );
    }

    /**
     * Test supports() method for filtering feature.
     *
     * Validates: Requirement 4.2 (supports filtering)
     *
     * @return void
     */
    #[Test]
    public function test_supports_method_for_filtering_feature(): void
    {
        $this->assertTrue(
            $this->engine->supports('filtering'),
            'DataTablesEngine should support filtering'
        );
    }

    /**
     * Test supports() method for fixed-columns feature.
     *
     * Validates: Requirement 4.5 (supports FixedColumns extension)
     *
     * @return void
     */
    #[Test]
    public function test_supports_method_for_fixed_columns_feature(): void
    {
        $this->assertTrue(
            $this->engine->supports('fixed-columns'),
            'DataTablesEngine should support fixed-columns'
        );
    }

    /**
     * Test supports() method for row-selection feature.
     *
     * Validates: Requirement 4.2 (supports row selection)
     *
     * @return void
     */
    #[Test]
    public function test_supports_method_for_row_selection_feature(): void
    {
        $this->assertTrue(
            $this->engine->supports('row-selection'),
            'DataTablesEngine should support row-selection'
        );
    }

    /**
     * Test supports() method for export feature.
     *
     * Validates: Requirement 4.6 (supports Buttons extension for export)
     *
     * @return void
     */
    #[Test]
    public function test_supports_method_for_export_feature(): void
    {
        $this->assertTrue(
            $this->engine->supports('export'),
            'DataTablesEngine should support export'
        );
    }

    /**
     * Test supports() method for responsive feature.
     *
     * Validates: Requirement 4.2 (supports responsive design)
     *
     * @return void
     */
    #[Test]
    public function test_supports_method_for_responsive_feature(): void
    {
        $this->assertTrue(
            $this->engine->supports('responsive'),
            'DataTablesEngine should support responsive'
        );
    }

    /**
     * Test supports() method for dark-mode feature.
     *
     * Validates: Requirement 4.2 (supports dark mode)
     *
     * @return void
     */
    #[Test]
    public function test_supports_method_for_dark_mode_feature(): void
    {
        $this->assertTrue(
            $this->engine->supports('dark-mode'),
            'DataTablesEngine should support dark-mode'
        );
    }

    /**
     * Test supports() method returns false for unsupported features.
     *
     * Validates: Requirement 4.2 (correctly reports unsupported features)
     *
     * @return void
     */
    #[Test]
    public function test_supports_method_returns_false_for_unsupported_features(): void
    {
        $this->assertFalse(
            $this->engine->supports('column-resizing'),
            'DataTablesEngine should not support column-resizing'
        );
        
        $this->assertFalse(
            $this->engine->supports('virtual-scrolling'),
            'DataTablesEngine should not support virtual-scrolling'
        );
        
        $this->assertFalse(
            $this->engine->supports('lazy-loading'),
            'DataTablesEngine should not support lazy-loading'
        );
        
        $this->assertFalse(
            $this->engine->supports('nonexistent-feature'),
            'DataTablesEngine should not support nonexistent features'
        );
    }

    /**
     * Test getName() method returns 'datatables'.
     *
     * Validates: Requirement 4.1 (engine identification)
     *
     * @return void
     */
    #[Test]
    public function test_get_name_method_returns_datatables(): void
    {
        $this->assertEquals(
            'datatables',
            $this->engine->getName(),
            'getName() should return "datatables"'
        );
    }

    /**
     * Test getVersion() method returns version string.
     *
     * Validates: Requirement 4.1 (version information)
     *
     * @return void
     */
    #[Test]
    public function test_get_version_method_returns_version_string(): void
    {
        $version = $this->engine->getVersion();
        
        $this->assertIsString($version, 'Version should be a string');
        $this->assertNotEmpty($version, 'Version should not be empty');
        $this->assertMatchesRegularExpression(
            '/^\d+\.\d+\.\d+$/',
            $version,
            'Version should follow semantic versioning (X.Y.Z)'
        );
    }

    /**
     * Test processServerSide() method returns empty array.
     *
     * DataTables uses Yajra for server-side processing, which is handled
     * by the existing TableBuilder implementation.
     *
     * Validates: Requirement 4.3 (uses Yajra for server-side processing)
     *
     * @return void
     */
    #[Test]
    public function test_process_server_side_method_returns_empty_array(): void
    {
        $table = Mockery::mock(TableBuilder::class);
        
        $result = $this->engine->processServerSide($table);
        
        $this->assertIsArray($result, 'processServerSide() should return an array');
        $this->assertEmpty($result, 'processServerSide() should return empty array for DataTables');
    }

    /**
     * Test getConfig() method returns configuration array.
     *
     * Validates: Requirement 4.7 (configuration management)
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
     * Validates: Requirement 4.7 (configuration management)
     *
     * @return void
     */
    #[Test]
    public function test_set_config_method_updates_configuration(): void
    {
        $newConfig = [
            'pageLength' => 50,
            'customOption' => 'value',
        ];
        
        $this->engine->setConfig($newConfig);
        
        $config = $this->engine->getConfig();
        
        $this->assertArrayHasKey('pageLength', $config);
        $this->assertEquals(50, $config['pageLength']);
        $this->assertArrayHasKey('customOption', $config);
        $this->assertEquals('value', $config['customOption']);
    }

    /**
     * Test setConfig() method merges with existing configuration.
     *
     * Validates: Requirement 4.7 (configuration merging)
     *
     * @return void
     */
    #[Test]
    public function test_set_config_method_merges_with_existing_configuration(): void
    {
        $initialConfig = ['option1' => 'value1'];
        $this->engine->setConfig($initialConfig);
        
        $additionalConfig = ['option2' => 'value2'];
        $this->engine->setConfig($additionalConfig);
        
        $config = $this->engine->getConfig();
        
        $this->assertArrayHasKey('option1', $config);
        $this->assertArrayHasKey('option2', $config);
        $this->assertEquals('value1', $config['option1']);
        $this->assertEquals('value2', $config['option2']);
    }

    /**
     * Test configure() method with fixed columns.
     *
     * Validates: Requirement 4.5 (FixedColumns extension configuration)
     *
     * @return void
     */
    #[Test]
    public function test_configure_method_with_fixed_columns(): void
    {
        $table = Mockery::mock(TableBuilder::class);
        $table->shouldReceive('toArray')->andReturn([
            'fixedLeft' => 2,
            'fixedRight' => 1,
        ]);
        $table->shouldReceive('getAjaxUrl')->andReturn('/api/data');
        $table->shouldReceive('getFixedLeft')->andReturn(2);
        $table->shouldReceive('getFixedRight')->andReturn(1);
        $table->shouldReceive('getExportButtons')->andReturn([]);
        $table->shouldReceive('getSelectable')->andReturn(false);
        
        $this->engine->configure($table);
        
        $config = $this->engine->getConfig();
        
        $this->assertArrayHasKey('fixedColumns', $config);
        $this->assertIsArray($config['fixedColumns']);
        $this->assertArrayHasKey('leftColumns', $config['fixedColumns']);
        $this->assertArrayHasKey('rightColumns', $config['fixedColumns']);
        $this->assertEquals(2, $config['fixedColumns']['leftColumns']);
        $this->assertEquals(1, $config['fixedColumns']['rightColumns']);
    }

    /**
     * Test configure() method with export buttons.
     *
     * Validates: Requirement 4.6 (Buttons extension configuration)
     *
     * @return void
     */
    #[Test]
    public function test_configure_method_with_export_buttons(): void
    {
        $table = Mockery::mock(TableBuilder::class);
        $table->shouldAllowMockingProtectedMethods();
        $table->shouldReceive('toArray')->andReturn([
            'columns' => ['name' => 'Name', 'email' => 'Email'],
            'fixedLeft' => null,
            'fixedRight' => null,
        ]);
        $table->shouldReceive('getAjaxUrl')->andReturn('/api/data');
        $table->shouldReceive('getFixedLeft')->andReturn(null);
        $table->shouldReceive('getFixedRight')->andReturn(null);
        $table->shouldReceive('getExportButtons')->andReturn(['excel', 'csv', 'pdf']);
        $table->shouldReceive('getNonExportableColumns')->andReturn([]);
        $table->shouldReceive('getColumns')->andReturn(['name' => 'Name', 'email' => 'Email']);
        $table->shouldReceive('getTableName')->andReturn('users');
        $table->shouldReceive('getSelectable')->andReturn(false);
        
        $this->engine->configure($table);
        
        $config = $this->engine->getConfig();
        
        $this->assertArrayHasKey('buttons', $config);
        $this->assertIsArray($config['buttons']);
        $this->assertCount(3, $config['buttons']);
    }

    /**
     * Test configure() method with row selection.
     *
     * Validates: Requirement 4.2 (row selection configuration)
     *
     * @return void
     */
    #[Test]
    public function test_configure_method_with_row_selection(): void
    {
        $table = Mockery::mock(TableBuilder::class);
        $table->shouldReceive('toArray')->andReturn([
            'fixedLeft' => null,
            'fixedRight' => null,
        ]);
        $table->shouldReceive('getAjaxUrl')->andReturn('/api/data');
        $table->shouldReceive('getFixedLeft')->andReturn(null);
        $table->shouldReceive('getFixedRight')->andReturn(null);
        $table->shouldReceive('getExportButtons')->andReturn([]);
        $table->shouldReceive('getSelectable')->andReturn(true);
        $table->shouldReceive('getSelectionMode')->andReturn('multiple');
        
        $this->engine->configure($table);
        
        $config = $this->engine->getConfig();
        
        $this->assertArrayHasKey('select', $config);
        $this->assertIsArray($config['select']);
        $this->assertEquals('multi', $config['select']['style']);
    }

    /**
     * Test render() method prepares data correctly for AdminRenderer.
     *
     * Validates: Requirement 4.1 (wraps AdminRenderer implementation)
     *
     * @return void
     */
    #[Test]
    public function test_render_method_prepares_data_correctly(): void
    {
        $table = Mockery::mock(TableBuilder::class);
        $table->shouldAllowMockingProtectedMethods();
        $table->shouldReceive('toArray')->andReturn([
            'columns' => ['name' => 'Name', 'email' => 'Email'],
            'tableName' => 'users_table',
            'serverSide' => true,
            'hiddenColumns' => ['id'],
            'columnWidths' => ['name' => '200px'],
            'columnColors' => ['status' => '#00ff00'],
            'fixedLeft' => null,
            'fixedRight' => null,
        ]);
        $table->shouldReceive('getData')->andReturn([
            ['name' => 'John', 'email' => 'john@example.com'],
        ]);
        $table->shouldReceive('getAjaxUrl')->andReturn('/api/users');
        $table->shouldReceive('getExportButtons')->andReturn([]);
        $table->shouldReceive('getFixedLeft')->andReturn(null);
        $table->shouldReceive('getFixedRight')->andReturn(null);
        $table->shouldReceive('getSelectable')->andReturn(false);
        $table->shouldReceive('getTableName')->andReturn('users_table');
        
        $this->renderer->shouldReceive('render')
            ->once()
            ->with(Mockery::on(function ($data) {
                return is_array($data)
                    && isset($data['columns'])
                    && isset($data['rows'])
                    && isset($data['table_id'])
                    && $data['table_id'] === 'users_table'
                    && isset($data['server_side'])
                    && $data['server_side'] === true
                    && isset($data['ajax_url'])
                    && $data['ajax_url'] === '/api/users';
            }))
            ->andReturn('<table>...</table>');
        
        $result = $this->engine->render($table);
        
        $this->assertIsString($result);
    }

    /**
     * Test all interface methods are implemented.
     *
     * Validates: Requirement 4.1 (implements TableEngineInterface)
     *
     * @return void
     */
    #[Test]
    public function test_all_interface_methods_are_implemented(): void
    {
        $reflection = new \ReflectionClass(DataTablesEngine::class);
        
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
                "DataTablesEngine should implement {$method}() method"
            );
        }
    }
}
