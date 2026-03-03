<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Support\Theme;

use Canvastack\Canvastack\Support\Theme\CssVariableGenerator;
use Canvastack\Canvastack\Support\Theme\Theme;
use PHPUnit\Framework\TestCase;

class CssVariableGeneratorTest extends TestCase
{
    protected CssVariableGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->generator = new CssVariableGenerator();
    }

    public function test_generates_color_variables(): void
    {
        $colors = [
            'primary' => '#6366f1',
            'secondary' => '#8b5cf6',
        ];

        $variables = $this->generator->generateColorVariables($colors);

        $this->assertArrayHasKey('--cs-color-primary', $variables);
        $this->assertArrayHasKey('--cs-color-secondary', $variables);
        $this->assertEquals('#6366f1', $variables['--cs-color-primary']);
        $this->assertEquals('#8b5cf6', $variables['--cs-color-secondary']);
    }

    public function test_generates_color_shades(): void
    {
        $colors = [
            'primary' => [
                '50' => '#eef2ff',
                '500' => '#6366f1',
                '900' => '#312e81',
            ],
        ];

        $variables = $this->generator->generateColorVariables($colors);

        $this->assertArrayHasKey('--cs-color-primary-50', $variables);
        $this->assertArrayHasKey('--cs-color-primary-500', $variables);
        $this->assertArrayHasKey('--cs-color-primary-900', $variables);
        $this->assertEquals('#eef2ff', $variables['--cs-color-primary-50']);
        $this->assertEquals('#6366f1', $variables['--cs-color-primary-500']);
    }

    public function test_generates_font_variables(): void
    {
        $fonts = [
            'sans' => 'Inter, system-ui, sans-serif',
            'mono' => 'JetBrains Mono, monospace',
        ];

        $variables = $this->generator->generateFontVariables($fonts);

        $this->assertArrayHasKey('--cs-font-sans', $variables);
        $this->assertArrayHasKey('--cs-font-mono', $variables);
        $this->assertEquals('Inter, system-ui, sans-serif', $variables['--cs-font-sans']);
    }

    public function test_generates_layout_variables(): void
    {
        $layout = [
            'sidebar_width' => '16rem',
            'navbar_height' => '4rem',
        ];

        $variables = $this->generator->generateLayoutVariables($layout);

        $this->assertArrayHasKey('--cs-layout-sidebar-width', $variables);
        $this->assertArrayHasKey('--cs-layout-navbar-height', $variables);
        $this->assertEquals('16rem', $variables['--cs-layout-sidebar-width']);
    }

    public function test_generates_nested_layout_variables(): void
    {
        $layout = [
            'border_radius' => [
                'sm' => '0.375rem',
                'md' => '0.5rem',
                'lg' => '0.75rem',
            ],
        ];

        $variables = $this->generator->generateLayoutVariables($layout);

        $this->assertArrayHasKey('--cs-layout-border-radius-sm', $variables);
        $this->assertArrayHasKey('--cs-layout-border-radius-md', $variables);
        $this->assertArrayHasKey('--cs-layout-border-radius-lg', $variables);
        $this->assertEquals('0.375rem', $variables['--cs-layout-border-radius-sm']);
    }

    public function test_generates_component_variables(): void
    {
        $components = [
            'button' => [
                'border_radius' => 'xl',
                'padding' => [
                    'sm' => '0.75rem 1rem',
                    'md' => '1rem 1.5rem',
                ],
            ],
        ];

        $variables = $this->generator->generateComponentVariables($components);

        $this->assertArrayHasKey('--cs-component-button-border-radius', $variables);
        $this->assertArrayHasKey('--cs-component-button-padding-sm', $variables);
        $this->assertArrayHasKey('--cs-component-button-padding-md', $variables);
        $this->assertEquals('xl', $variables['--cs-component-button-border-radius']);
    }

    public function test_generates_gradient_variables(): void
    {
        $gradients = [
            'primary' => 'linear-gradient(135deg, #6366f1, #8b5cf6)',
            'subtle' => 'linear-gradient(135deg, #eef2ff, #f5f3ff)',
        ];

        $variables = $this->generator->generateGradientVariables($gradients);

        $this->assertArrayHasKey('--cs-gradient-primary', $variables);
        $this->assertArrayHasKey('--cs-gradient-subtle', $variables);
        $this->assertStringContainsString('linear-gradient', $variables['--cs-gradient-primary']);
    }

    public function test_generates_all_variables_from_theme(): void
    {
        $theme = new Theme(
            'test',
            'Test Theme',
            '1.0.0',
            'Test',
            'Test theme',
            [
                'colors' => [
                    'primary' => '#6366f1',
                ],
                'fonts' => [
                    'sans' => 'Inter, sans-serif',
                ],
                'layout' => [
                    'sidebar_width' => '16rem',
                ],
                'components' => [
                    'button' => [
                        'border_radius' => 'xl',
                    ],
                ],
                'gradient' => [
                    'primary' => 'linear-gradient(135deg, #6366f1, #8b5cf6)',
                ],
            ]
        );

        $variables = $this->generator->generate($theme);

        $this->assertArrayHasKey('--cs-color-primary', $variables);
        $this->assertArrayHasKey('--cs-font-sans', $variables);
        $this->assertArrayHasKey('--cs-layout-sidebar-width', $variables);
        $this->assertArrayHasKey('--cs-component-button-border-radius', $variables);
        $this->assertArrayHasKey('--cs-gradient-primary', $variables);
    }

    public function test_converts_underscores_to_hyphens(): void
    {
        $layout = [
            'sidebar_width' => '16rem',
            'navbar_height' => '4rem',
        ];

        $variables = $this->generator->generateLayoutVariables($layout);

        $this->assertArrayHasKey('--cs-layout-sidebar-width', $variables);
        $this->assertArrayHasKey('--cs-layout-navbar-height', $variables);
    }

    public function test_can_set_custom_prefix(): void
    {
        $this->generator->setPrefix('custom');

        $colors = ['primary' => '#6366f1'];
        $variables = $this->generator->generateColorVariables($colors);

        $this->assertArrayHasKey('--custom-color-primary', $variables);
    }

    public function test_generates_var_reference(): void
    {
        $varRef = $this->generator->var('color', 'primary');

        $this->assertEquals('var(--cs-color-primary)', $varRef);
    }

    public function test_generates_var_reference_with_fallback(): void
    {
        $varRef = $this->generator->varWithFallback('#6366f1', 'color', 'primary');

        $this->assertEquals('var(--cs-color-primary, #6366f1)', $varRef);
    }

    public function test_handles_deeply_nested_variables(): void
    {
        $components = [
            'button' => [
                'padding' => [
                    'sm' => [
                        'x' => '0.75rem',
                        'y' => '0.5rem',
                    ],
                ],
            ],
        ];

        $variables = $this->generator->generateComponentVariables($components);

        $this->assertArrayHasKey('--cs-component-button-padding-sm-x', $variables);
        $this->assertArrayHasKey('--cs-component-button-padding-sm-y', $variables);
    }

    public function test_ignores_non_scalar_values_at_leaf_level(): void
    {
        $layout = [
            'invalid' => ['nested' => ['too' => ['deep' => 'value']]],
        ];

        $variables = $this->generator->generateLayoutVariables($layout);

        // Should handle nested arrays recursively
        $this->assertArrayHasKey('--cs-layout-invalid-nested-too-deep', $variables);
    }

    public function test_handles_empty_configuration(): void
    {
        $theme = new Theme(
            'empty',
            'Empty Theme',
            '1.0.0',
            'Test',
            'Empty theme'
        );

        $variables = $this->generator->generate($theme);

        $this->assertIsArray($variables);
        $this->assertEmpty($variables);
    }
}
