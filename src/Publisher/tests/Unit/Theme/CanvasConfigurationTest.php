<?php

namespace Tests\Unit\Theme;

use Tests\TestCase;

/**
 * Unit tests for canvas template configuration
 * 
 * Feature: theme-adapter
 * Task: 10.2 Write unit tests for canvas configuration
 * Requirements: 13.2
 */
class CanvasConfigurationTest extends TestCase
{
    private array $config;

    protected function setUp(): void
    {
        parent::setUp();
        $this->config = config('canvastack.templates.admin.canvas');
    }

    /** @test */
    public function test_canvas_configuration_exists(): void
    {
        $this->assertNotNull($this->config, 'Canvas configuration should exist in config/canvastack.templates.php');
        $this->assertIsArray($this->config, 'Canvas configuration should be an array');
    }

    /** @test */
    public function test_canvas_has_position_structure(): void
    {
        $this->assertArrayHasKey('position', $this->config, 'Canvas config should have position key');
        $this->assertIsArray($this->config['position'], 'Position should be an array');
    }

    /** @test */
    public function test_canvas_position_has_top_configuration(): void
    {
        $this->assertArrayHasKey('top', $this->config['position'], 'Position should have top key');
        $this->assertArrayHasKey('js', $this->config['position']['top'], 'Position.top should have js key');
        $this->assertArrayHasKey('css', $this->config['position']['top'], 'Position.top should have css key');
    }

    /** @test */
    public function test_canvas_position_has_bottom_configuration(): void
    {
        $this->assertArrayHasKey('bottom', $this->config['position'], 'Position should have bottom key');
        $this->assertArrayHasKey('first', $this->config['position']['bottom'], 'Position.bottom should have first key');
        $this->assertArrayHasKey('last', $this->config['position']['bottom'], 'Position.bottom should have last key');
    }

    /** @test */
    public function test_canvas_position_bottom_first_has_js_and_css(): void
    {
        $this->assertArrayHasKey('js', $this->config['position']['bottom']['first'], 'Position.bottom.first should have js key');
        $this->assertArrayHasKey('css', $this->config['position']['bottom']['first'], 'Position.bottom.first should have css key');
    }

    /** @test */
    public function test_canvas_position_bottom_last_has_js_and_css(): void
    {
        $this->assertArrayHasKey('js', $this->config['position']['bottom']['last'], 'Position.bottom.last should have js key');
        $this->assertArrayHasKey('css', $this->config['position']['bottom']['last'], 'Position.bottom.last should have css key');
    }

    /** @test */
    public function test_canvas_loads_tailwindcss_cdn(): void
    {
        $topJs = $this->config['position']['top']['js'];
        $this->assertIsArray($topJs, 'Position.top.js should be an array');
        $this->assertContains('https://cdn.tailwindcss.com', $topJs, 'Canvas should load TailwindCSS CDN in position.top.js');
    }

    /** @test */
    public function test_canvas_loads_custom_css(): void
    {
        $bottomFirstCss = $this->config['position']['bottom']['first']['css'];
        $this->assertIsArray($bottomFirstCss, 'Position.bottom.first.css should be an array');
        $this->assertContains('css/canvas.css', $bottomFirstCss, 'Canvas should load custom CSS in position.bottom.first.css');
    }

    /** @test */
    public function test_canvas_loads_custom_js(): void
    {
        $bottomLastJs = $this->config['position']['bottom']['last']['js'];
        $this->assertIsArray($bottomLastJs, 'Position.bottom.last.js should be an array');
        $this->assertContains('js/canvas-scripts.js', $bottomLastJs, 'Canvas should load custom JS in position.bottom.last.js');
    }

    /** @test */
    public function test_canvas_has_all_required_component_keys(): void
    {
        $requiredKeys = [
            'datatable',
            'textarea',
            'tagsinput',
            'file',
            'select',
            'selectMonth',
            'date',
            'datetime',
            'daterange',
            'time',
            'highcharts',
            'chartjs'
        ];

        foreach ($requiredKeys as $key) {
            $this->assertArrayHasKey($key, $this->config, "Canvas config should have {$key} key");
        }
    }

    /** @test */
    public function test_canvas_component_keys_have_js_and_css(): void
    {
        $componentKeys = [
            'datatable',
            'textarea',
            'tagsinput',
            'file',
            'select',
            'selectMonth',
            'date',
            'datetime',
            'daterange',
            'time',
            'highcharts',
            'chartjs'
        ];

        foreach ($componentKeys as $key) {
            $this->assertArrayHasKey('js', $this->config[$key], "{$key} should have js key");
            $this->assertArrayHasKey('css', $this->config[$key], "{$key} should have css key");
            $this->assertIsArray($this->config[$key]['js'], "{$key}.js should be an array");
            $this->assertIsArray($this->config[$key]['css'], "{$key}.css should be an array");
        }
    }

