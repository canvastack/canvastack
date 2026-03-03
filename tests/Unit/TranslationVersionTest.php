<?php

namespace Canvastack\Canvastack\Tests\Unit;

use Canvastack\Canvastack\Support\Localization\TranslationLoader;
use Canvastack\Canvastack\Support\Localization\TranslationVersion;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Support\Facades\File;

class TranslationVersionTest extends TestCase
{
    protected TranslationVersion $version;

    protected TranslationLoader $loader;

    protected string $storagePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loader = $this->app->make(TranslationLoader::class);
        $this->version = new TranslationVersion($this->loader);
        $this->storagePath = storage_path('app/translations/versions');
    }

    protected function tearDown(): void
    {
        // Cleanup test versions
        if (File::isDirectory($this->storagePath)) {
            File::deleteDirectory($this->storagePath);
        }

        parent::tearDown();
    }

    public function test_creates_snapshot(): void
    {
        $result = $this->version->createSnapshot('1.0.0', 'Initial version');

        $this->assertTrue($result);
    }

    public function test_loads_snapshot(): void
    {
        $this->version->createSnapshot('1.0.0', 'Initial version');

        $snapshot = $this->version->loadSnapshot('1.0.0');

        $this->assertNotNull($snapshot);
        $this->assertArrayHasKey('version', $snapshot);
        $this->assertArrayHasKey('locales', $snapshot);
    }

    public function test_gets_all_versions(): void
    {
        $this->version->createSnapshot('1.0.0', 'Version 1');
        $this->version->createSnapshot('1.1.0', 'Version 2');

        $versions = $this->version->getAllVersions();

        $this->assertCount(2, $versions);
    }

    public function test_gets_version_metadata(): void
    {
        $this->version->createSnapshot('1.0.0', 'Test version');

        $metadata = $this->version->getVersionMetadata('1.0.0');

        $this->assertNotNull($metadata);
        $this->assertEquals('1.0.0', $metadata['version']);
        $this->assertEquals('Test version', $metadata['description']);
    }

    public function test_deletes_version(): void
    {
        $this->version->createSnapshot('1.0.0', 'Test version');

        $result = $this->version->deleteVersion('1.0.0');

        $this->assertTrue($result);
        $this->assertNull($this->version->loadSnapshot('1.0.0'));
    }

    public function test_exports_version(): void
    {
        $this->version->createSnapshot('1.0.0', 'Test version');

        $exportPath = storage_path('app/test-export.json');

        $result = $this->version->export('1.0.0', $exportPath);

        $this->assertTrue($result);
        $this->assertFileExists($exportPath);

        // Cleanup
        unlink($exportPath);
    }

    public function test_imports_version(): void
    {
        $this->version->createSnapshot('1.0.0', 'Original version');

        $exportPath = storage_path('app/test-import.json');
        $this->version->export('1.0.0', $exportPath);

        $this->version->deleteVersion('1.0.0');

        $result = $this->version->import($exportPath, '1.0.0');

        $this->assertTrue($result);
        $this->assertNotNull($this->version->loadSnapshot('1.0.0'));

        // Cleanup
        unlink($exportPath);
    }

    public function test_gets_current_version(): void
    {
        $current = $this->version->getCurrentVersion();

        $this->assertIsString($current);
    }

    public function test_sets_current_version(): void
    {
        $this->version->setCurrentVersion('2.0.0');

        $this->assertEquals('2.0.0', $this->version->getCurrentVersion());
    }

    public function test_diffs_versions(): void
    {
        $this->version->createSnapshot('1.0.0', 'Version 1');
        $this->version->createSnapshot('1.1.0', 'Version 2');

        $diff = $this->version->diff('1.0.0', '1.1.0');

        $this->assertArrayHasKey('from_version', $diff);
        $this->assertArrayHasKey('to_version', $diff);
        $this->assertArrayHasKey('locales', $diff);
    }
}
