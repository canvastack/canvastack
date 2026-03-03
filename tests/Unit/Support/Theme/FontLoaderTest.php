<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Support\Theme;

use Canvastack\Canvastack\Support\Theme\FontLoader;
use Canvastack\Canvastack\Support\Theme\Theme;
use PHPUnit\Framework\TestCase;

class FontLoaderTest extends TestCase
{
    protected FontLoader $loader;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loader = new FontLoader();
    }

    public function test_generates_google_font_import(): void
    {
        $import = $this->loader->generateGoogleFontImport('Inter');

        $this->assertStringContainsString('<link', $import);
        $this->assertStringContainsString('fonts.googleapis.com', $import);
        $this->assertStringContainsString('Inter', $import);
        $this->assertStringContainsString('preconnect', $import);
    }

    public function test_generates_google_font_import_with_weights(): void
    {
        $import = $this->loader->generateGoogleFontImport('Inter', [400, 600, 700]);

        $this->assertStringContainsString('wght@400;600;700', $import);
    }

    public function test_skips_system_fonts(): void
    {
        $import = $this->loader->generateGoogleFontImport('system-ui');

        $this->assertNull($import);
    }

    public function test_extracts_font_name_from_family_string(): void
    {
        $import = $this->loader->generateGoogleFontImport('Inter, system-ui, sans-serif');

        $this->assertStringContainsString('Inter', $import);
        $this->assertStringNotContainsString('system-ui', $import);
    }

    public function test_generates_font_import_from_config(): void
    {
        $config = [
            'provider' => 'google',
            'family' => 'Roboto',
            'weights' => [400, 700],
        ];

        $import = $this->loader->generateFontImport($config);

        $this->assertNotNull($import);
        $this->assertStringContainsString('Roboto', $import);
        $this->assertStringContainsString('wght@400;700', $import);
    }

    public function test_generates_adobe_font_import(): void
    {
        $config = [
            'provider' => 'adobe',
            'kit_id' => 'abc123',
        ];

        $import = $this->loader->generateFontImport($config);

        $this->assertNotNull($import);
        $this->assertStringContainsString('use.typekit.net', $import);
        $this->assertStringContainsString('abc123', $import);
    }

    public function test_generates_custom_font_import(): void
    {
        $config = [
            'provider' => 'custom',
            'url' => 'https://example.com/fonts.css',
        ];

        $import = $this->loader->generateFontImport($config);

        $this->assertNotNull($import);
        $this->assertStringContainsString('example.com/fonts.css', $import);
    }

    public function test_generates_font_face_css(): void
    {
        $config = [
            'family' => 'Custom Font',
            'src' => [
                'woff2' => '/fonts/custom.woff2',
                'woff' => '/fonts/custom.woff',
            ],
            'weight' => 400,
            'style' => 'normal',
        ];

        $css = $this->loader->generateFontFace($config);

        $this->assertStringContainsString('@font-face', $css);
        $this->assertStringContainsString('Custom Font', $css);
        $this->assertStringContainsString('/fonts/custom.woff2', $css);
        $this->assertStringContainsString('format(\'woff2\')', $css);
        $this->assertStringContainsString('font-weight: 400', $css);
    }

    public function test_generates_preload_links(): void
    {
        $fonts = [
            [
                'preload' => true,
                'url' => '/fonts/custom.woff2',
                'format' => 'woff2',
            ],
            [
                'preload' => false,
                'url' => '/fonts/other.woff2',
            ],
        ];

        $preloads = $this->loader->generatePreloads($fonts);

        $this->assertStringContainsString('<link rel="preload"', $preloads);
        $this->assertStringContainsString('/fonts/custom.woff2', $preloads);
        $this->assertStringNotContainsString('/fonts/other.woff2', $preloads);
    }

    public function test_generates_imports_from_theme(): void
    {
        $theme = new Theme(
            'test',
            'Test',
            '1.0.0',
            'Test',
            'Test theme',
            [
                'fonts' => [
                    'sans' => 'Inter, sans-serif',
                    'mono' => 'JetBrains Mono, monospace',
                ],
            ]
        );

        $imports = $this->loader->generateImports($theme);

        $this->assertStringContainsString('Inter', $imports);
        $this->assertStringContainsString('JetBrains+Mono', $imports);
    }

    public function test_generates_imports_from_detailed_config(): void
    {
        $theme = new Theme(
            'test',
            'Test',
            '1.0.0',
            'Test',
            'Test theme',
            [
                'fonts' => [
                    'sans' => [
                        'family' => 'Inter',
                        'provider' => 'google',
                        'weights' => [400, 600],
                    ],
                ],
            ]
        );

        $imports = $this->loader->generateImports($theme);

        $this->assertStringContainsString('Inter', $imports);
        $this->assertStringContainsString('wght@400;600', $imports);
    }

    public function test_generates_complete_font_loading_html(): void
    {
        $theme = new Theme(
            'test',
            'Test',
            '1.0.0',
            'Test',
            'Test theme',
            [
                'fonts' => [
                    'sans' => 'Inter, sans-serif',
                    'custom' => [
                        'family' => 'Custom Font',
                        'local' => true,
                        'src' => [
                            'woff2' => '/fonts/custom.woff2',
                        ],
                    ],
                ],
            ]
        );

        $html = $this->loader->generateComplete($theme);

        $this->assertStringContainsString('Inter', $html);
        $this->assertStringContainsString('@font-face', $html);
        $this->assertStringContainsString('Custom Font', $html);
    }

    public function test_gets_font_family_value_from_string(): void
    {
        $value = $this->loader->getFontFamilyValue('Inter, sans-serif');

        $this->assertEquals('Inter, sans-serif', $value);
    }

    public function test_gets_font_family_value_from_array(): void
    {
        $font = [
            'family' => 'Inter, sans-serif',
            'provider' => 'google',
        ];

        $value = $this->loader->getFontFamilyValue($font);

        $this->assertEquals('Inter, sans-serif', $value);
    }

    public function test_returns_default_font_family_for_invalid_input(): void
    {
        $value = $this->loader->getFontFamilyValue(null);

        $this->assertEquals('system-ui, -apple-system, sans-serif', $value);
    }

    public function test_can_add_custom_provider(): void
    {
        $this->loader->addProvider('custom', 'https://custom.com/fonts');

        $providers = $this->loader->getProviders();

        $this->assertArrayHasKey('custom', $providers);
        $this->assertEquals('https://custom.com/fonts', $providers['custom']);
    }

    public function test_handles_empty_fonts_configuration(): void
    {
        $theme = new Theme(
            'test',
            'Test',
            '1.0.0',
            'Test',
            'Test theme'
        );

        $imports = $this->loader->generateImports($theme);

        $this->assertEmpty($imports);
    }

    public function test_handles_font_with_quotes(): void
    {
        $import = $this->loader->generateGoogleFontImport('"Inter", sans-serif');

        $this->assertStringContainsString('Inter', $import);
    }

    public function test_returns_null_for_adobe_font_without_kit_id(): void
    {
        $config = [
            'provider' => 'adobe',
        ];

        $import = $this->loader->generateFontImport($config);

        $this->assertNull($import);
    }

    public function test_returns_null_for_custom_font_without_url(): void
    {
        $config = [
            'provider' => 'custom',
        ];

        $import = $this->loader->generateFontImport($config);

        $this->assertNull($import);
    }

    public function test_returns_empty_string_for_font_face_without_src(): void
    {
        $config = [
            'family' => 'Custom Font',
        ];

        $css = $this->loader->generateFontFace($config);

        $this->assertEmpty($css);
    }

    public function test_builds_google_fonts_url_correctly(): void
    {
        $import = $this->loader->generateGoogleFontImport('Inter', [300, 400, 500]);

        $this->assertStringContainsString('family=Inter', $import);
        $this->assertStringContainsString('wght@300;400;500', $import);
        $this->assertStringContainsString('display=swap', $import);
    }

    public function test_handles_multiple_word_font_names(): void
    {
        $import = $this->loader->generateGoogleFontImport('Roboto Mono');

        $this->assertStringContainsString('Roboto+Mono', $import);
    }
}
