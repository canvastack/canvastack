<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Support\Theme;

use Canvastack\Canvastack\Support\Theme\Theme;
use Canvastack\Canvastack\Support\Theme\ThemeLoader;
use Canvastack\Canvastack\Support\Theme\ThemeValidator;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Config;

class ThemeLoaderRegistryTest extends TestCase
{
    protected ThemeLoader $loader;

    protected Filesystem $files;

    protected function setUp(): void
    {
        parent::setUp();

        $this->files = new Filesystem();
        $this->loader = new ThemeLoader(
            resource_path('themes'),
            $this->files,
            new ThemeValidator()
        );
    }

    /** @test */
    public function it_loads_themes_from_config_registry(): void
    {
        // Set up test config
        config([
            'canvastack-ui.theme.registry' => [
                [
                    'name' => 'test-theme',
                    'display_name' => 'Test Theme',
                    'version' => '1.0.0',
                    'author' => 'Test Author',
                    'description' => 'A test theme',
                    'config' => [
                        'colors' => [
                            'primary' => '#6366f1',
                            'secondary' => '#8b5cf6',
                            'accent' => '#a855f7',
                        ],
                        'fonts' => [
                            'sans' => 'Inter',
                        ],
                    ],
                ],
            ],
        ]);

        $themes = $this->loader->loadFromRegistry();

        $this->assertIsArray($themes);
        $this->assertNotEmpty($themes);
        $this->assertInstanceOf(Theme::class, $themes[0]);
        $this->assertEquals('test-theme', $themes[0]->getName());
    }

    /** @test */
    public function it_skips_invalid_themes_in_registry(): void
    {
        config([
            'canvastack-ui.theme.registry' => [
                [
                    'name' => 'valid-theme',
                    'display_name' => 'Valid Theme',
                    'version' => '1.0.0',
                    'author' => 'Test Author',
                    'description' => 'A valid theme',
                    'config' => [
                        'colors' => [
                            'primary' => '#6366f1',
                            'secondary' => '#8b5cf6',
                            'accent' => '#a855f7',
                        ],
                        'fonts' => [
                            'sans' => 'Inter',
                        ],
                    ],
                ],
                [
                    'name' => 'invalid-theme',
                    // Missing required fields
                ],
            ],
        ]);

        $themes = $this->loader->loadFromRegistry();

        // Should only load the valid theme
        $this->assertCount(1, $themes);
        $this->assertEquals('valid-theme', $themes[0]->getName());
    }

    /** @test */
    public function it_loads_all_themes_from_registry_and_filesystem(): void
    {
        config([
            'canvastack-ui.theme.registry' => [
                [
                    'name' => 'registry-theme',
                    'display_name' => 'Registry Theme',
                    'version' => '1.0.0',
                    'author' => 'Test Author',
                    'description' => 'A theme from registry',
                    'config' => [
                        'colors' => [
                            'primary' => '#6366f1',
                            'secondary' => '#8b5cf6',
                            'accent' => '#a855f7',
                        ],
                        'fonts' => [
                            'sans' => 'Inter',
                        ],
                    ],
                ],
            ],
        ]);

        $themes = $this->loader->loadAll();

        $this->assertIsArray($themes);

        // Should have at least the registry theme
        $themeNames = array_map(fn ($theme) => $theme->getName(), $themes);
        $this->assertContains('registry-theme', $themeNames);
    }

    /** @test */
    public function it_returns_empty_array_when_no_registry_configured(): void
    {
        config(['canvastack-ui.theme.registry' => []]);

        $themes = $this->loader->loadFromRegistry();

        $this->assertIsArray($themes);
        $this->assertEmpty($themes);
    }

    /** @test */
    public function it_loads_default_themes_from_config(): void
    {
        // Mock config to return default themes
        Config::set('canvastack-ui.theme.registry', [
            [
                'name' => 'gradient',
                'display_name' => 'Gradient Theme',
                'version' => '1.0.0',
                'author' => 'CanvaStack',
                'description' => 'Gradient theme',
                'config' => [
                    'colors' => [
                        'primary' => ['500' => '#6366f1'],
                        'secondary' => ['500' => '#8b5cf6'],
                        'accent' => ['500' => '#a855f7'],
                    ],
                    'fonts' => [
                        'sans' => ['Inter', 'system-ui', 'sans-serif'],
                    ],
                ],
            ],
            [
                'name' => 'ocean',
                'display_name' => 'Ocean Theme',
                'version' => '1.0.0',
                'author' => 'CanvaStack',
                'description' => 'Ocean theme',
                'config' => [
                    'colors' => [
                        'primary' => ['500' => '#0ea5e9'],
                        'secondary' => ['500' => '#06b6d4'],
                        'accent' => ['500' => '#0891b2'],
                    ],
                    'fonts' => [
                        'sans' => ['Inter', 'system-ui', 'sans-serif'],
                    ],
                ],
            ],
            [
                'name' => 'forest',
                'display_name' => 'Forest Theme',
                'version' => '1.0.0',
                'author' => 'CanvaStack',
                'description' => 'Forest theme',
                'config' => [
                    'colors' => [
                        'primary' => ['500' => '#10b981'],
                        'secondary' => ['500' => '#059669'],
                        'accent' => ['500' => '#047857'],
                    ],
                    'fonts' => [
                        'sans' => ['Inter', 'system-ui', 'sans-serif'],
                    ],
                ],
            ],
            [
                'name' => 'midnight',
                'display_name' => 'Midnight Theme',
                'version' => '1.0.0',
                'author' => 'CanvaStack',
                'description' => 'Midnight theme',
                'config' => [
                    'colors' => [
                        'primary' => ['500' => '#1e293b'],
                        'secondary' => ['500' => '#334155'],
                        'accent' => ['500' => '#475569'],
                    ],
                    'fonts' => [
                        'sans' => ['Inter', 'system-ui', 'sans-serif'],
                    ],
                ],
            ],
        ]);

        $themes = $this->loader->loadFromRegistry();

        $this->assertIsArray($themes);
        $this->assertNotEmpty($themes);

        // Check for default themes
        $themeNames = array_map(fn ($theme) => $theme->getName(), $themes);

        $this->assertContains('gradient', $themeNames);
        $this->assertContains('ocean', $themeNames);
        $this->assertContains('forest', $themeNames);
        $this->assertContains('midnight', $themeNames);
    }

    /** @test */
    public function it_validates_themes_loaded_from_registry(): void
    {
        config([
            'canvastack-ui.theme.registry' => [
                [
                    'name' => 'InvalidName',  // Invalid kebab-case
                    'display_name' => 'Invalid Theme',
                    'version' => '1.0.0',
                    'author' => 'Test Author',
                    'description' => 'An invalid theme',
                    'config' => [
                        'colors' => [
                            'primary' => '#6366f1',
                            'secondary' => '#8b5cf6',
                            'accent' => '#a855f7',
                        ],
                        'fonts' => [
                            'sans' => 'Inter',
                        ],
                    ],
                ],
            ],
        ]);

        $themes = $this->loader->loadFromRegistry();

        // Invalid theme should be skipped
        $this->assertEmpty($themes);
    }
}
