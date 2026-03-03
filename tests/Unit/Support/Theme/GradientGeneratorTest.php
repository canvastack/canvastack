<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Support\Theme;

use Canvastack\Canvastack\Support\Theme\GradientGenerator;
use PHPUnit\Framework\TestCase;

class GradientGeneratorTest extends TestCase
{
    protected GradientGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->generator = new GradientGenerator();
    }

    public function test_generates_linear_gradient(): void
    {
        $colors = ['#6366f1', '#8b5cf6', '#a855f7'];
        $gradient = $this->generator->linear($colors);

        $this->assertStringContainsString('linear-gradient', $gradient);
        $this->assertStringContainsString('135deg', $gradient);
        $this->assertStringContainsString('#6366f1', $gradient);
        $this->assertStringContainsString('#8b5cf6', $gradient);
        $this->assertStringContainsString('#a855f7', $gradient);
    }

    public function test_generates_linear_gradient_with_custom_angle(): void
    {
        $colors = ['#6366f1', '#8b5cf6'];
        $gradient = $this->generator->linear($colors, 90);

        $this->assertStringContainsString('90deg', $gradient);
    }

    public function test_generates_radial_gradient(): void
    {
        $colors = ['#6366f1', '#8b5cf6'];
        $gradient = $this->generator->radial($colors);

        $this->assertStringContainsString('radial-gradient', $gradient);
        $this->assertStringContainsString('circle', $gradient);
        $this->assertStringContainsString('center', $gradient);
    }

    public function test_generates_radial_gradient_with_custom_shape(): void
    {
        $colors = ['#6366f1', '#8b5cf6'];
        $gradient = $this->generator->radial($colors, 'ellipse', 'top left');

        $this->assertStringContainsString('ellipse', $gradient);
        $this->assertStringContainsString('top left', $gradient);
    }

    public function test_generates_conic_gradient(): void
    {
        $colors = ['#6366f1', '#8b5cf6', '#a855f7'];
        $gradient = $this->generator->conic($colors);

        $this->assertStringContainsString('conic-gradient', $gradient);
        $this->assertStringContainsString('from 0deg', $gradient);
        $this->assertStringContainsString('center', $gradient);
    }

    public function test_generates_conic_gradient_with_custom_angle(): void
    {
        $colors = ['#6366f1', '#8b5cf6'];
        $gradient = $this->generator->conic($colors, 45);

        $this->assertStringContainsString('from 45deg', $gradient);
    }

    public function test_generates_two_color_gradient(): void
    {
        $gradient = $this->generator->twoColor('#6366f1', '#8b5cf6');

        $this->assertStringContainsString('linear-gradient', $gradient);
        $this->assertStringContainsString('#6366f1', $gradient);
        $this->assertStringContainsString('#8b5cf6', $gradient);
    }

    public function test_generates_three_color_gradient(): void
    {
        $gradient = $this->generator->threeColor('#6366f1', '#8b5cf6', '#a855f7');

        $this->assertStringContainsString('#6366f1', $gradient);
        $this->assertStringContainsString('#8b5cf6', $gradient);
        $this->assertStringContainsString('#a855f7', $gradient);
    }

    public function test_generates_gradient_with_stops(): void
    {
        $colorStops = [
            '#6366f1' => '0%',
            '#8b5cf6' => '50%',
            '#a855f7' => '100%',
        ];

        $gradient = $this->generator->withStops($colorStops);

        $this->assertStringContainsString('#6366f1 0%', $gradient);
        $this->assertStringContainsString('#8b5cf6 50%', $gradient);
        $this->assertStringContainsString('#a855f7 100%', $gradient);
    }

    public function test_generates_primary_gradient_from_theme_colors(): void
    {
        $themeColors = [
            'primary' => '#6366f1',
            'secondary' => '#8b5cf6',
            'accent' => '#a855f7',
        ];

        $gradient = $this->generator->primary($themeColors);

        $this->assertStringContainsString('#6366f1', $gradient);
        $this->assertStringContainsString('#8b5cf6', $gradient);
        $this->assertStringContainsString('#a855f7', $gradient);
    }

    public function test_generates_primary_gradient_with_color_shades(): void
    {
        $themeColors = [
            'primary' => [
                '50' => '#eef2ff',
                '500' => '#6366f1',
                '900' => '#312e81',
            ],
            'secondary' => [
                '500' => '#8b5cf6',
            ],
        ];

        $gradient = $this->generator->primary($themeColors);

        $this->assertStringContainsString('#6366f1', $gradient);
        $this->assertStringContainsString('#8b5cf6', $gradient);
    }

    public function test_generates_subtle_gradient_light_mode(): void
    {
        $themeColors = [
            'primary' => [
                '50' => '#eef2ff',
                '500' => '#6366f1',
            ],
            'secondary' => [
                '50' => '#f5f3ff',
                '500' => '#8b5cf6',
            ],
        ];

        $gradient = $this->generator->subtle($themeColors, false);

        $this->assertStringContainsString('#eef2ff', $gradient);
        $this->assertStringContainsString('#f5f3ff', $gradient);
    }

    public function test_generates_subtle_gradient_dark_mode(): void
    {
        $themeColors = [
            'primary' => [
                '500' => '#6366f1',
                '950' => '#1e1b4b',
            ],
            'secondary' => [
                '500' => '#8b5cf6',
                '900' => '#581c87',
            ],
        ];

        $gradient = $this->generator->subtle($themeColors, true);

        $this->assertStringContainsString('#1e1b4b', $gradient);
        $this->assertStringContainsString('#581c87', $gradient);
    }

    public function test_generates_mesh_gradient(): void
    {
        $colors = ['#6366f1', '#8b5cf6', '#a855f7'];
        $gradient = $this->generator->mesh($colors, 3);

        $this->assertStringContainsString('linear-gradient', $gradient);
        // Should contain multiple gradients separated by commas
        $this->assertGreaterThan(1, substr_count($gradient, 'linear-gradient'));
    }

    public function test_generates_gradient_from_theme(): void
    {
        $themeColors = [
            'primary' => '#6366f1',
            'secondary' => '#8b5cf6',
        ];

        $gradient = $this->generator->fromTheme($themeColors, 'linear', ['angle' => 90]);

        $this->assertStringContainsString('linear-gradient', $gradient);
        $this->assertStringContainsString('90deg', $gradient);
    }

    public function test_generates_radial_gradient_from_theme(): void
    {
        $themeColors = [
            'primary' => '#6366f1',
            'secondary' => '#8b5cf6',
        ];

        $gradient = $this->generator->fromTheme($themeColors, 'radial', [
            'shape' => 'ellipse',
            'position' => 'top',
        ]);

        $this->assertStringContainsString('radial-gradient', $gradient);
        $this->assertStringContainsString('ellipse', $gradient);
        $this->assertStringContainsString('top', $gradient);
    }

    public function test_generates_conic_gradient_from_theme(): void
    {
        $themeColors = [
            'primary' => '#6366f1',
            'secondary' => '#8b5cf6',
        ];

        $gradient = $this->generator->fromTheme($themeColors, 'conic', ['angle' => 45]);

        $this->assertStringContainsString('conic-gradient', $gradient);
        $this->assertStringContainsString('45deg', $gradient);
    }

    public function test_generates_var_reference(): void
    {
        $varRef = $this->generator->varReference('primary');

        $this->assertEquals('var(--cs-gradient-primary)', $varRef);
    }

    public function test_generates_gradient_with_css_variables(): void
    {
        $variableNames = ['primary', 'secondary', 'accent'];
        $gradient = $this->generator->withVariables($variableNames);

        $this->assertStringContainsString('var(--cs-color-primary)', $gradient);
        $this->assertStringContainsString('var(--cs-color-secondary)', $gradient);
        $this->assertStringContainsString('var(--cs-color-accent)', $gradient);
    }

    public function test_handles_empty_colors_array(): void
    {
        $gradient = $this->generator->linear([]);

        $this->assertStringContainsString('linear-gradient', $gradient);
    }

    public function test_extracts_colors_from_nested_theme_structure(): void
    {
        $themeColors = [
            'primary' => [
                '50' => '#eef2ff',
                '500' => '#6366f1',
            ],
            'secondary' => '#8b5cf6',
        ];

        $gradient = $this->generator->fromTheme($themeColors);

        // Should extract 500 shade from primary and use secondary directly
        $this->assertStringContainsString('#6366f1', $gradient);
        $this->assertStringContainsString('#8b5cf6', $gradient);
    }
}
