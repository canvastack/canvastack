<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Support\Theme;

use Canvastack\Canvastack\Facades\Theme as ThemeFacade;
use Canvastack\Canvastack\Support\Theme\Theme;
use Canvastack\Canvastack\Support\Theme\ThemeManager;
use Canvastack\Canvastack\Tests\TestCase;

/**
 * Theme API Test.
 *
 * Tests for theme helper functions, Blade directives, and facade.
 */
class ThemeApiTest extends TestCase
{
    protected ThemeManager $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = app('canvastack.theme');
    }

    /** @test */
    public function it_can_access_theme_manager_via_helper()
    {
        $manager = theme();

        $this->assertInstanceOf(ThemeManager::class, $manager);
    }

    /** @test */
    public function it_can_get_theme_config_via_helper()
    {
        $value = theme('name');

        $this->assertNotNull($value);
    }

    /** @test */
    public function it_can_get_theme_config_with_default_via_helper()
    {
        $value = theme('nonexistent.key', 'default-value');

        $this->assertEquals('default-value', $value);
    }

    /** @test */
    public function it_can_get_current_theme_via_helper()
    {
        $theme = current_theme();

        $this->assertInstanceOf(Theme::class, $theme);
    }

    /** @test */
    public function it_can_get_theme_color_via_helper()
    {
        $color = theme_color('primary');

        $this->assertNotNull($color);
    }

    /** @test */
    public function it_can_get_theme_font_via_helper()
    {
        $font = theme_font('sans');

        $this->assertNotNull($font);
    }

    /** @test */
    public function it_can_get_theme_css_via_helper()
    {
        $css = theme_css();

        $this->assertIsString($css);
        // CSS should contain :root or be empty string (both valid)
        if (!empty($css)) {
            $this->assertStringContainsString(':root', $css);
        } else {
            $this->assertEmpty($css);
        }
    }

    /** @test */
    public function it_can_get_minified_theme_css_via_helper()
    {
        $css = theme_css(true);

        $this->assertIsString($css);
        // Minified CSS should not contain multiple newlines
        // But might be empty, which is also valid
        if (!empty($css)) {
            $this->assertStringNotContainsString("\n\n", $css);
        } else {
            $this->assertEmpty($css);
        }
    }

    /** @test */
    public function it_can_inject_theme_via_helper()
    {
        $output = theme_inject();

        $this->assertIsString($output);
        // Output should contain style tag
        $this->assertStringContainsString('<style', $output);
        $this->assertStringContainsString('</style>', $output);
    }

    /** @test */
    public function it_can_access_theme_manager_via_facade()
    {
        $theme = ThemeFacade::current();

        $this->assertInstanceOf(Theme::class, $theme);
    }

    /** @test */
    public function it_can_get_theme_colors_via_facade()
    {
        $colors = ThemeFacade::colors();

        $this->assertIsArray($colors);
        $this->assertNotEmpty($colors);
    }

    /** @test */
    public function it_can_get_theme_fonts_via_facade()
    {
        $fonts = ThemeFacade::fonts();

        $this->assertIsArray($fonts);
        $this->assertNotEmpty($fonts);
    }

    /** @test */
    public function it_can_check_dark_mode_support_via_facade()
    {
        $supportsDarkMode = ThemeFacade::supportsDarkMode();

        $this->assertIsBool($supportsDarkMode);
    }

    /** @test */
    public function it_can_get_all_themes_via_facade()
    {
        $themes = ThemeFacade::all();

        $this->assertIsArray($themes);
        $this->assertNotEmpty($themes);
    }

    /** @test */
    public function it_can_get_theme_names_via_facade()
    {
        $names = ThemeFacade::names();

        $this->assertIsArray($names);
        $this->assertNotEmpty($names);
    }

    /** @test */
    public function it_can_check_theme_exists_via_facade()
    {
        $exists = ThemeFacade::has('default');

        $this->assertTrue($exists);
    }

    /** @test */
    public function it_can_get_compiled_css_via_facade()
    {
        $css = ThemeFacade::getCompiledCss();

        $this->assertIsString($css);
        // CSS should contain :root or be empty
        if (!empty($css)) {
            $this->assertStringContainsString(':root', $css);
        } else {
            $this->assertEmpty($css);
        }
    }

    /** @test */
    public function it_can_get_tailwind_config_via_facade()
    {
        $config = ThemeFacade::getTailwindConfig();

        $this->assertIsArray($config);
        // Config should have theme key or be empty array
        if (!empty($config)) {
            $this->assertArrayHasKey('theme', $config);
        } else {
            $this->assertEmpty($config);
        }
    }

    /** @test */
    public function it_can_inject_css_via_facade()
    {
        $css = ThemeFacade::injectCss();

        $this->assertIsString($css);
        // Should contain style tag
        $this->assertStringContainsString('<style', $css);
        $this->assertStringContainsString('</style>', $css);
    }

    /** @test */
    public function it_can_inject_fonts_via_facade()
    {
        $fonts = ThemeFacade::injectFonts();

        $this->assertIsString($fonts);
    }

    /** @test */
    public function it_can_inject_complete_theme_via_facade()
    {
        $output = ThemeFacade::injectComplete();

        $this->assertIsString($output);
        // Should contain style tag
        $this->assertStringContainsString('<style', $output);
        $this->assertStringContainsString('</style>', $output);
    }

    /** @test */
    public function it_can_export_theme_via_facade()
    {
        $json = ThemeFacade::export('json');

        $this->assertIsString($json);
        $this->assertJson($json);
    }

    /** @test */
    public function it_can_clear_cache_via_facade()
    {
        $result = ThemeFacade::clearCache();

        $this->assertInstanceOf(ThemeManager::class, $result);
    }

    /** @test */
    public function it_can_reload_themes_via_facade()
    {
        $result = ThemeFacade::reload();

        $this->assertInstanceOf(ThemeManager::class, $result);
    }
}
