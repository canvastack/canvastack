<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Support\Theme;

use Canvastack\Canvastack\Support\Theme\Theme;
use Canvastack\Canvastack\Support\Theme\ThemeCache;
use Canvastack\Canvastack\Support\Theme\ThemeCompiler;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use PHPUnit\Framework\TestCase;

class ThemeCompilerTest extends TestCase
{
    protected ThemeCompiler $compiler;

    protected Theme $theme;

    protected function setUp(): void
    {
        parent::setUp();

        $store = new ArrayStore();
        $repository = new Repository($store);
        $cache = new ThemeCache($repository);
        $this->compiler = new ThemeCompiler($cache);

        $this->theme = new Theme(
            name: 'test-theme',
            displayName: 'Test Theme',
            version: '1.0.0',
            author: 'Test Author',
            description: 'Test Description',
            config: [
                'colors' => [
                    'primary' => [
                        '500' => '#6366f1',
                        '600' => '#4f46e5',
                    ],
                    'secondary' => [
                        '500' => '#8b5cf6',
                    ],
                ],
                'fonts' => [
                    'sans' => 'Inter, sans-serif',
                    'mono' => 'JetBrains Mono, monospace',
                ],
                'layout' => [
                    'sidebar_width' => '16rem',
                    'border_radius' => [
                        'sm' => '0.375rem',
                        'md' => '0.5rem',
                    ],
                ],
                'components' => [
                    'button' => [
                        'border_radius' => 'xl',
                        'padding' => [
                            'sm' => '0.75rem 1rem',
                            'md' => '1rem 1.5rem',
                        ],
                    ],
                ],
                'gradient' => [
                    'primary' => 'linear-gradient(135deg, #6366f1, #8b5cf6)',
                ],
            ]
        );
    }

    public function test_can_extract_css_variables(): void
    {
        $variables = $this->compiler->extractCssVariables($this->theme);

        $this->assertIsArray($variables);
        $this->assertArrayHasKey('--cs-color-primary-500', $variables);
        $this->assertEquals('#6366f1', $variables['--cs-color-primary-500']);
        $this->assertArrayHasKey('--cs-font-sans', $variables);
        $this->assertEquals('Inter, sans-serif', $variables['--cs-font-sans']);
    }

    public function test_can_compile_to_css(): void
    {
        $css = $this->compiler->compileToCss($this->theme);

        $this->assertIsString($css);
        $this->assertStringContainsString(':root', $css);
        $this->assertStringContainsString('--cs-color-primary-500', $css);
        $this->assertStringContainsString('#6366f1', $css);
    }

    public function test_can_compile_to_minified_css(): void
    {
        $css = $this->compiler->compileToCss($this->theme, minify: true);

        $this->assertIsString($css);
        $this->assertStringContainsString(':root{', $css);
        $this->assertStringNotContainsString("\n  ", $css); // No indentation
    }

    public function test_extracts_color_variables_correctly(): void
    {
        $variables = $this->compiler->extractCssVariables($this->theme);

        $this->assertArrayHasKey('--cs-color-primary-500', $variables);
        $this->assertArrayHasKey('--cs-color-primary-600', $variables);
        $this->assertArrayHasKey('--cs-color-secondary-500', $variables);
        $this->assertEquals('#6366f1', $variables['--cs-color-primary-500']);
        $this->assertEquals('#4f46e5', $variables['--cs-color-primary-600']);
    }

    public function test_extracts_font_variables_correctly(): void
    {
        $variables = $this->compiler->extractCssVariables($this->theme);

        $this->assertArrayHasKey('--cs-font-sans', $variables);
        $this->assertArrayHasKey('--cs-font-mono', $variables);
        $this->assertEquals('Inter, sans-serif', $variables['--cs-font-sans']);
        $this->assertEquals('JetBrains Mono, monospace', $variables['--cs-font-mono']);
    }

    public function test_extracts_layout_variables_correctly(): void
    {
        $variables = $this->compiler->extractCssVariables($this->theme);

        $this->assertArrayHasKey('--cs-layout-sidebar-width', $variables);
        $this->assertArrayHasKey('--cs-layout-border-radius-sm', $variables);
        $this->assertArrayHasKey('--cs-layout-border-radius-md', $variables);
        $this->assertEquals('16rem', $variables['--cs-layout-sidebar-width']);
        $this->assertEquals('0.375rem', $variables['--cs-layout-border-radius-sm']);
    }

