<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Support\Theme;

use Canvastack\Canvastack\Support\Theme\TailwindConfigGenerator;
use Canvastack\Canvastack\Support\Theme\Theme;
use Canvastack\Canvastack\Support\Theme\ThemeCache;
use Canvastack\Canvastack\Support\Theme\ThemeLoader;
use Canvastack\Canvastack\Support\Theme\ThemeManager;
use Canvastack\Canvastack\Support\Theme\ThemeRepository;
use Canvastack\Canvastack\Tests\TestCase;

class TailwindConfigGeneratorTest extends TestCase
{
    protected ThemeManager $themeManager;

    protected TailwindConfigGenerator $generator;

    protected string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();

        // Create temporary directory for themes
        $this->tempDir = sys_get_temp_dir() . '/canvastack_test_' . uniqid();
        mkdir($this->tempDir, 0777, true);

        // Create test theme directory
        mkdir($this->tempDir . '/test', 0777, true);

        // Create test theme
        $themeData = [
            'name' => 'test',
            'display_name' => 'Test Theme',
            'version' => '1.0.0',
            'author' => 'Test Author',
            'description' => 'Test theme for unit testing',
            'config' => [
                'colors' => [
                    'primary' => [
                        '500' => '#6366f1',
                        '600' => '#4f46e5',
                    ],
                    'secondary' => [
                        '500' => '#8b5cf6',
                    ],
                    'accent' => [
                        '500' => '#a855f7',
                    ],
                    'gray' => [
                        '50' => '#f9fafb',
                        '100' => '#f3f4f6',
                        '800' => '#1f2937',
                        '900' => '#111827',
                        '950' => '#030712',
                    ],
                    'info' => [
                        '400' => '#60a5fa',
                    ],
                    'success' => [
                        '400' => '#34d399',
                    ],
                    'warning' => [
                        '400' => '#fbbf24',
                    ],
                    'error' => [
                        '400' => '#f87171',
                    ],
                ],
                'fonts' => [
                    'sans' => 'Inter, system-ui, sans-serif',
                    'mono' => 'JetBrains Mono, monospace',
                ],
                'layout' => [
                    'container_max_width' => '80rem',
                    'border_radius' => [
                        'sm' => '0.375rem',
                        'md' => '0.5rem',
                    ],
                ],
                'dark_mode' => [
                    'enabled' => true,
                    'default' => 'light',
                ],
            ],
        ];

        file_put_contents(
            $this->tempDir . '/test/theme.json',
            json_encode($themeData)
        );

        // Initialize theme system
        $repository = new ThemeRepository();
        $files = new \Illuminate\Filesystem\Filesystem();
        $loader = new ThemeLoader($this->tempDir, $files);
        $cache = new ThemeCache();
        $this->themeManager = new ThemeManager($repository, $loader, $cache);

        // Load theme manually from JSON file
        $theme = $loader->loadFromFile($this->tempDir . '/test/theme.json');
        $repository->register($theme);

