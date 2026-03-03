<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Support\Theme;

use Canvastack\Canvastack\Support\Theme\Theme;
use Canvastack\Canvastack\Support\Theme\ThemeExporter;
use Illuminate\Filesystem\Filesystem;
use PHPUnit\Framework\TestCase;

class ThemeExporterTest extends TestCase
{
    protected ThemeExporter $exporter;

    protected Filesystem $files;

    protected string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->files = new Filesystem();
        $this->exporter = new ThemeExporter($this->files);
        $this->tempDir = sys_get_temp_dir() . '/canvastack-test-' . uniqid();

        $this->files->makeDirectory($this->tempDir, 0755, true);
    }

    protected function tearDown(): void
    {
        if ($this->files->exists($this->tempDir)) {
            $this->files->deleteDirectory($this->tempDir);
        }

        parent::tearDown();
    }

    public function test_export_to_json(): void
    {
        $theme = $this->createTestTheme();

        $json = $this->exporter->toJson($theme);

        $this->assertIsString($json);
        $this->assertJson($json);

        $data = json_decode($json, true);
        $this->assertEquals('test', $data['name']);
        $this->assertEquals('Test Theme', $data['display_name']);
    }

    public function test_export_to_php(): void
    {
        $theme = $this->createTestTheme();

        $php = $this->exporter->toPhp($theme);

        $this->assertIsString($php);
        $this->assertStringStartsWith('<?php', $php);
        $this->assertStringContainsString('return', $php);
    }

    public function test_export_to_file_json(): void
    {
        $theme = $this->createTestTheme();
        $path = $this->tempDir . '/theme.json';

        $result = $this->exporter->toFile($theme, $path, 'json');

        $this->assertTrue($result);
        $this->assertFileExists($path);

        $content = $this->files->get($path);
        $data = json_decode($content, true);
        $this->assertEquals('test', $data['name']);
    }

    public function test_export_to_file_php(): void
    {
        $theme = $this->createTestTheme();
        $path = $this->tempDir . '/theme.php';

        $result = $this->exporter->toFile($theme, $path, 'php');

        $this->assertTrue($result);
        $this->assertFileExists($path);

        $data = require $path;
        $this->assertIsArray($data);
        $this->assertEquals('test', $data['name']);
    }

    public function test_export_multiple_themes(): void
    {
        $themes = [
            $this->createTestTheme('theme1'),
            $this->createTestTheme('theme2'),
        ];

        $count = $this->exporter->exportMultiple($themes, $this->tempDir, 'json');

        $this->assertEquals(2, $count);
        $this->assertFileExists($this->tempDir . '/theme1.json');
        $this->assertFileExists($this->tempDir . '/theme2.json');
    }

    public function test_export_includes_metadata(): void
    {
        $theme = $this->createTestTheme();

        $json = $this->exporter->toJson($theme);
        $data = json_decode($json, true);

        $this->assertArrayHasKey('name', $data);
        $this->assertArrayHasKey('display_name', $data);
        $this->assertArrayHasKey('version', $data);
        $this->assertArrayHasKey('author', $data);
        $this->assertArrayHasKey('description', $data);
        $this->assertArrayHasKey('config', $data);
        $this->assertArrayHasKey('exported_at', $data);
    }

    protected function createTestTheme(string $name = 'test'): Theme
    {
        return new Theme(
            name: $name,
            displayName: 'Test Theme',
            version: '1.0.0',
            author: 'Test Author',
            description: 'Test description',
            config: [
                'colors' => [
                    'primary' => '#000000',
                ],
            ]
        );
    }
}
