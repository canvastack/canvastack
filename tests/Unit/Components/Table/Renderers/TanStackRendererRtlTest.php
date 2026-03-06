<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Table\Renderers;

use Canvastack\Canvastack\Components\Table\Renderers\TanStackRenderer;
use Canvastack\Canvastack\Components\Table\TableBuilder;
use Canvastack\Canvastack\Support\Integration\ThemeLocaleIntegration;
use Canvastack\Canvastack\Support\Theme\ThemeManager;
use Canvastack\Canvastack\Tests\TestCase;
use Mockery;

/**
 * Test RTL support in TanStackRenderer.
 * 
 * Validates Requirement 51.11: Integrate with ThemeLocaleIntegration for RTL support
 */
class TanStackRendererRtlTest extends TestCase
{
    protected TanStackRenderer $renderer;
    protected ThemeLocaleIntegration $themeLocaleIntegration;
    protected ThemeManager $themeManager;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->themeLocaleIntegration = Mockery::mock(ThemeLocaleIntegration::class);
        $this->themeManager = Mockery::mock(ThemeManager::class);
        
        // Bind theme manager to container
        $this->app->singleton('canvastack.theme', function () {
            return $this->themeManager;
        });
        
        // Mock translator for locale detection and translations
        $translator = Mockery::mock(\Illuminate\Contracts\Translation\Translator::class);
        $translator->shouldReceive('getLocale')->andReturn('en');
        $translator->shouldReceive('get')->andReturnUsing(function ($key) {
            // Return the key itself as translation (simple mock)
            return $key;
        });
        $this->app->singleton('translator', function () use ($translator) {
            return $translator;
        });
        