    /** @test */
    public function test_canvas_asset_paths_are_correctly_formatted(): void
    {
        // Test TailwindCSS CDN URL format
        $topJs = $this->config['position']['top']['js'];
        foreach ($topJs as $asset) {
            if ($asset !== null) {
                $this->assertIsString($asset, 'Asset path should be a string');
                if (str_starts_with($asset, 'http')) {
                    $this->assertTrue(
                        filter_var($asset, FILTER_VALIDATE_URL) !== false,
                        "CDN URL {$asset} should be a valid URL"
                    );
                }
            }
        }

        // Test custom CSS path format
        $bottomFirstCss = $this->config['position']['bottom']['first']['css'];
        foreach ($bottomFirstCss as $asset) {
            if ($asset !== null) {
                $this->assertIsString($asset, 'Asset path should be a string');
                $this->assertStringStartsWith('css/', $asset, 'CSS asset should start with css/');
                $this->assertStringEndsWith('.css', $asset, 'CSS asset should end with .css');
            }
        }

        // Test custom JS path format
        $bottomLastJs = $this->config['position']['bottom']['last']['js'];
        foreach ($bottomLastJs as $asset) {
            if ($asset !== null) {
                $this->assertIsString($asset, 'Asset path should be a string');
                $this->assertStringStartsWith('js/', $asset, 'JS asset should start with js/');
                $this->assertStringEndsWith('.js', $asset, 'JS asset should end with .js');
            }
        }
    }

    /** @test */
    public function test_canvas_datatable_configuration(): void
    {
        $datatable = $this->config['datatable'];
        
        $this->assertIsArray($datatable['js'], 'Datatable js should be an array');
        $this->assertIsArray($datatable['css'], 'Datatable css should be an array');
        
        // Canvas should use DataTables like default template
        $this->assertContains('vendor/DataTables/js/datatables.min.js', $datatable['js']);
        $this->assertContains('js/datatables/filter.js', $datatable['js']);
        $this->assertContains('vendor/DataTables/css/datatables.css', $datatable['css']);
    }

    /** @test */
    public function test_canvas_select_configuration_is_null(): void
    {
        // Canvas uses native Tailwind form-input, not Chosen.js
        $select = $this->config['select'];
        
        $this->assertIsArray($select['js'], 'Select js should be an array');
        $this->assertIsArray($select['css'], 'Select css should be an array');
        $this->assertContains(null, $select['js'], 'Canvas select should not load Chosen.js');
        $this->assertContains(null, $select['css'], 'Canvas select should not load Chosen.js CSS');
    }

    /** @test */
    public function test_canvas_configuration_mirrors_default_structure(): void
    {
        $defaultConfig = config('canvastack.templates.admin.default');
        
        // Both should have the same top-level keys
        $this->assertEquals(
            array_keys($defaultConfig),
            array_keys($this->config),
            'Canvas config should have the same structure as default config'
        );
    }

    /** @test */
    public function test_canvas_date_picker_configuration(): void
    {
        $date = $this->config['date'];
        $datetime = $this->config['datetime'];
        
        // Canvas should use the same date/datetime pickers as default
        $this->assertContains('vendor/plugins/nodes/jquery-datetimepicker/build/jquery.datetimepicker.full.min.js', $date['js']);
        $this->assertContains('last:js/form.picker.js', $date['js']);
        $this->assertContains('vendor/plugins/nodes/jquery-datetimepicker/build/jquery.datetimepicker.min.css', $date['css']);
        
        $this->assertContains('vendor/plugins/nodes/jquery-datetimepicker/build/jquery.datetimepicker.full.min.js', $datetime['js']);
        $this->assertContains('last:js/form.picker.js', $datetime['js']);
        $this->assertContains('vendor/plugins/nodes/jquery-datetimepicker/build/jquery.datetimepicker.min.css', $datetime['css']);
    }

    /** @test */
    public function test_canvas_chart_configuration(): void
    {
        $highcharts = $this->config['highcharts'];
        $chartjs = $this->config['chartjs'];
        
        // Canvas should use the same chart libraries as default
        $this->assertContains('vendor/plugins/highcharts/js/highcharts.js', $highcharts['js']);
        $this->assertContains('vendor/plugins/highcharts/js/modules/exporting.js', $highcharts['js']);
        
        $this->assertContains('charts/chartjs/Chart.min.js', $chartjs['js']);
    }
}
