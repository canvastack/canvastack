<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Support\Theme;

use Canvastack\Canvastack\Support\Theme\CssInjector;
use Canvastack\Canvastack\Support\Theme\CssVariableGenerator;
use Canvastack\Canvastack\Support\Theme\Theme;
use PHPUnit\Framework\TestCase;

class CssInjectorTest extends TestCase
{
    protected CssInjector $injector;

    protected Theme $theme;

    protected function setUp(): void
    {
        parent::setUp();
        $this->injector = new CssInjector();
        $this->theme = new Theme(
            'test',
            'Test Theme',
            '1.0.0',
            'Test',
            'Test theme',
            [
                'colors' => [
                    'primary' => '#6366f1',
                    'secondary' => '#8b5cf6',
                ],
                'fonts' => [
                    'sans' => 'Inter, sans-serif',
                ],
            ]
        );
    }

    public function test_injects_css_variables(): void
    {
        $css = $this->injector->inject($this->theme);

        $this->assertStringContainsString('<style id="canvastack-theme-variables">', $css);
        $this->assertStringContainsString(':root', $css);
        $this->assertStringContainsString('--cs-color-primary', $css);
        $this->assertStringContainsString('#6366f1', $css);
        $this->assertStringContainsString('</style>', $css);
    }

    public function test_generates_style_tag(): void
    {
        $variables = [
            '--cs-color-primary' => '#6366f1',
            '--cs-color-secondary' => '#8b5cf6',
        ];

        $styleTag = $this->injector->generateStyleTag($variables);

        $this->assertStringStartsWith('<style', $styleTag);
        $this->assertStringEndsWith('</style>', $styleTag);
        $this->assertStringContainsString('--cs-color-primary', $styleTag);
    }

    public function test_generates_css_from_variables(): void
    {
        $variables = [
            '--cs-color-primary' => '#6366f1',
            '--cs-font-sans' => 'Inter, sans-serif',
        ];

        $css = $this->injector->generateCss($variables);

        $this->assertStringContainsString(':root {', $css);
        $this->assertStringContainsString('--cs-color-primary: #6366f1;', $css);
        $this->assertStringContainsString('--cs-font-sans: Inter, sans-serif;', $css);
        $this->assertStringContainsString('}', $css);
    }

    public function test_generates_minified_css(): void
    {
        $this->injector->setMinify(true);

        $variables = [
            '--cs-color-primary' => '#6366f1',
        ];

        $css = $this->injector->generateCss($variables);

        $this->assertStringContainsString(':root{', $css);
        $this->assertStringNotContainsString("\n", $css);
        $this->assertStringNotContainsString('  ', $css);
    }

    public function test_generates_css_with_dark_mode(): void
    {
        $lightVariables = [
            '--cs-color-bg' => '#ffffff',
        ];

        $darkVariables = [
            '--cs-color-bg' => '#000000',
        ];

        $css = $this->injector->generateCssWithDarkMode($lightVariables, $darkVariables);

        $this->assertStringContainsString(':root {', $css);
        $this->assertStringContainsString('.dark {', $css);
        $this->assertStringContainsString('--cs-color-bg: #ffffff;', $css);
        $this->assertStringContainsString('--cs-color-bg: #000000;', $css);
    }

    public function test_injects_theme_switcher_javascript(): void
    {
        $themes = [
            'light' => [
                'name' => 'light',
                'displayName' => 'Light',
                'variables' => ['--cs-color-primary' => '#6366f1'],
            ],
            'dark' => [
                'name' => 'dark',
                'displayName' => 'Dark',
                'variables' => ['--cs-color-primary' => '#312e81'],
            ],
        ];

        $js = $this->injector->injectThemeSwitcher($themes);

        $this->assertStringContainsString('<script id="canvastack-theme-switcher">', $js);
        $this->assertStringContainsString('window.CanvastackTheme', $js);
        $this->assertStringContainsString('switch:', $js);
        $this->assertStringContainsString('updateVariables:', $js);
        $this->assertStringContainsString('localStorage', $js);
        $this->assertStringContainsString('</script>', $js);
    }

    public function test_generates_theme_data(): void
    {
        $theme1 = new Theme(
            'light',
            'Light Theme',
            '1.0.0',
            'Test',
            'Light theme',
            ['colors' => ['primary' => '#6366f1']]
        );

        $theme2 = new Theme(
            'dark',
            'Dark Theme',
            '1.0.0',
            'Test',
            'Dark theme',
            ['colors' => ['primary' => '#312e81']]
        );

        $themes = ['light' => $theme1, 'dark' => $theme2];
        $data = $this->injector->generateThemeData($themes);

        $this->assertArrayHasKey('light', $data);
        $this->assertArrayHasKey('dark', $data);
        $this->assertEquals('light', $data['light']['name']);
        $this->assertEquals('Light Theme', $data['light']['displayName']);
        $this->assertArrayHasKey('variables', $data['light']);
    }

    public function test_injects_complete_theme_system(): void
    {
        $theme1 = new Theme(
            'light',
            'Light',
            '1.0.0',
            'Test',
            'Light theme',
            ['colors' => ['primary' => '#6366f1']]
        );

        $theme2 = new Theme(
            'dark',
            'Dark',
            '1.0.0',
            'Test',
            'Dark theme',
            ['colors' => ['primary' => '#312e81']]
        );

        $output = $this->injector->injectComplete($theme1, ['light' => $theme1, 'dark' => $theme2]);

        $this->assertStringContainsString('<style id="canvastack-theme-variables">', $output);
        $this->assertStringContainsString('<script id="canvastack-theme-switcher">', $output);
        $this->assertStringContainsString('--cs-color-primary', $output);
        $this->assertStringContainsString('window.CanvastackTheme', $output);
    }

    public function test_handles_empty_variables(): void
    {
        $css = $this->injector->generateCss([]);

        $this->assertEmpty($css);
    }

    public function test_can_set_custom_generator(): void
    {
        $customGenerator = new CssVariableGenerator('custom');
        $this->injector->setGenerator($customGenerator);

        $css = $this->injector->inject($this->theme);

        $this->assertStringContainsString('--custom-color-primary', $css);
    }

    public function test_minify_flag_works(): void
    {
        $this->assertFalse($this->injector->isMinified());

        $this->injector->setMinify(true);

        $this->assertTrue($this->injector->isMinified());
    }

    public function test_inline_injection_returns_same_as_inject(): void
    {
        $inline = $this->injector->injectInline($this->theme);
        $regular = $this->injector->inject($this->theme);

        $this->assertEquals($regular, $inline);
    }
}
