<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Support\Theme;

use Canvastack\Canvastack\Tests\TestCase;
use Canvastack\Canvastack\Support\Theme\TailwindThemePlugin;
use Canvastack\Canvastack\Support\Theme\ThemeCache;
use Canvastack\Canvastack\Support\Theme\ThemeLoader;
use Canvastack\Canvastack\Support\Theme\ThemeManager;
use Canvastack\Canvastack\Support\Theme\ThemeRepository;

class TailwindThemePluginTest extends TestCase
{
    protected ThemeManager $themeManager;

    protected TailwindThemePlugin $plugin;

    protected string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();

        // Create temporary directory for themes
        $this->tempDir = sys_get_temp_dir() . '/canvastack_test_' . uniqid();
        mkdir($this->tempDir, 0777, true);
        mkdir($this->tempDir . '/test', 0777, true);

        // Create test theme with gradient
        $themeData = [
            'name' => 'test',
            'display_name' => 'Test Theme',
            'version' => '1.0.0',
            'author' => 'Test Author',
            'description' => 'Test theme',
            'config' => [
                'colors' => [
                    'primary' => [
                        '500' => '#6366f1',
                    ],
                    'secondary' => [
                        '500' => '#8b5cf6',
                    ],
                    'accent' => [
                        '500' => '#a855f7',
                    ],
                ],
                'fonts' => [
                    'sans' => ['Inter', 'system-ui', 'sans-serif'],
                ],
                'gradient' => [
                    'primary' => 'linear-gradient(135deg, #6366f1, #8b5cf6)',
                    'subtle' => 'linear-gradient(135deg, #eef2ff, #f5f3ff)',
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

        // Create cache with empty array store (no pre-cached data)
        $cacheStore = new \Illuminate\Cache\ArrayStore();
        $cacheRepository = new \Illuminate\Cache\Repository($cacheStore);
        $cache = new ThemeCache($cacheRepository);

        $this->themeManager = new ThemeManager($repository, $loader, $cache);
        $this->themeManager->loadThemes();

        // Initialize plugin
        $this->plugin = new TailwindThemePlugin($this->themeManager);
    }

    protected function tearDown(): void
    {
        // Clean up temporary directory
        if (is_dir($this->tempDir)) {
            // Recursively delete directory
            $this->deleteDirectory($this->tempDir);
        }

        parent::tearDown();
    }

    /**
     * Recursively delete a directory.
     *
     * @param string $dir
     * @return void
     */
    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = array_diff(scandir($dir), ['.', '..']);

        foreach ($items as $item) {
            $path = $dir . DIRECTORY_SEPARATOR . $item;

            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($dir);
    }

    public function test_generates_plugin_code(): void
    {
        $code = $this->plugin->generate();

        $this->assertStringContainsString('const plugin = require', $code);
        $this->assertStringContainsString('addUtilities', $code);
        $this->assertStringContainsString('module.exports', $code);
    }

    public function test_generates_color_utilities(): void
    {
        $code = $this->plugin->generate();

        $this->assertStringContainsString('.theme-test-primary', $code);
        $this->assertStringContainsString('.theme-test-secondary', $code);
        $this->assertStringContainsString('.theme-test-accent', $code);
        $this->assertStringContainsString('#6366f1', $code);
    }

    public function test_generates_gradient_utilities(): void
    {
        $code = $this->plugin->generate();

        $this->assertStringContainsString('.theme-test-gradient-primary', $code);
        $this->assertStringContainsString('.theme-test-gradient-subtle', $code);
        $this->assertStringContainsString('linear-gradient', $code);
    }

    public function test_generates_background_utilities(): void
    {
        $code = $this->plugin->generate();

        $this->assertStringContainsString('.bg-theme-test-primary', $code);
        $this->assertStringContainsString('backgroundColor', $code);
    }

    public function test_generates_border_utilities(): void
    {
        $code = $this->plugin->generate();

        $this->assertStringContainsString('.border-theme-test-primary', $code);
        $this->assertStringContainsString('borderColor', $code);
    }

    public function test_generates_text_utilities(): void
    {
        $code = $this->plugin->generate();

        $this->assertStringContainsString('.text-theme-test-primary', $code);
        $this->assertStringContainsString('color', $code);
    }

    public function test_generates_as_module(): void
    {
        $code = $this->plugin->generateAsModule();

        $this->assertStringContainsString('import plugin from', $code);
        $this->assertStringContainsString('export default plugin', $code);
        $this->assertStringContainsString('addUtilities', $code);
    }

    public function test_saves_to_file(): void
    {
        $filePath = $this->tempDir . '/plugin.js';

        $result = $this->plugin->saveToFile($filePath, 'commonjs');

        $this->assertTrue($result);
        $this->assertFileExists($filePath);

        $content = file_get_contents($filePath);
        $this->assertStringContainsString('module.exports', $content);
    }

    public function test_saves_to_file_as_module(): void
    {
        $filePath = $this->tempDir . '/plugin.mjs';

        $result = $this->plugin->saveToFile($filePath, 'module');

        $this->assertTrue($result);
        $this->assertFileExists($filePath);

        $content = file_get_contents($filePath);
        $this->assertStringContainsString('export default', $content);
    }

    public function test_handles_multiple_themes(): void
    {
        // Create second theme
        $theme2Data = [
            'name' => 'ocean',
            'display_name' => 'Ocean',
            'version' => '1.0.0',
            'author' => 'Test Author',
            'description' => 'Ocean theme',
            'config' => [
                'colors' => [
                    'primary' => ['500' => '#06b6d4'],
                    'secondary' => ['500' => '#14b8a6'],
                    'accent' => ['500' => '#0891b2'],
                ],
                'fonts' => [
                    'sans' => ['Inter', 'system-ui', 'sans-serif'],
                ],
            ],
        ];

        mkdir($this->tempDir . '/ocean', 0777, true);
        file_put_contents(
            $this->tempDir . '/ocean/theme.json',
            json_encode($theme2Data)
        );

        // Create a new theme manager instance to load all themes
        $repository = new \Canvastack\Canvastack\Support\Theme\ThemeRepository();
        $files = new \Illuminate\Filesystem\Filesystem();
        $loader = new \Canvastack\Canvastack\Support\Theme\ThemeLoader($this->tempDir, $files);
        $cacheStore = new \Illuminate\Cache\ArrayStore();
        $cacheRepository = new \Illuminate\Cache\Repository($cacheStore);
        $cache = new \Canvastack\Canvastack\Support\Theme\ThemeCache($cacheRepository);
        $newManager = new \Canvastack\Canvastack\Support\Theme\ThemeManager($repository, $loader, $cache);
        $newManager->loadThemes();

        $newPlugin = new \Canvastack\Canvastack\Support\Theme\TailwindThemePlugin($newManager);
        $code = $newPlugin->generate();

        $this->assertStringContainsString('.theme-test-primary', $code);
        $this->assertStringContainsString('.theme-ocean-primary', $code);
    }

    public function test_handles_theme_without_gradients(): void
    {
        // Create theme without gradients
        $minimalTheme = [
            'name' => 'minimal',
            'display_name' => 'Minimal',
            'version' => '1.0.0',
            'author' => 'Test Author',
            'description' => 'Minimal theme',
            'config' => [
                'colors' => [
                    'primary' => ['500' => '#000000'],
                    'secondary' => ['500' => '#666666'],
                    'accent' => ['500' => '#333333'],
                ],
                'fonts' => [
                    'sans' => ['Inter', 'system-ui', 'sans-serif'],
                ],
            ],
        ];

        mkdir($this->tempDir . '/minimal', 0777, true);
        file_put_contents(
            $this->tempDir . '/minimal/theme.json',
            json_encode($minimalTheme)
        );

        // Create a new theme manager instance to load all themes
        $repository = new \Canvastack\Canvastack\Support\Theme\ThemeRepository();
        $files = new \Illuminate\Filesystem\Filesystem();
        $loader = new \Canvastack\Canvastack\Support\Theme\ThemeLoader($this->tempDir, $files);
        $cacheStore = new \Illuminate\Cache\ArrayStore();
        $cacheRepository = new \Illuminate\Cache\Repository($cacheStore);
        $cache = new \Canvastack\Canvastack\Support\Theme\ThemeCache($cacheRepository);
        $newManager = new \Canvastack\Canvastack\Support\Theme\ThemeManager($repository, $loader, $cache);
        $newManager->loadThemes();

        $newPlugin = new \Canvastack\Canvastack\Support\Theme\TailwindThemePlugin($newManager);
        $code = $newPlugin->generate();

        // Should still generate color utilities
        $this->assertStringContainsString('.theme-minimal-primary', $code);
    }
}