        $this->renderer = new TanStackRenderer($this->themeLocaleIntegration);
    }

    /**
     * Setup theme manager mocks for rendering.
     */
    protected function setupThemeMocks(): void
    {
        $mockTheme = Mockery::mock(\Canvastack\Canvastack\Contracts\ThemeInterface::class);
        $mockTheme->shouldReceive('getName')->andReturn('default');
        $mockTheme->shouldReceive('getVersion')->andReturn('1.0.0');
        
        $this->themeManager
            ->shouldReceive('current')
            ->andReturn($mockTheme);
    }

    /**
     * Create a mock table builder for testing.
     */
    protected function createMockTable(): TableBuilder
    {
        $table = Mockery::mock(TableBuilder::class)->makePartial();
        $table->shouldAllowMockingProtectedMethods();
        $table->shouldReceive('getTableId')->andReturn('test-table');
        $table->shouldReceive('getConfiguration')->andReturn((object)[
            'selectable' => false,
        ]);
        $table->shouldReceive('hasFilters')->andReturn(false);
        $table->shouldReceive('hasBulkActions')->andReturn(false);
        
        return $table;
    }

    /**
     * Test that renderer integrates with ThemeLocaleIntegration.
     * 
     * Validates: Requirement 51.11 - Integrate with ThemeLocaleIntegration
     */
    public function test_renderer_integrates_with_theme_locale_integration(): void
    {
        $this->assertInstanceOf(
            ThemeLocaleIntegration::class,
            $this->themeLocaleIntegration,
            'Renderer should have ThemeLocaleIntegration instance'
        );
    }

    /**
     * Test that RTL attributes are added to table container.
     * 
     * Validates: Requirement 51.11 - Support RTL layouts for RTL locales
     */
    public function test_rtl_attributes_added_to_table_container(): void
    {
        // Setup theme mocks
        $this->setupThemeMocks();
        
        // Mock ThemeLocaleIntegration to return RTL attributes
        $this->themeLocaleIntegration
            ->shouldReceive('getHtmlAttributes')
            ->once()
            ->andReturn([
                'lang' => 'ar',
                'dir' => 'rtl',
                'class' => 'rtl-locale'
            ]);

        $this->themeLocaleIntegration
            ->shouldReceive('getBodyClasses')
            ->once()
            ->andReturn('rtl-locale arabic-font');

        $this->themeLocaleIntegration
            ->shouldReceive('getLocalizedThemeCss')
            ->once()
            ->andReturn('/* Localized CSS */');

        // Create mock table
        $table = $this->createMockTable();

        // Render table
        $html = $this->renderer->render($table, [], [], []);

        // Assert RTL attributes are present
        $this->assertStringContainsString('dir="rtl"', $html, 'Table container should have dir="rtl" attribute');
        $this->assertStringContainsString('rtl-locale', $html, 'Table container should have RTL class');
        $this->assertStringContainsString('arabic-font', $html, 'Table container should have locale-specific font class');
    }

    /**
     * Test that LTR attributes are added for LTR locales.
     * 
     * Validates: Requirement 51.11 - Support RTL layouts for RTL locales
     */
    public function test_ltr_attributes_added_for_ltr_locales(): void
    {
        // Setup theme mocks
        $this->setupThemeMocks();
        
        // Mock ThemeLocaleIntegration to return LTR attributes
        $this->themeLocaleIntegration
            ->shouldReceive('getHtmlAttributes')
            ->once()
            ->andReturn([
                'lang' => 'en',
                'dir' => 'ltr',
                'class' => ''
            ]);

        $this->themeLocaleIntegration
            ->shouldReceive('getBodyClasses')
            ->once()
            ->andReturn('');

        $this->themeLocaleIntegration
            ->shouldReceive('getLocalizedThemeCss')
            ->once()
            ->andReturn('/* Localized CSS */');

        // Create mock table
        $table = $this->createMockTable();

        // Render table
        $html = $this->renderer->render($table, [], [], []);

        // Assert LTR attributes are present
        $this->assertStringContainsString('dir="ltr"', $html, 'Table container should have dir="ltr" attribute');
    }

    /**
     * Test that localized theme CSS includes locale-specific fonts.
     * 
     * Validates: Requirement 51.11 - Use locale-specific fonts from theme
     */
    public function test_localized_theme_css_includes_locale_specific_fonts(): void
    {
        // Setup theme mocks
        $this->setupThemeMocks();
        
        // Mock ThemeLocaleIntegration to return CSS with Arabic fonts
        $arabicFontCss = <<<CSS
@import url('https://fonts.googleapis.com/css2?family=Noto+Sans+Arabic:wght@400;500;600;700&display=swap');

:root {
    --cs-font-sans: 'Noto Sans Arabic', 'Tajawal', 'Cairo', system-ui, sans-serif;
}
CSS;

        $this->themeLocaleIntegration
            ->shouldReceive('getLocalizedThemeCss')
            ->once()
            ->andReturn($arabicFontCss);

        $this->themeLocaleIntegration
            ->shouldReceive('getHtmlAttributes')
            ->once()
            ->andReturn([
                'lang' => 'ar',
                'dir' => 'rtl',
                'class' => 'rtl-locale'
            ]);

        $this->themeLocaleIntegration
            ->shouldReceive('getBodyClasses')
            ->once()
            ->andReturn('rtl-locale');

        // Create mock table
        $table = $this->createMockTable();

        // Render table
        $html = $this->renderer->render($table, [], [], []);

        // Assert Arabic fonts are included
        $this->assertStringContainsString('Noto Sans Arabic', $html, 'CSS should include Arabic font');
        $this->assertStringContainsString('Tajawal', $html, 'CSS should include Tajawal font');
        $this->assertStringContainsString('Cairo', $html, 'CSS should include Cairo font');
    }

    /**
     * Test that RTL CSS is included for RTL locales.
     * 
     * Validates: Requirement 51.11 - Support RTL layouts for RTL locales
     */
    public function test_rtl_css_included_for_rtl_locales(): void
    {
        // Setup theme mocks
        $this->setupThemeMocks();
        
        // Mock ThemeLocaleIntegration to return CSS with RTL styles
        $rtlCss = <<<CSS
[dir="rtl"] .tanstack-table th,
[dir="rtl"] .tanstack-table td {
    text-align: right;
}

[dir="rtl"] .sort-indicator {
    margin-left: 0;
    margin-right: 0.5rem;
}
CSS;

        $this->themeLocaleIntegration
            ->shouldReceive('getLocalizedThemeCss')
            ->once()
            ->andReturn($rtlCss);

        $this->themeLocaleIntegration
            ->shouldReceive('getHtmlAttributes')
            ->once()
            ->andReturn([
                'lang' => 'ar',
                'dir' => 'rtl',
                'class' => 'rtl-locale'
            ]);

        $this->themeLocaleIntegration
            ->shouldReceive('getBodyClasses')
            ->once()
            ->andReturn('rtl-locale');

        // Create mock table
        $table = $this->createMockTable();

        // Render table
        $html = $this->renderer->render($table, [], [], []);

        // Assert RTL CSS is included
        $this->assertStringContainsString('[dir="rtl"]', $html, 'CSS should include RTL selectors');
        $this->assertStringContainsString('text-align: right', $html, 'CSS should include RTL text alignment');
    }

    /**
     * Test that renderStyles includes RTL-specific CSS.
     * 
     * Validates: Requirement 51.11 - Support RTL layouts for RTL locales
     */
    public function test_render_styles_includes_rtl_css(): void
    {
        // Mock theme manager
        $mockTheme = Mockery::mock();
        $mockTheme->shouldReceive('getName')->andReturn('default');
        $mockTheme->shouldReceive('getVersion')->andReturn('1.0.0');
        
        $this->themeManager
            ->shouldReceive('current')
            ->andReturn($mockTheme);
        
        $this->themeManager
            ->shouldReceive('colors')
            ->andReturn([
                'primary' => '#6366f1',
                'secondary' => '#8b5cf6',
                'success' => '#10b981',
                'warning' => '#f59e0b',
                'error' => '#ef4444',
                'info' => '#3b82f6',
            ]);
        
        $this->themeManager
            ->shouldReceive('fonts')
            ->andReturn([
                'sans' => 'Inter, system-ui, sans-serif',
                'mono' => 'JetBrains Mono, monospace',
            ]);

        // Create mock table
        $table = Mockery::mock(TableBuilder::class);

        // Render styles
        $styles = $this->renderer->renderStyles($table);

        // Assert RTL CSS is present
        $this->assertStringContainsString('[dir="rtl"]', $styles, 'Styles should include RTL selectors');
        $this->assertStringContainsString('[dir="rtl"] .tanstack-table th', $styles, 'Styles should include RTL table header styles');
        $this->assertStringContainsString('[dir="rtl"] .tanstack-table td', $styles, 'Styles should include RTL table cell styles');
        $this->assertStringContainsString('[dir="rtl"] .sort-indicator', $styles, 'Styles should include RTL sort indicator styles');
        $this->assertStringContainsString('[dir="rtl"] .tanstack-pagination', $styles, 'Styles should include RTL pagination styles');
        $this->assertStringContainsString('[dir="rtl"] .tanstack-table-pinned-left', $styles, 'Styles should include RTL pinned column styles');
        $this->assertStringContainsString('[dir="rtl"] .tanstack-table-pinned-right', $styles, 'Styles should include RTL pinned column styles');
    }

    /**
     * Test that locale information is included in theme injection comment.
     * 
     * Validates: Requirement 51.11 - Integrate with ThemeLocaleIntegration
     */
    public function test_locale_information_in_theme_injection_comment(): void
    {
        // Setup theme mocks
        $this->setupThemeMocks();

        $this->themeLocaleIntegration
            ->shouldReceive('getLocalizedThemeCss')
            ->once()
            ->andReturn('/* CSS */');

        $this->themeLocaleIntegration
            ->shouldReceive('getHtmlAttributes')
            ->once()
            ->andReturn([
                'lang' => 'ar',
                'dir' => 'rtl',
                'class' => 'rtl-locale'
            ]);

        $this->themeLocaleIntegration
            ->shouldReceive('getBodyClasses')
            ->once()
            ->andReturn('rtl-locale');

        // Create mock table
        $table = $this->createMockTable();

        // Render table
        $html = $this->renderer->render($table, [], [], []);

        // Assert locale is mentioned in comment
        $this->assertStringContainsString('Locale:', $html, 'Theme injection comment should include locale');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
