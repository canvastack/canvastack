<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Support\Theme;

use Canvastack\Canvastack\Support\Theme\Theme;
use PHPUnit\Framework\TestCase;

class ThemeVariantTest extends TestCase
{
    public function test_default_variant_is_light(): void
    {
        $theme = new Theme(
            name: 'test',
            displayName: 'Test',
            version: '1.0.0',
            author: 'Test',
            description: 'Test',
            config: []
        );

        $this->assertEquals('light', $theme->getVariant());
    }

    public function test_can_set_variant(): void
    {
        $theme = new Theme(
            name: 'test',
            displayName: 'Test',
            version: '1.0.0',
            author: 'Test',
            description: 'Test',
            config: []
        );

        $theme->setVariant('dark');

        $this->assertEquals('dark', $theme->getVariant());
    }

    public function test_invalid_variant_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $theme = new Theme(
            name: 'test',
            displayName: 'Test',
            version: '1.0.0',
            author: 'Test',
            description: 'Test',
            config: []
        );

        $theme->setVariant('invalid');
    }

    public function test_has_variant(): void
    {
        $theme = new Theme(
            name: 'test',
            displayName: 'Test',
            version: '1.0.0',
            author: 'Test',
            description: 'Test',
            config: []
        );

        $this->assertTrue($theme->hasVariant('light'));
        $this->assertTrue($theme->hasVariant('dark'));
        $this->assertFalse($theme->hasVariant('invalid'));
    }

    public function test_variant_specific_colors(): void
    {
        $theme = new Theme(
            name: 'test',
            displayName: 'Test',
            version: '1.0.0',
            author: 'Test',
            description: 'Test',
            config: [
                'colors' => [
                    'primary' => '#000000',
                ],
                'light' => [
                    'colors' => [
                        'background' => '#ffffff',
                    ],
                ],
                'dark' => [
                    'colors' => [
                        'background' => '#000000',
                    ],
                ],
            ]
        );

        // Light variant
        $theme->setVariant('light');
        $colors = $theme->getVariantColors();
        $this->assertEquals('#ffffff', $colors['background']);
        $this->assertEquals('#000000', $colors['primary']);

        // Dark variant
        $theme->setVariant('dark');
        $colors = $theme->getVariantColors();
        $this->assertEquals('#000000', $colors['background']);
        $this->assertEquals('#000000', $colors['primary']);
    }

    public function test_variant_css_variables(): void
    {
        $theme = new Theme(
            name: 'test',
            displayName: 'Test',
            version: '1.0.0',
            author: 'Test',
            description: 'Test',
            config: [
                'colors' => [
                    'primary' => '#000000',
                ],
                'light' => [
                    'colors' => [
                        'background' => '#ffffff',
                    ],
                ],
                'dark' => [
                    'colors' => [
                        'background' => '#111111',
                    ],
                ],
                'fonts' => [
                    'sans' => 'Arial',
                ],
            ]
        );

        // Light variant
        $theme->setVariant('light');
        $vars = $theme->getVariantCssVariables();
        $this->assertEquals('#ffffff', $vars['--color-background']);
        $this->assertEquals('#000000', $vars['--color-primary']);
        $this->assertEquals('Arial', $vars['--font-sans']);

        // Dark variant
        $theme->setVariant('dark');
        $vars = $theme->getVariantCssVariables();
        $this->assertEquals('#111111', $vars['--color-background']);
    }

    public function test_get_available_variants(): void
    {
        $theme = new Theme(
            name: 'test',
            displayName: 'Test',
            version: '1.0.0',
            author: 'Test',
            description: 'Test',
            config: []
        );

        $variants = $theme->getAvailableVariants();

        $this->assertIsArray($variants);
        $this->assertContains('light', $variants);
        $this->assertContains('dark', $variants);
    }
}
