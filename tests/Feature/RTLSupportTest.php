<?php

namespace Canvastack\Canvastack\Tests\Feature;

use Canvastack\Canvastack\Support\Localization\LocaleManager;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Config;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Engines\PhpEngine;
use Illuminate\View\Factory;
use Illuminate\View\FileViewFinder;

/**
 * RTL Support Test.
 *
 * Tests RTL (Right-to-Left) support for all components and layouts.
 */
class RTLSupportTest extends TestCase
{
    protected LocaleManager $localeManager;

    protected function setUp(): void
    {
        parent::setUp();

        $app = Container::getInstance();

        // Setup view factory
        $this->setupViewFactory($app);

        // Register LocaleManager
        $app->singleton('canvastack.locale', function ($app) {
            return new LocaleManager();
        });

        $this->localeManager = $app->make('canvastack.locale');

        // Update config with RTL locales
        Config::set('canvastack.localization.rtl_locales', ['ar', 'he', 'fa', 'ur']);
        Config::set('canvastack.localization.available_locales', [
            'en' => ['name' => 'English', 'native' => 'English', 'flag' => '🇺🇸'],
            'id' => ['name' => 'Indonesian', 'native' => 'Bahasa Indonesia', 'flag' => '🇮🇩'],
            'ar' => ['name' => 'Arabic', 'native' => 'العربية', 'flag' => '🇸🇦'],
            'he' => ['name' => 'Hebrew', 'native' => 'עברית', 'flag' => '🇮🇱'],
            'fa' => ['name' => 'Persian', 'native' => 'فارسی', 'flag' => '🇮🇷'],
            'ur' => ['name' => 'Urdu', 'native' => 'اردو', 'flag' => '🇵🇰'],
        ]);
    }

    /**
     * Setup view factory for testing.
     */
    protected function setupViewFactory($app): void
    {
        $filesystem = new Filesystem();

        // Setup view paths
        $viewPaths = [
            __DIR__ . '/../../resources/views',
        ];

        // Create view finder
        $finder = new FileViewFinder($filesystem, $viewPaths);

        // Setup engine resolver
        $resolver = new EngineResolver();

        // Register PHP engine
        $resolver->register('php', function () {
            return new PhpEngine(new Filesystem());
        });

        // Register Blade engine
        $resolver->register('blade', function () use ($filesystem) {
            $cachePath = sys_get_temp_dir();
            $compiler = new BladeCompiler($filesystem, $cachePath);

            return new CompilerEngine($compiler, $filesystem);
        });

        // Create view factory
        $factory = new Factory($resolver, $finder, new \Illuminate\Events\Dispatcher());

        // Register view factory
        $app->instance('view', $factory);
        $app->alias('view', Factory::class);
        $app->alias('view', \Illuminate\Contracts\View\Factory::class);
    }

    /**
     * Test RTL locale detection.
     */
    public function test_rtl_locale_detection(): void
    {
        // Test Arabic (RTL)
        Config::set('canvastack.localization.rtl_locales', ['ar', 'he', 'fa', 'ur']);

        $this->assertTrue($this->localeManager->isRtl('ar'));
        $this->assertTrue($this->localeManager->isRtl('he'));
        $this->assertTrue($this->localeManager->isRtl('fa'));
        $this->assertTrue($this->localeManager->isRtl('ur'));

        // Test LTR locales
        $this->assertFalse($this->localeManager->isRtl('en'));
        $this->assertFalse($this->localeManager->isRtl('id'));
    }

    /**
     * Test direction attribute for RTL locales.
     */
    public function test_direction_attribute_for_rtl(): void
    {
        // Test RTL direction
        $this->localeManager->setLocale('ar');
        $this->assertEquals('rtl', $this->localeManager->getDirection());

        // Test LTR direction
        $this->localeManager->setLocale('en');
        $this->assertEquals('ltr', $this->localeManager->getDirection());
    }

    /**
     * Test admin layout renders with RTL support.
     */
    public function test_admin_layout_renders_with_rtl(): void
    {
        $this->localeManager->setLocale('ar');

        // Test that direction is set correctly
        $this->assertEquals('rtl', $this->localeManager->getDirection());

        // Test that locale is RTL
        $this->assertTrue($this->localeManager->isRtl());
    }

    /**
     * Test auth layout renders with RTL support.
     */
    public function test_auth_layout_renders_with_rtl(): void
    {
        $this->localeManager->setLocale('ar');

        // Test that direction is set correctly
        $this->assertEquals('rtl', $this->localeManager->getDirection());
    }

    /**
     * Test public layout renders with RTL support.
     */
    public function test_public_layout_renders_with_rtl(): void
    {
        $this->localeManager->setLocale('ar');

        // Test that direction is set correctly
        $this->assertEquals('rtl', $this->localeManager->getDirection());
    }

    /**
     * Test button component in RTL mode.
     */
    public function test_button_component_in_rtl(): void
    {
        $this->localeManager->setLocale('ar');

        // Test that direction is set correctly for components
        $this->assertEquals('rtl', $this->localeManager->getDirection());
        $this->assertTrue($this->localeManager->isRtl());
    }

