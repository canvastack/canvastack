<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Support\Config;

use Canvastack\Canvastack\Support\Config\ConfigBackupService;
use Canvastack\Canvastack\Support\Config\ConfigurationManager;
use Canvastack\Canvastack\Support\Config\ConfigValidator;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Support\Facades\File;

class ConfigBackupServiceTest extends TestCase
{
    protected ConfigBackupService $service;

    protected ConfigurationManager $manager;

    protected string $backupPath;

    protected function setUp(): void
    {
        parent::setUp();

        $validator = new ConfigValidator();
        $this->manager = new ConfigurationManager($validator);
        $this->service = new ConfigBackupService($this->manager);
        $this->backupPath = $this->service->getBackupPath();

        // Ensure backup directory exists
        if (!File::exists($this->backupPath)) {
            File::makeDirectory($this->backupPath, 0755, true);
        }
    }

    protected function tearDown(): void
    {
        // Clean up test backups
        if (File::exists($this->backupPath)) {
            $files = File::files($this->backupPath);
            foreach ($files as $file) {
                if (str_contains($file->getFilename(), 'test_')) {
                    File::delete($file->getPathname());
                }
            }
        }

        parent::tearDown();
    }

    /** @test */
    public function it_can_create_backup()
    {
        $result = $this->service->createBackup('test_backup');

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('backup', $result);
        $this->assertArrayHasKey('name', $result['backup']);
        $this->assertArrayHasKey('filename', $result['backup']);
        $this->assertArrayHasKey('path', $result['backup']);
        $this->assertTrue(File::exists($result['backup']['path']));
    }

    /** @test */
    public function it_creates_backup_with_timestamp_if_no_name_provided()
    {
        $result = $this->service->createBackup();

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('backup_', $result['backup']['name']);
    }

    /** @test */
    public function it_can_list_backups()
    {
        // Create test backups
        $this->service->createBackup('test_backup_1');
        $this->service->createBackup('test_backup_2');

        $backups = $this->service->listBackups();

        $this->assertIsArray($backups);
        $this->assertGreaterThanOrEqual(2, count($backups));

        $firstBackup = $backups[0];
        $this->assertArrayHasKey('filename', $firstBackup);
        $this->assertArrayHasKey('name', $firstBackup);
        $this->assertArrayHasKey('created_at', $firstBackup);
        $this->assertArrayHasKey('size', $firstBackup);
        $this->assertArrayHasKey('size_human', $firstBackup);
    }

    /** @test */
    public function it_can_get_backup_details()
    {
        $createResult = $this->service->createBackup('test_backup_details');
        $filename = $createResult['backup']['filename'];

        $result = $this->service->getBackupDetails($filename);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('backup', $result);
        $this->assertArrayHasKey('filename', $result['backup']);
        $this->assertArrayHasKey('name', $result['backup']);
        $this->assertArrayHasKey('groups', $result['backup']);
    }

    /** @test */
    public function it_returns_error_for_non_existent_backup_details()
    {
        $result = $this->service->getBackupDetails('non_existent.json');

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    /** @test */
    public function it_can_delete_backup()
    {
        $createResult = $this->service->createBackup('test_backup_delete');
        $filename = $createResult['backup']['filename'];

        $result = $this->service->deleteBackup($filename);

        $this->assertTrue($result['success']);
        $this->assertFalse(File::exists($this->backupPath . '/' . $filename));
    }

    /** @test */
    public function it_returns_error_when_deleting_non_existent_backup()
    {
        $result = $this->service->deleteBackup('non_existent.json');

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    /** @test */
    public function it_can_export_backup()
    {
        $createResult = $this->service->createBackup('test_backup_export');
        $filename = $createResult['backup']['filename'];

        $result = $this->service->exportBackup($filename);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('path', $result);
        $this->assertArrayHasKey('filename', $result);
        $this->assertArrayHasKey('content', $result);
        $this->assertJson($result['content']);
    }

    /** @test */
    public function it_returns_error_when_exporting_non_existent_backup()
    {
        $result = $this->service->exportBackup('non_existent.json');

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    /** @test */
    public function it_can_import_backup()
    {
        $content = json_encode([
            'name' => 'test_import',
            'created_at' => now()->toIso8601String(),
            'version' => '1.0.0',
            'config' => [
                'version' => '1.0.0',
                'settings' => [],
            ],
        ]);

        $result = $this->service->importBackup($content, 'test_imported_backup.json');

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('filename', $result);
        $this->assertTrue(File::exists($this->backupPath . '/' . $result['filename']));
    }

    /** @test */
    public function it_rejects_invalid_json_import()
    {
        $result = $this->service->importBackup('invalid json', 'test_invalid.json');

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    /** @test */
    public function it_rejects_invalid_backup_structure()
    {
        $content = json_encode([
            'invalid' => 'structure',
        ]);

        $result = $this->service->importBackup($content, 'test_invalid_structure.json');

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    /** @test */
    public function it_can_clean_old_backups()
    {
        // Create multiple backups
        for ($i = 1; $i <= 15; $i++) {
            $this->service->createBackup("test_backup_clean_{$i}");
        }

        $result = $this->service->cleanOldBackups(10);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('deleted', $result);
        $this->assertGreaterThanOrEqual(5, count($result['deleted']));
    }

    /** @test */
    public function it_does_not_clean_if_backups_below_threshold()
    {
        // Create only 5 backups
        for ($i = 1; $i <= 5; $i++) {
            $this->service->createBackup("test_backup_no_clean_{$i}");
        }

        $result = $this->service->cleanOldBackups(10);

        $this->assertTrue($result['success']);
        $this->assertEmpty($result['deleted']);
    }

    /** @test */
    public function it_sanitizes_filenames()
    {
        $result = $this->service->createBackup('test backup with spaces!@#');

        $this->assertTrue($result['success']);
        $this->assertStringNotContainsString(' ', $result['backup']['filename']);
        $this->assertStringNotContainsString('!', $result['backup']['filename']);
        $this->assertStringNotContainsString('@', $result['backup']['filename']);
    }

    /** @test */
    public function it_formats_bytes_correctly()
    {
        $createResult = $this->service->createBackup('test_backup_bytes');
        $filename = $createResult['backup']['filename'];

        $backups = $this->service->listBackups();
        $backup = collect($backups)->firstWhere('filename', $filename);

        $this->assertNotNull($backup);
        $this->assertStringContainsString('B', $backup['size_human']);
    }

    /** @test */
    public function it_sorts_backups_by_date_descending()
    {
        // Create backups with delay
        $this->service->createBackup('test_backup_sort_1');
        sleep(1);
        $this->service->createBackup('test_backup_sort_2');

        $backups = $this->service->listBackups();

        // Most recent should be first
        $this->assertStringContainsString('sort_2', $backups[0]['name']);
    }
}