    public function test_extracts_component_variables_correctly(): void
    {
        $variables = $this->compiler->extractCssVariables($this->theme);

        $this->assertArrayHasKey('--cs-component-button-border-radius', $variables);
        $this->assertArrayHasKey('--cs-component-button-padding-sm', $variables);
        $this->assertArrayHasKey('--cs-component-button-padding-md', $variables);
        $this->assertEquals('xl', $variables['--cs-component-button-border-radius']);
        $this->assertEquals('0.75rem 1rem', $variables['--cs-component-button-padding-sm']);
    }

    public function test_extracts_gradient_variables_correctly(): void
    {
        $variables = $this->compiler->extractCssVariables($this->theme);

        $this->assertArrayHasKey('--cs-gradient-primary', $variables);
        $this->assertEquals('linear-gradient(135deg, #6366f1, #8b5cf6)', $variables['--cs-gradient-primary']);
    }

    public function test_can_compile_to_tailwind_config(): void
    {
        $config = $this->compiler->compileToTailwindConfig($this->theme);

        $this->assertIsArray($config);
        $this->assertArrayHasKey('theme', $config);
        $this->assertArrayHasKey('extend', $config['theme']);
        $this->assertArrayHasKey('colors', $config['theme']['extend']);
        $this->assertArrayHasKey('fontFamily', $config['theme']['extend']);
    }

    public function test_tailwind_config_contains_colors(): void
    {
        $config = $this->compiler->compileToTailwindConfig($this->theme);

        $colors = $config['theme']['extend']['colors'];
        $this->assertArrayHasKey('primary', $colors);
        $this->assertArrayHasKey('secondary', $colors);
        $this->assertEquals(['500' => '#6366f1', '600' => '#4f46e5'], $colors['primary']);
    }

    public function test_tailwind_config_contains_fonts(): void
    {
        $config = $this->compiler->compileToTailwindConfig($this->theme);

        $fonts = $config['theme']['extend']['fontFamily'];
        $this->assertArrayHasKey('sans', $fonts);
        $this->assertArrayHasKey('mono', $fonts);
        $this->assertEquals(['Inter', ' sans-serif'], $fonts['sans']);
    }

    public function test_can_compile_to_javascript(): void
    {
        $js = $this->compiler->compileToJavaScript($this->theme);

        $this->assertIsString($js);
        $this->assertStringContainsString('window.canvastackTheme', $js);
        $this->assertStringContainsString('test-theme', $js);
        $this->assertStringContainsString('Test Theme', $js);
    }

    public function test_uses_cache_when_enabled(): void
    {
        $this->compiler->setUseCache(true);

        // First call should compile and cache
        $css1 = $this->compiler->compileToCss($this->theme);

        // Second call should use cache
        $css2 = $this->compiler->compileToCss($this->theme);

        $this->assertEquals($css1, $css2);
    }

    public function test_can_disable_caching(): void
    {
        $this->compiler->setUseCache(false);

        $this->assertFalse($this->compiler->isUsingCache());
    }

    public function test_can_set_and_get_cache(): void
    {
        $store = new ArrayStore();
        $repository = new Repository($store);
        $cache = new ThemeCache($repository);

        $this->compiler->setCache($cache);

        $this->assertSame($cache, $this->compiler->getCache());
    }

    public function test_handles_empty_config_gracefully(): void
    {
        $emptyTheme = new Theme(
            name: 'empty',
            displayName: 'Empty',
            version: '1.0.0',
            author: 'Author',
            description: 'Description',
            config: []
        );

        $variables = $this->compiler->extractCssVariables($emptyTheme);
        $this->assertIsArray($variables);
        $this->assertEmpty($variables);
    }

    public function test_css_output_is_valid(): void
    {
        $css = $this->compiler->compileToCss($this->theme);

        // Check for valid CSS structure
        $this->assertStringContainsString(':root {', $css);
        $this->assertStringContainsString('}', $css);
        $this->assertMatchesRegularExpression('/--[\w-]+:\s*[^;]+;/', $css);
    }
}
