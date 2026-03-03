<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Support\Theme;

use Canvastack\Canvastack\Support\Theme\ThemeMigrator;
use PHPUnit\Framework\TestCase;

class ThemeMigratorTest extends TestCase
{
    protected ThemeMigrator $migrator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->migrator = new ThemeMigrator();
    }

    public function test_register_migration(): void
    {
        $this->migrator->register('1.0.0', '1.1.0', function ($data) {
            return $data;
        });

        $this->assertTrue($this->migrator->hasMigration('1.0.0', '1.1.0'));
    }

    public function test_migrate_theme_data(): void
    {
        $this->migrator->register('1.0.0', '1.1.0', function ($data) {
            $data['config']['new_feature'] = true;

            return $data;
        });

        $themeData = [
            'name' => 'test',
            'version' => '1.0.0',
            'config' => [],
        ];

        $migrated = $this->migrator->migrate($themeData, '1.1.0');

        $this->assertEquals('1.1.0', $migrated['version']);
        $this->assertTrue($migrated['config']['new_feature']);
    }

    public function test_migrate_through_multiple_versions(): void
    {
        $this->migrator->register('1.0.0', '1.1.0', function ($data) {
            $data['config']['feature_1'] = true;

            return $data;
        });

        $this->migrator->register('1.1.0', '1.2.0', function ($data) {
            $data['config']['feature_2'] = true;

            return $data;
        });

        $themeData = [
            'name' => 'test',
            'version' => '1.0.0',
            'config' => [],
        ];

        $migrated = $this->migrator->migrate($themeData, '1.2.0');

        $this->assertEquals('1.2.0', $migrated['version']);
        $this->assertTrue($migrated['config']['feature_1']);
        $this->assertTrue($migrated['config']['feature_2']);
    }

    public function test_no_migration_needed_for_same_version(): void
    {
        $themeData = [
            'name' => 'test',
            'version' => '1.0.0',
            'config' => [],
        ];

        $migrated = $this->migrator->migrate($themeData, '1.0.0');

        $this->assertEquals($themeData, $migrated);
    }

    public function test_downgrade_throws_exception(): void
    {
        $this->expectException(\RuntimeException::class);

        $themeData = [
            'name' => 'test',
            'version' => '2.0.0',
            'config' => [],
        ];

        $this->migrator->migrate($themeData, '1.0.0');
    }

    public function test_no_migration_path_throws_exception(): void
    {
        $this->expectException(\RuntimeException::class);

        $themeData = [
            'name' => 'test',
            'version' => '1.0.0',
            'config' => [],
        ];

        $this->migrator->migrate($themeData, '2.0.0');
    }

    public function test_migration_history(): void
    {
        $this->migrator->register('1.0.0', '1.1.0', function ($data) {
            return $data;
        });

        $themeData = [
            'name' => 'test',
            'version' => '1.0.0',
            'config' => [],
        ];

        $this->migrator->migrate($themeData, '1.1.0');

        $history = $this->migrator->getHistory('test');

        $this->assertCount(1, $history);
        $this->assertEquals('1.0.0 -> 1.1.0', $history[0]);
    }

    public function test_migration_summary(): void
    {
        $this->migrator->register('1.0.0', '1.1.0', function ($data) {
            return $data;
        });

        $this->migrator->register('1.1.0', '1.2.0', function ($data) {
            return $data;
        });

        $summary = $this->migrator->getSummary('1.0.0', '1.2.0');

        $this->assertEquals('1.0.0', $summary['from']);
        $this->assertEquals('1.2.0', $summary['to']);
        $this->assertEquals(2, $summary['steps']);
        $this->assertTrue($summary['available']);
    }

    public function test_default_migrations(): void
    {
        $this->migrator->registerDefaultMigrations();

        $this->assertTrue($this->migrator->hasMigration('1.0.0', '1.1.0'));
        $this->assertTrue($this->migrator->hasMigration('1.1.0', '1.2.0'));
        $this->assertTrue($this->migrator->hasMigration('1.2.0', '2.0.0'));
    }

    public function test_validate_theme_data(): void
    {
        $validData = [
            'name' => 'test',
            'version' => '1.0.0',
            'config' => [],
        ];

        $this->assertTrue($this->migrator->validate($validData));

        $invalidData = [
            'name' => 'test',
        ];

        $this->assertFalse($this->migrator->validate($invalidData));
    }

    public function test_migration_adds_metadata(): void
    {
        $this->migrator->register('1.0.0', '1.1.0', function ($data) {
            return $data;
        });

        $themeData = [
            'name' => 'test',
            'version' => '1.0.0',
            'config' => [],
        ];

        $migrated = $this->migrator->migrate($themeData, '1.1.0');

        $this->assertArrayHasKey('migrations', $migrated);
        $this->assertCount(1, $migrated['migrations']);
        $this->assertEquals('1.0.0', $migrated['migrations'][0]['from']);
        $this->assertEquals('1.1.0', $migrated['migrations'][0]['to']);
        $this->assertArrayHasKey('applied_at', $migrated['migrations'][0]);
    }
}