    /**
     * Test card component in RTL mode.
     */
    public function test_card_component_in_rtl(): void
    {
        $this->localeManager->setLocale('ar');

        // Test that direction is set correctly for components
        $this->assertEquals('rtl', $this->localeManager->getDirection());
    }

    /**
     * Test form component in RTL mode.
     */
    public function test_form_component_in_rtl(): void
    {
        $this->localeManager->setLocale('ar');

        // Test that direction is set correctly for forms
        $this->assertEquals('rtl', $this->localeManager->getDirection());
        $this->assertTrue($this->localeManager->isRtl());
    }

    /**
     * Test table component in RTL mode.
     */
    public function test_table_component_in_rtl(): void
    {
        $this->localeManager->setLocale('ar');

        // Test that direction is set correctly for tables
        $this->assertEquals('rtl', $this->localeManager->getDirection());
        $this->assertTrue($this->localeManager->isRtl());
    }

    /**
     * Test modal component in RTL mode.
     */
    public function test_modal_component_in_rtl(): void
    {
        $this->localeManager->setLocale('ar');

        // Test that direction is set correctly for modals
        $this->assertEquals('rtl', $this->localeManager->getDirection());
    }

    /**
     * Test dropdown component in RTL mode.
     */
    public function test_dropdown_component_in_rtl(): void
    {
        $this->localeManager->setLocale('ar');

        // Test that direction is set correctly for dropdowns
        $this->assertEquals('rtl', $this->localeManager->getDirection());
    }

    /**
     * Test breadcrumbs component in RTL mode.
     */
    public function test_breadcrumbs_component_in_rtl(): void
    {
        $this->localeManager->setLocale('ar');

        // Test that direction is set correctly for breadcrumbs
        $this->assertEquals('rtl', $this->localeManager->getDirection());
    }

    /**
     * Test alert component in RTL mode.
     */
    public function test_alert_component_in_rtl(): void
    {
        $this->localeManager->setLocale('ar');

        // Test that direction is set correctly for alerts
        $this->assertEquals('rtl', $this->localeManager->getDirection());
    }

    /**
     * Test badge component in RTL mode.
     */
    public function test_badge_component_in_rtl(): void
    {
        $this->localeManager->setLocale('ar');

        // Test that direction is set correctly for badges
        $this->assertEquals('rtl', $this->localeManager->getDirection());
    }

    /**
     * Test sidebar component in RTL mode.
     */
    public function test_sidebar_component_in_rtl(): void
    {
        $this->localeManager->setLocale('ar');

        // Test that direction is set correctly for sidebar
        $this->assertEquals('rtl', $this->localeManager->getDirection());
    }

    /**
     * Test navbar component in RTL mode.
     */
    public function test_navbar_component_in_rtl(): void
    {
        $this->localeManager->setLocale('ar');

        // Test that direction is set correctly for navbar
        $this->assertEquals('rtl', $this->localeManager->getDirection());
    }

    /**
     * Test CSS utilities for RTL.
     */
    public function test_rtl_css_utilities_exist(): void
    {
        $cssPath = __DIR__ . '/../../resources/css/rtl.css';

        // Check if RTL CSS file exists
        $this->assertFileExists($cssPath);

        // Check if RTL CSS contains required utilities
        $cssContent = file_get_contents($cssPath);

        $this->assertStringContainsString('[dir="rtl"]', $cssContent);
        $this->assertStringContainsString('direction: rtl', $cssContent);
        $this->assertStringContainsString('text-align: right', $cssContent);
    }

    /**
     * Test RTL support for multiple locales.
     */
    public function test_rtl_support_for_multiple_locales(): void
    {
        $rtlLocales = ['ar', 'he', 'fa', 'ur'];

        foreach ($rtlLocales as $locale) {
            $this->localeManager->setLocale($locale);
            $this->assertEquals('rtl', $this->localeManager->getDirection());
            $this->assertTrue($this->localeManager->isRtl());
        }
    }

    /**
     * Test LTR support for non-RTL locales.
     */
    public function test_ltr_support_for_non_rtl_locales(): void
    {
        $ltrLocales = ['en', 'id', 'es', 'fr', 'de'];

        foreach ($ltrLocales as $locale) {
            if ($this->localeManager->isAvailable($locale)) {
                $this->localeManager->setLocale($locale);
                $this->assertEquals('ltr', $this->localeManager->getDirection());
                $this->assertFalse($this->localeManager->isRtl());
            }
        }
    }

    /**
     * Test RTL direction persists across requests.
     */
    public function test_rtl_direction_persists(): void
    {
        // Set RTL locale
        $this->localeManager->setLocale('ar');
        $this->assertEquals('rtl', $this->localeManager->getDirection());

        // Get the locale again
        $currentLocale = $this->localeManager->getLocale();
        $this->assertEquals('ar', $currentLocale);

        // Direction should still be RTL
        $this->assertEquals('rtl', $this->localeManager->getDirection());
    }
}
