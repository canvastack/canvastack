<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Support\Theme;

use Canvastack\Canvastack\Support\Theme\Theme;
use Canvastack\Canvastack\Support\Theme\ThemeImporter;
use Canvastack\Canvastack\Support\Theme\ThemeLoader;
use Illuminate\Filesystem\Filesystem;
use PHPUnit\Framework\TestCase;

class ThemeImporterTest extends TestCase
{
    protected ThemeImporter $importer;

    protected Filesystem $files;

    protected string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->files = new Filesystem();
        $this->tempDir = sys_get_temp_dir() . '/canvastack-test-' . uniqid();
        $this->files->makeDirectory($this->tempDir, 0755, true);

        $loader = new ThemeLoader($this->tempDir, $this->files);
        $this->importer = new ThemeImporter($loader, $this->files);
    }

    protected function tearDown(): void
    {
        if ($this->files->exists($this->tempDir)) {
            $this->files->deleteDirectory($this->tempDir);
        }

        parent::tearDown();
    }

    public function test_import_from_json(): void
    {
        $json = json_encode([
            'name' => 'test',
            'display_name' => 'Test Theme',
            'version' => '1.0.0',
            'author' => 'Test',
            'description' => 'Test',
            'config' => [
                'colors' => [
                    'primary' => '#000000',
                    'secondary' => '#111111',
                    'accent' => '#222222',
                ],
                'fonts' => [
                    'sans' => 'Arial',
                ],
            ],
        ]);

        $theme = $this->importer->fromJson($json);

        $this->assertInstanceOf(Theme::class, $theme);
        $this->assertEquals('test', $theme->getName());
        $this->assertEquals('Test Theme', $theme->getDisplayName());
    }

    public function test_import_from_array(): void
    {
        $data = [
            'name' => 'test',
            'display_name' => 'Test Theme',
            'version' => '1.0.0',
            'author' => 'Test',
            'description' => 'Test',
            'config' => [
                'colors' => [
                    'primary' => '#000000',
                    'secondary' => '#111111',
                    'accent' => '#222222',
                ],
                'fonts' => [
                    'sans' => 'Arial',
                ],
            ],
        ];

        $theme = $this->importer->fromArray($data);

        $this->assertInstanceOf(Theme::class, $theme);
        $this->assertEquals('test', $theme->getName());
    }

    public function test_import_from_json_file(): void
    {
        $data = [
            'name' => 'test',
            'display_name' => 'Test Theme',
            'version' => '1.0.0',
            'author' => 'Test',
            'description' => 'Test',
            'config' => [
                'colors' => [
                    'primary' => '#000000',
                    'secondary' => '#111111',
                    'accent' => '#222222',
                ],
                'fonts' => [
                    'sans' => 'Arial',
                ],
            ],
        ];

        $path = $this->tempDir . '/theme.json';
        $this->files->put($path, json_encode($data));

        $theme = $this->importer->fromFile($path);

        $this->assertInstanceOf(Theme::class, $theme);
        $this->assertEquals('test', $theme->getName());
    }

    public function test_import_from_php_file(): void
    {
        $data = [
            'name' => 'test',
            'display_name' => 'Test Theme',
            'version' => '1.0.0',
            'author' => 'Test',
            'description' => 'Test',
            'config' => [
                'colors' => [
                    'primary' => '#000000',
                    'secondary' => '#111111',
                    'accent' => '#222222',
                ],
                'fonts' => [
                    'sans' => 'Arial',
                ],
            ],
        ];

        $path = $this->tempDir . '/theme.php';
        $this->files->put($path, "<?php\n\nreturn " . var_export($data, true) . ";\n");

        $theme = $this->importer->fromFile($path);

        $this->assertInstanceOf(Theme::class, $theme);
        $this->assertEquals('test', $theme->getName());
    }

    public function test_import_invalid_json_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->importer->fromJson('invalid json');
    }

    public function test_import_missing_file_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->importer->fromFile('/nonexistent/file.json');
    }

    public function test_validate_theme_package(): void
    {
        $data = [
            'name' => 'test',
            'display_name' => 'Test Theme',
            'version' => '1.0.0',
            'author' => 'Test',
            'description' => 'Test',
            'config' => [
                'colors' => [
                    'primary' => '#000000',
                    'secondary' => '#111111',
                    'accent' => '#222222',
                ],
                'fonts' => [
                    'sans' => 'Arial',
                ],
            ],
        ];

        $path = $this->tempDir . '/theme.json';
        $this->files->put($path, json_encode($data));

        $result = $this->importer->validate($path);

        $this->assertTrue($result['valid']);
        $this->assertIsArray($result['theme']);
        $this->assertEmpty($result['errors']);
    }

    public function test_validate_invalid_theme_package(): void
    {
        $path = $this->tempDir . '/invalid.json';
        $this->files->put($path, 'invalid json');

        $result = $this->importer->validate($path);

        $this->assertFalse($result['valid']);
        $this->assertNull($result['theme']);
        $this->assertNotEmpty($result['errors']);
    }
}
