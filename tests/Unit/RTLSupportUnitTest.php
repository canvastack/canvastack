<?php

namespace Canvastack\Canvastack\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * RTL Support Unit Test.
 *
 * Simple unit tests for RTL CSS utilities and configuration.
 */
class RTLSupportUnitTest extends TestCase
{
    /**
     * Test RTL CSS file exists.
     */
    public function test_rtl_css_file_exists(): void
    {
        $cssPath = __DIR__ . '/../../resources/css/rtl.css';

        $this->assertFileExists($cssPath, 'RTL CSS file should exist');
    }

    /**
     * Test RTL CSS contains required utilities.
     */
    public function test_rtl_css_contains_required_utilities(): void
    {
        $cssPath = __DIR__ . '/../../resources/css/rtl.css';
        $cssContent = file_get_contents($cssPath);

        // Check for basic RTL directives
        $this->assertStringContainsString('[dir="rtl"]', $cssContent);
        $this->assertStringContainsString('direction: rtl', $cssContent);
        $this->assertStringContainsString('text-align: right', $cssContent);

        // Check for component-specific RTL support
        $this->assertStringContainsString('sidebar', $cssContent);
        $this->assertStringContainsString('navbar', $cssContent);
        $this->assertStringContainsString('datatable', $cssContent);
        $this->assertStringContainsString('form', $cssContent);
    }

    /**
     * Test RTL CSS is imported in main CSS.
     */
    public function test_rtl_css_imported_in_main_css(): void
    {
        $mainCssPath = __DIR__ . '/../../resources/css/canvastack.css';
        $mainCssContent = file_get_contents($mainCssPath);

        $this->assertStringContainsString("@import './rtl.css'", $mainCssContent);
    }

    /**
     * Test RTL configuration exists.
     */
    public function test_rtl_configuration_exists(): void
    {
        $configPath = __DIR__ . '/../../config/canvastack.php';
        $this->assertFileExists($configPath);

        $config = include $configPath;

        // Check for localization configuration
        $this->assertArrayHasKey('localization', $config);
        $this->assertArrayHasKey('rtl_locales', $config['localization']);

        // Check RTL locales are defined
        $rtlLocales = $config['localization']['rtl_locales'];
        $this->assertIsArray($rtlLocales);
        $this->assertContains('ar', $rtlLocales);
        $this->assertContains('he', $rtlLocales);
        $this->assertContains('fa', $rtlLocales);
        $this->assertContains('ur', $rtlLocales);
    }

    /**
     * Test JavaScript RTL manager exists.
     */
    public function test_javascript_rtl_manager_exists(): void
    {
        $jsPath = __DIR__ . '/../../resources/js/canvastack.js';
        $jsContent = file_get_contents($jsPath);

        // Check for RTL Manager class
        $this->assertStringContainsString('class RTLManager', $jsContent);
        $this->assertStringContainsString('setDirection', $jsContent);
        $this->assertStringContainsString('getDirection', $jsContent);
        $this->assertStringContainsString('isRTL', $jsContent);
        $this->assertStringContainsString('updateLayoutForRTL', $jsContent);
    }

    /**
     * Test layout files have dir attribute support.
     */
    public function test_layout_files_have_dir_attribute(): void
    {
        $layouts = [
            __DIR__ . '/../../resources/views/components/layouts/admin.blade.php',
            __DIR__ . '/../../resources/views/components/layouts/auth.blade.php',
            __DIR__ . '/../../resources/views/components/layouts/public.blade.php',
        ];

        foreach ($layouts as $layoutPath) {
            $this->assertFileExists($layoutPath);

            $layoutContent = file_get_contents($layoutPath);

            // Check for dir attribute in HTML tag
            $this->assertStringContainsString('dir=', $layoutContent);
            $this->assertStringContainsString("app('canvastack.locale')->getDirection()", $layoutContent);
        }
    }

    /**
     * Test RTL CSS utilities cover all necessary properties.
     */
    public function test_rtl_css_utilities_comprehensive(): void
    {
        $cssPath = __DIR__ . '/../../resources/css/rtl.css';
        $cssContent = file_get_contents($cssPath);

        // Check for margin utilities
        $this->assertStringContainsString('.ms-', $cssContent);
        $this->assertStringContainsString('.me-', $cssContent);

        // Check for padding utilities
        $this->assertStringContainsString('.ps-', $cssContent);
        $this->assertStringContainsString('.pe-', $cssContent);

        // Check for border utilities
        $this->assertStringContainsString('.border-start', $cssContent);
        $this->assertStringContainsString('.border-end', $cssContent);

        // Check for position utilities
        $this->assertStringContainsString('.start-0', $cssContent);
        $this->assertStringContainsString('.end-0', $cssContent);

        // Check for flexbox utilities
        $this->assertStringContainsString('.flex-row-start', $cssContent);
        $this->assertStringContainsString('.justify-start', $cssContent);
    }
}