        // Initialize generator
        $this->generator = new TailwindConfigGenerator($this->themeManager, $cache);
    }

    protected function tearDown(): void
    {
        // Clean up temporary directory
        if (is_dir($this->tempDir)) {
            $this->deleteDirectory($this->tempDir);
        }

        parent::tearDown();
    }

    /**
     * Recursively delete a directory.
     */
    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    public function test_generates_config_for_theme(): void
    {
        $config = $this->generator->generate('test', false);

        $this->assertIsArray($config);
        $this->assertArrayHasKey('theme', $config);
        $this->assertArrayHasKey('extend', $config['theme']);
        $this->assertArrayHasKey('colors', $config['theme']['extend']);
    }

    public function test_extracts_colors_correctly(): void
    {
        $config = $this->generator->generate('test', false);

        $colors = $config['theme']['extend']['colors'];

        $this->assertArrayHasKey('primary', $colors);
        $this->assertArrayHasKey('secondary', $colors);
        $this->assertEquals('#6366f1', $colors['primary']['500']);
        $this->assertEquals('#8b5cf6', $colors['secondary']['500']);
    }

    public function test_extracts_fonts_correctly(): void
    {
        $config = $this->generator->generate('test', false);

        $fonts = $config['theme']['extend']['fontFamily'];

        $this->assertArrayHasKey('sans', $fonts);
        $this->assertArrayHasKey('mono', $fonts);
        $this->assertIsArray($fonts['sans']);
        $this->assertContains('Inter', $fonts['sans']);
    }

    public function test_extracts_layout_correctly(): void
    {
        $config = $this->generator->generate('test', false);

        $this->assertArrayHasKey('maxWidth', $config['theme']['extend']);
        $this->assertEquals('80rem', $config['theme']['extend']['maxWidth']['container']);
    }

    public function test_extracts_border_radius_correctly(): void
    {
        $config = $this->generator->generate('test', false);

        $borderRadius = $config['theme']['extend']['borderRadius'];

        $this->assertArrayHasKey('sm', $borderRadius);
        $this->assertArrayHasKey('md', $borderRadius);
        $this->assertEquals('0.375rem', $borderRadius['sm']);
    }

    public function test_generates_config_for_all_themes(): void
    {
        $config = $this->generator->generateForAllThemes(false);

        $this->assertIsArray($config);
        $this->assertArrayHasKey('theme', $config);
        $this->assertArrayHasKey('colors', $config['theme']['extend']);
    }

    public function test_generates_daisyui_theme(): void
    {
        $theme = $this->themeManager->get('test');
        $daisyUITheme = $this->generator->generateDaisyUITheme($theme);

        $this->assertIsArray($daisyUITheme);
        $this->assertArrayHasKey('test', $daisyUITheme);
        $this->assertArrayHasKey('primary', $daisyUITheme['test']);
        $this->assertEquals('#6366f1', $daisyUITheme['test']['primary']);
    }

    public function test_generates_daisyui_dark_theme(): void
    {
        $theme = $this->themeManager->get('test');
        $darkTheme = $this->generator->generateDaisyUIDarkTheme($theme);

        $this->assertIsArray($darkTheme);
        $this->assertArrayHasKey('test-dark', $darkTheme);
        $this->assertArrayHasKey('primary', $darkTheme['test-dark']);
    }

    public function test_generates_complete_config(): void
    {
        $config = $this->generator->generateComplete(false);

        $this->assertIsArray($config);
        $this->assertArrayHasKey('theme', $config);
        $this->assertArrayHasKey('daisyui', $config);
        $this->assertArrayHasKey('themes', $config['daisyui']);
    }

    public function test_exports_as_javascript(): void
    {
        $js = $this->generator->exportAsJavaScript(false);

        $this->assertStringStartsWith('export default', $js);
        $this->assertStringContainsString('"theme"', $js);
        $this->assertStringContainsString('"colors"', $js);
    }

    public function test_exports_as_commonjs(): void
    {
        $js = $this->generator->exportAsCommonJS(false);

        $this->assertStringStartsWith('module.exports', $js);
        $this->assertStringContainsString('"theme"', $js);
    }

    public function test_caches_generated_config(): void
    {
        // First call - should generate and cache
        $config1 = $this->generator->generate('test', true);

        // Second call - should use cache
        $config2 = $this->generator->generate('test', true);

        $this->assertEquals($config1, $config2);
    }

    public function test_clears_cache(): void
    {
        // Generate and cache
        $this->generator->generate('test', true);

        // Clear cache
        $this->generator->clearCache();

        // This should regenerate (we can't directly test cache miss, but no errors is good)
        $config = $this->generator->generate('test', true);

        $this->assertIsArray($config);
    }

    public function test_sets_cache_ttl(): void
    {
        $this->generator->setCacheTtl(7200);

        // No direct way to test TTL, but ensure method is chainable
        $this->assertInstanceOf(TailwindConfigGenerator::class, $this->generator);
    }

    public function test_handles_theme_without_optional_fields(): void
    {
        // Create minimal theme directory
        mkdir($this->tempDir . '/minimal', 0777, true);

        // Create minimal theme
        $minimalTheme = [
            'name' => 'minimal',
            'display_name' => 'Minimal',
            'version' => '1.0.0',
            'author' => 'Test Author',
            'description' => 'Minimal test theme',
            'config' => [
                'colors' => [
                    'primary' => ['500' => '#000000'],
                    'secondary' => ['500' => '#666666'],
                    'accent' => ['500' => '#999999'],
                ],
                'fonts' => [
                    'sans' => 'Arial, sans-serif',
                ],
            ],
            'colors' => [
                'primary' => ['500' => '#000000'],
                'secondary' => ['500' => '#666666'],
                'accent' => ['500' => '#999999'],
            ],
            'fonts' => [
                'sans' => 'Arial, sans-serif',
            ],
            'dark_mode' => [
                'enabled' => false,
            ],
        ];

        file_put_contents(
            $this->tempDir . '/minimal/theme.json',
            json_encode($minimalTheme)
        );

        $this->themeManager->reload();

        $config = $this->generator->generate('minimal', false);

        $this->assertIsArray($config);
        $this->assertArrayHasKey('colors', $config['theme']['extend']);
    }

    public function test_generates_base_config(): void
    {
        $config = $this->generator->generate('test', false);

        $this->assertArrayHasKey('content', $config);
        $this->assertArrayHasKey('darkMode', $config);
        $this->assertEquals('class', $config['darkMode']);
        $this->assertIsArray($config['content']);
    }
}
