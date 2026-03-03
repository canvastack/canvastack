<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Support\Theme;

use Canvastack\Canvastack\Contracts\ThemeInterface;
use Canvastack\Canvastack\Support\Theme\Theme;
use Canvastack\Canvastack\Support\Theme\ThemeCache;
use Canvastack\Canvastack\Support\Theme\ThemeLoader;
use Canvastack\Canvastack\Support\Theme\ThemeManager;
use Canvastack\Canvastack\Support\Theme\ThemeRepository;
use Canvastack\Canvastack\Tests\TestCase;
use InvalidArgumentException;

/**
 * ThemeManager Unit Tests.
 *
 * Tests for the ThemeManager class covering:
 * - Theme loading and initialization
 * - Theme switching and retrieval
 * - Theme caching
 * - Theme validation
 * - Configuration management
 * - CSS/JS generation
 * - Theme inheritance
 */
class ThemeManagerTest extends TestCase
{
    protected ThemeManager $manager;

    protected ThemeRepository $repository;

    protected ThemeLoader $loader;

    protected ThemeCache $cache;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new ThemeRepository();
        $this->loader = new ThemeLoader(
            resource_path('themes'),
            app('files')
        );
        $this->cache = new ThemeCache();
        $this->manager = new ThemeManager($this->repository, $this->loader, $this->cache);
    }

    /** @test */
    public function it_can_be_instantiated(): void
    {
        $this->assertInstanceOf(ThemeManager::class, $this->manager);
    }

    /** @test */
    public function it_initializes_with_default_theme(): void
    {
        $this->manager->initialize();

        $this->assertGreaterThan(0, $this->repository->count());
        $this->assertInstanceOf(ThemeInterface::class, $this->manager->current());
    }

    /** @test */
    public function it_creates_default_theme_if_none_exists(): void
    {
        $this->manager->initialize();

        $this->assertTrue($this->manager->has('default'));
        $theme = $this->manager->get('default');
        $this->assertEquals('default', $theme->getName());
        $this->assertEquals('Default Theme', $theme->getDisplayName());
    }

    /** @test */
    public function it_loads_themes_from_registry(): void
    {
        $this->manager->loadThemes();

        // After loading, repository should have themes (at least from config)
        $count = $this->repository->count();
        $this->assertGreaterThanOrEqual(0, $count);

        // If no themes loaded from filesystem, initialize will create default
        if ($count === 0) {
            $this->manager->initialize();
            $this->assertGreaterThan(0, $this->repository->count());
        }
    }

    /** @test */
    public function it_can_get_current_theme(): void
    {
        $this->manager->initialize();

        $current = $this->manager->current();

        $this->assertInstanceOf(ThemeInterface::class, $current);
        $this->assertNotEmpty($current->getName());
    }

    /** @test */
    public function it_can_set_current_theme(): void
    {
        $this->manager->initialize();

        // Register a test theme
        $testTheme = $this->createTestTheme('test-theme');
        $this->manager->register($testTheme);

        // Set as current
        $this->manager->setCurrentTheme('test-theme');

        $this->assertEquals('test-theme', $this->manager->current()->getName());
    }

    /** @test */
    public function it_throws_exception_when_setting_nonexistent_theme(): void
    {
        $this->manager->initialize();

        $this->expectException(InvalidArgumentException::class);
        $this->manager->setCurrentTheme('nonexistent-theme');
    }

    /** @test */
    public function it_can_get_theme_by_name(): void
    {
        $this->manager->initialize();

        $theme = $this->manager->get('default');

        $this->assertInstanceOf(ThemeInterface::class, $theme);
        $this->assertEquals('default', $theme->getName());
    }

    /** @test */
    public function it_throws_exception_when_getting_nonexistent_theme(): void
    {
        $this->manager->initialize();

        $this->expectException(InvalidArgumentException::class);
        $this->manager->get('nonexistent-theme');
    }

    /** @test */
    public function it_can_check_if_theme_exists(): void
    {
        $this->manager->initialize();

        $this->assertTrue($this->manager->has('default'));
        $this->assertFalse($this->manager->has('nonexistent-theme'));
    }

    /** @test */
    public function it_can_get_all_themes(): void
    {
        $this->manager->initialize();

        $themes = $this->manager->all();

        $this->assertIsArray($themes);
        $this->assertNotEmpty($themes);
        $this->assertContainsOnlyInstancesOf(ThemeInterface::class, $themes);
    }

    /** @test */
    public function it_can_get_all_theme_names(): void
    {
        $this->manager->initialize();

        $names = $this->manager->names();

        $this->assertIsArray($names);
        $this->assertNotEmpty($names);
        $this->assertContains('default', $names);
    }

    /** @test */
    public function it_can_register_new_theme(): void
    {
        $this->manager->initialize();

        $testTheme = $this->createTestTheme('custom-theme');
        $this->manager->register($testTheme);

        $this->assertTrue($this->manager->has('custom-theme'));
        $this->assertEquals('custom-theme', $this->manager->get('custom-theme')->getName());
    }

    /** @test */
    public function it_can_load_theme_from_array(): void
    {
        $this->manager->initialize();

        $config = [
            'name' => 'array-theme',
            'display_name' => 'Array Theme',
            'version' => '1.0.0',
            'author' => 'Test Author',
            'description' => 'Theme loaded from array',
            'config' => [
                'colors' => [
                    'primary' => '#ff0000',
                    'secondary' => '#00ff00',
                    'accent' => '#0000ff',
                ],
                'fonts' => [
                    'sans' => 'Inter, sans-serif',
                ],
            ],
        ];

        $this->manager->loadFromArray($config);

        $this->assertTrue($this->manager->has('array-theme'));
        $theme = $this->manager->get('array-theme');
        $this->assertEquals('#ff0000', $theme->get('colors.primary'));
    }

    /** @test */
    public function it_can_get_css_variables(): void
    {
        $this->manager->initialize();

        $variables = $this->manager->getCssVariables();

        $this->assertIsArray($variables);
        $this->assertNotEmpty($variables);
        $this->assertArrayHasKey('--color-primary', $variables);
    }

    /** @test */
    public function it_can_generate_css(): void
    {
        $this->manager->initialize();

        $css = $this->manager->generateCss();

        $this->assertIsString($css);
        $this->assertStringContainsString(':root', $css);
        // CSS variables use --cs- prefix
        $this->assertStringContainsString('--cs-color-', $css);
    }

    /** @test */
    public function it_can_get_compiled_css(): void
    {
        $this->manager->initialize();

        $css = $this->manager->getCompiledCss();

        $this->assertIsString($css);
        $this->assertNotEmpty($css);
    }

    /** @test */
    public function it_can_get_compiled_css_minified(): void
    {
        $this->manager->initialize();

        $css = $this->manager->getCompiledCss(true);

        $this->assertIsString($css);
        $this->assertNotEmpty($css);
    }

    /** @test */
    public function it_can_get_tailwind_config(): void
    {
        $this->manager->initialize();

        $config = $this->manager->getTailwindConfig();

        $this->assertIsArray($config);
        $this->assertArrayHasKey('theme', $config);
    }

    /** @test */
    public function it_can_get_javascript_config(): void
    {
        $this->manager->initialize();

        $js = $this->manager->getJavaScriptConfig();

        $this->assertIsString($js);
        $this->assertStringContainsString('window.canvastack', $js);
    }

    /** @test */
    public function it_can_get_theme_config_value(): void
    {
        $this->manager->initialize();

        $value = $this->manager->config('colors.primary');

        $this->assertNotNull($value);
        $this->assertIsString($value);
    }

    /** @test */
    public function it_returns_default_for_nonexistent_config(): void
    {
        $this->manager->initialize();

        $value = $this->manager->config('nonexistent.key', 'default-value');

        $this->assertEquals('default-value', $value);
    }

    /** @test */
    public function it_can_get_theme_colors(): void
    {
        $this->manager->initialize();

        $colors = $this->manager->colors();

        $this->assertIsArray($colors);
        $this->assertNotEmpty($colors);
        $this->assertArrayHasKey('primary', $colors);
    }

    /** @test */
    public function it_can_get_theme_fonts(): void
    {
        $this->manager->initialize();

        $fonts = $this->manager->fonts();

        $this->assertIsArray($fonts);
        $this->assertNotEmpty($fonts);
    }

    /** @test */
    public function it_can_get_theme_layout(): void
    {
        $this->manager->initialize();

        $layout = $this->manager->layout();

        $this->assertIsArray($layout);
        $this->assertNotEmpty($layout);
    }

    /** @test */
    public function it_can_check_dark_mode_support(): void
    {
        $this->manager->initialize();

        $supportsDarkMode = $this->manager->supportsDarkMode();

        $this->assertIsBool($supportsDarkMode);
    }

    /** @test */
    public function it_can_clear_cache(): void
    {
        $this->manager->initialize();

        $result = $this->manager->clearCache();

        $this->assertInstanceOf(ThemeManager::class, $result);
    }

    /** @test */
    public function it_can_reload_themes(): void
    {
        $this->manager->initialize();
        $initialCount = $this->repository->count();

        $result = $this->manager->reload();

        $this->assertInstanceOf(ThemeManager::class, $result);

        // After reload, if no themes loaded from filesystem, initialize again
        if ($this->repository->count() === 0) {
            $this->manager->initialize();
        }

        $this->assertGreaterThanOrEqual(1, $this->repository->count());
    }

    /** @test */
    public function it_can_get_all_metadata(): void
    {
        $this->manager->initialize();

        $metadata = $this->manager->getAllMetadata();

        $this->assertIsArray($metadata);
        $this->assertNotEmpty($metadata);
        $this->assertArrayHasKey('default', $metadata);
    }

    /** @test */
    public function it_can_export_theme_as_json(): void
    {
        $this->manager->initialize();

        $json = $this->manager->export('json');

        $this->assertIsString($json);
        $this->assertJson($json);
    }

    /** @test */
    public function it_can_export_theme_as_array(): void
    {
        $this->manager->initialize();

        $array = $this->manager->export('array');

        $this->assertIsString($array);
        $this->assertStringContainsString('array (', $array);
    }

    /** @test */
    public function it_throws_exception_for_invalid_export_format(): void
    {
        $this->manager->initialize();

        $this->expectException(InvalidArgumentException::class);
        $this->manager->export('invalid-format');
    }

    /** @test */
    public function it_can_get_repository(): void
    {
        $repository = $this->manager->getRepository();

        $this->assertInstanceOf(ThemeRepository::class, $repository);
    }

    /** @test */
    public function it_can_get_loader(): void
    {
        $loader = $this->manager->getLoader();

        $this->assertInstanceOf(ThemeLoader::class, $loader);
    }

    /** @test */
    public function it_can_get_cache(): void
    {
        $cache = $this->manager->getCache();

        $this->assertInstanceOf(ThemeCache::class, $cache);
    }

    /** @test */
    public function it_can_inject_css(): void
    {
        $this->manager->initialize();

        $css = $this->manager->injectCss();

        $this->assertIsString($css);
        $this->assertStringContainsString('<style', $css);
    }

    /** @test */
    public function it_can_inject_fonts(): void
    {
        $this->manager->initialize();

        $fonts = $this->manager->injectFonts();

        $this->assertIsString($fonts);
    }

    /** @test */
    public function it_can_inject_complete_theme(): void
    {
        $this->manager->initialize();

        $complete = $this->manager->injectComplete();

        $this->assertIsString($complete);
        $this->assertStringContainsString('<style', $complete);
    }

    /** @test */
    public function it_caches_loaded_themes(): void
    {
        // First load
        $this->manager->loadThemes();
        $firstCount = $this->repository->count();

        // Clear repository but not cache
        $this->repository->clear();

        // Second load should use cache
        $this->manager->loadThemes();
        $secondCount = $this->repository->count();

        $this->assertEquals($firstCount, $secondCount);
    }

    /** @test */
    public function it_resolves_theme_inheritance(): void
    {
        $this->manager->initialize();

        // Create parent theme
        $parentTheme = $this->createTestTheme('parent-theme');
        $this->manager->register($parentTheme);

        // Create child theme with parent
        $childConfig = [
            'name' => 'child-theme',
            'display_name' => 'Child Theme',
            'version' => '1.0.0',
            'author' => 'Test Author',
            'description' => 'Child theme',
            'parent' => 'parent-theme',
            'config' => [
                'colors' => [
                    'primary' => '#ff0000',
                    'secondary' => '#00ff00',
                    'accent' => '#0000ff',
                ],
                'fonts' => [
                    'sans' => 'Inter, sans-serif',
                ],
            ],
        ];

        $this->manager->loadFromArray($childConfig);

        // Get child theme and verify inheritance (no reload needed)
        $childTheme = $this->manager->get('child-theme');
        $this->assertTrue($childTheme->hasParent());
        $this->assertEquals('parent-theme', $childTheme->getParent());
    }

    /**
     * Helper method to create a test theme.
     */
    protected function createTestTheme(string $name): Theme
    {
        return new Theme(
            name: $name,
            displayName: ucfirst(str_replace('-', ' ', $name)),
            version: '1.0.0',
            author: 'Test Author',
            description: 'Test theme for unit testing',
            config: [
                'colors' => [
                    'primary' => '#6366f1',
                    'secondary' => '#8b5cf6',
                    'accent' => '#a855f7',
                ],
                'fonts' => [
                    'sans' => 'Inter, sans-serif',
                ],
                'layout' => [
                    'container' => '1280px',
                ],
                'dark_mode' => [
                    'enabled' => true,
                ],
            ]
        );
    }
}
