<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Support\Theme;

use Canvastack\Canvastack\Support\Theme\Theme;
use Canvastack\Canvastack\Support\Theme\ThemeThumbnailGenerator;
use PHPUnit\Framework\TestCase;

/**
 * Theme Thumbnail Generator Test.
 *
 * @covers \Canvastack\Canvastack\Support\Theme\ThemeThumbnailGenerator
 */
class ThemeThumbnailGeneratorTest extends TestCase
{
    protected ThemeThumbnailGenerator $generator;

    protected Theme $theme;

    protected function setUp(): void
    {
        parent::setUp();

        $this->generator = new ThemeThumbnailGenerator();

        $this->theme = new Theme(
            name: 'test',
            displayName: 'Test Theme',
            version: '1.0.0',
            author: 'Test Author',
            description: 'Test theme description',
            config: [
                'colors' => [
                    'primary' => [
                        '500' => '#6366f1',
                    ],
                    'secondary' => [
                        '500' => '#8b5cf6',
                    ],
                    'accent' => [
                        '500' => '#a855f7',
                    ],
                ],
                'gradient' => [
                    'primary' => 'linear-gradient(135deg, #6366f1, #8b5cf6, #a855f7)',
                ],
            ]
        );
    }

    public function test_generates_gradient_thumbnail(): void
    {
        $svg = $this->generator->generate($this->theme, 'gradient');

        $this->assertStringContainsString('<svg', $svg);
        $this->assertStringContainsString('Test Theme', $svg);
        $this->assertStringContainsString('linearGradient', $svg);
    }

    public function test_generates_palette_thumbnail(): void
    {
        $svg = $this->generator->generate($this->theme, 'palette');

        $this->assertStringContainsString('<svg', $svg);
        $this->assertStringContainsString('#6366f1', $svg);
        $this->assertStringContainsString('#8b5cf6', $svg);
        $this->assertStringContainsString('#a855f7', $svg);
    }

    public function test_generates_split_thumbnail(): void
    {
        $svg = $this->generator->generate($this->theme, 'split');

        $this->assertStringContainsString('<svg', $svg);
        $this->assertStringContainsString('Test Theme', $svg);
        $this->assertStringContainsString('linearGradient', $svg);
    }

    public function test_generates_card_thumbnail(): void
    {
        $svg = $this->generator->generate($this->theme, 'card');

        $this->assertStringContainsString('<svg', $svg);
        $this->assertStringContainsString('Test Theme', $svg);
    }

    public function test_sets_custom_dimensions(): void
    {
        $this->generator->setDimensions(640, 360);
        $svg = $this->generator->generate($this->theme);

        $this->assertStringContainsString('width="640"', $svg);
        $this->assertStringContainsString('height="360"', $svg);
    }

    public function test_generates_data_uri(): void
    {
        $dataUri = $this->generator->generateDataUri($this->theme);

        $this->assertStringStartsWith('data:image/svg+xml;base64,', $dataUri);
    }

    public function test_generates_all_variants(): void
    {
        $thumbnails = $this->generator->generateAll($this->theme);

        $this->assertIsArray($thumbnails);
        $this->assertArrayHasKey('gradient', $thumbnails);
        $this->assertArrayHasKey('palette', $thumbnails);
        $this->assertArrayHasKey('split', $thumbnails);
        $this->assertArrayHasKey('card', $thumbnails);
    }

    public function test_saves_to_file(): void
    {
        $tempFile = sys_get_temp_dir() . '/test-theme-thumbnail.svg';

        $result = $this->generator->saveToFile($this->theme, $tempFile);

        $this->assertTrue($result);
        $this->assertFileExists($tempFile);

        $content = file_get_contents($tempFile);
        $this->assertStringContainsString('<svg', $content);

        // Cleanup
        unlink($tempFile);
    }

    public function test_handles_simple_color_values(): void
    {
        $theme = new Theme(
            name: 'simple',
            displayName: 'Simple Theme',
            version: '1.0.0',
            author: 'Test',
            description: 'Simple colors',
            config: [
                'colors' => [
                    'primary' => '#ff0000',
                    'secondary' => '#00ff00',
                    'accent' => '#0000ff',
                ],
            ]
        );

        $svg = $this->generator->generate($theme, 'palette');

        $this->assertStringContainsString('#ff0000', $svg);
        $this->assertStringContainsString('#00ff00', $svg);
        $this->assertStringContainsString('#0000ff', $svg);
    }

    public function test_uses_fallback_colors_when_missing(): void
    {
        $theme = new Theme(
            name: 'minimal',
            displayName: 'Minimal Theme',
            version: '1.0.0',
            author: 'Test',
            description: 'Minimal config',
            config: [
                'colors' => [],
            ]
        );

        $svg = $this->generator->generate($theme);

        // Should use fallback color
        $this->assertStringContainsString('#6366f1', $svg);
    }
}
